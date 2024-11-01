#!/usr/bin/env python

"""
    Simple Stats
    ============
    Generate statistics for the JW FLV player and provide nice graphs and tables
    using Open Flash Chart and XHTML.
    
    http://www.simplethoughtproductions.com/simple-stats/
    
    Usage
    -----
    simplestats.py --summary myfile.log
    simplestats.py --query=192.168.0.1 myfile.log
    simplestats.py --query=/videos/myvideo.flv myfile.log
    
    These commands will generate the following, which can then be opened by
    a browser or included via PHP or another server-side language into a 
    stats interface:
    output/logfilename.html
    output/192.168.0.1.html
    output/_videos_myvideo.flv.html
    
    Contributers
    ------------
    Daniel G. Taylor <dan@programmer-art.org>
    Josh Chesarek <josh@simplethoughtproductions.com>
    
    License
    -------
    Copyright 2008 Daniel G. Taylor
    
    Released under the Creative Commons Attribution Noncommercial Share Alike
    license. For more information please see the following: 
    
    http://creativecommons.org/licenses/by-nc-sa/3.0/
    
    This work is NOT licensed for commercial use. If you use it to manage stats
    on a commercial site, or provide stats information to customers through the
    use of this script, then you must buy a commercial license. Commercial
    licenses are affordable, simple to obtain, and help provide further
    development of Simple Stats. Please see the following:
    
    http://www.simplethoughtproductions.com/simple-stats-licensing/
"""

import cPickle
import os
import os.path
import re
import sys

from OpenFlashChart import graph, graph_object
from optparse import OptionParser
from urllib import quote
from urlparse import urlsplit

# ==============================================================================
# Settings go here!
# ==============================================================================

# The maximum number of items to show in a graph (along the x-axis)
GRAPH_ITEMS_MAX = 10

# The maximum length of names along the x-axis
GRAPH_MAX_LABEL_LENGTH = 25

# The maximum number of items to show in the detail tables
MAX_FILES_DETAILED = 25
MAX_IPS_DETAILED = 25
MAX_REFERERS_DETAILED = 25

# Graph dimensions
GRAPH_WIDTH = 720
GRAPH_HEIGHT = 400

# Colors
GRAPH_BACKGROUND_COLOR = "#ffffff"
GRAPH_TITLE_COLOR = "#000000"
GRAPH_GRID_COLOR = "#cccccc"
GRAPH_LABEL_COLOR = "#222222"

GRAPH_BAR_OPACITY = 75

GRAPH_TOTAL_HITS_COLOR = "#1996dc"
GRAPH_UNIQUE_HITS_COLOR = "#ab19dc"
GRAPH_ELAPSED_COLOR = "#33cc11"
GRAPH_PERCENT_COLOR = "#c49316"
GRAPH_IP_COLOR = "#993322"
#GRAPH_REFERER_COLOR = "#b5e92b"
GRAPH_POPULARITY_COLOR = "#b5e92b"

# The precision at which to map popularity within a specific file
# The default is 10 and is precise to 1/10th of the file.
FILE_POPULARITY_PRECISION = 10

# ==============================================================================
# Internal stuff you probably don't want to mess with!
# ==============================================================================

VERSION = open("VERSION").read().strip()

# States
STATE_NONE      = 0
STATE_BUFFERING = 1
STATE_PLAYING   = 2
STATE_FINISHED  = 3

# ==============================================================================

# Python 2.3.x doesn't support sets, so define a function to return a set!
if not hasattr(__builtins__, "set"):
    def set(lst):
        """
            Return a set (list of unique objects) from a list.
        """
        s = []
        
        for item in lst:
            if item not in s:
                s.append(item)
        
        return s

# ==============================================================================

class State(object):
    """
        An object representing a state at a specific point in time.
    """
    def __init__(self, state, pos, seconds):
        """
            Store the state and number of seconds at which this state was
            activated.
        """
        self.type = int(state)
        self.pos = int(pos)
        self.seconds = int(seconds)
    
    def __repr__(self):
        """
            Return a friendly representation of this State object, for use e.g.
            inside the Python interactive interpreter.
        """
        return "<State: " + str(self.type) + ", " + str(self.pos) + ", " + \
               str(self.seconds) + ">"

class Stat(object):
    """
        An object representing a single statistic from the log file.
    """
    def __init__(self, filename=""):
        """
            Set sane defaults so that items aren't missing when they are not
            part of the log file.
        """
        self.title = ""
        self.filename = filename
        self.ip = ""
        self.agent = ""
        self.referer = ""
        self.hits = 0
        self.elapsed = 0
        self.duration = 0
        self.client = ""
        self.version = ""
        
    def __repr__(self):
        """
            Return a friendly representation of this Stat object, for use e.g.
            inside the Python interactive interpreter.
        """
        return "<Stat: " + ", ".join([str(key) + "=" + \
                str(self.__dict__[key]) for key in self.__dict__]) + ">"
    
    def get_popularity(self):
        """
            We are given a list of States
            [ <State: 0, 0, 0>,
                <State: 1, 0, 0>,
                <State: 2, 0, 2>,
                <State: 1, 15, 17>,
                <State: 2, 15, 21>,
                <State: 1, 25, 31> ]
            
            And we need to turn this into a mapping
            The mapping is split into X pieces where each piece counts as
            viewed iff a piece of the video was played within the region of
            length duration / X.
            
            For the example above, say the duration is 40 seconds. Say we
            want X = 10 (popularity for each 10th of the video). We can see
            that the viewer left while stuck buffering at 25 seconds into
            the video, so we can see the end was not watched.
            
            The mapping would look like (where each value represents
            40 / 10 = 4 seconds, so the first True is for 0 to 4 seconds,
            the second for 5 to 8 seconds, and so on):
            
            watched = [1, 1, 1, 1, 1, 1, 0, 0, 0, 0]
            
            This counts as a view for seconds 0 to 28 and no view for
            seconds 29 - 40.
        """
        if not self.duration or not self.states:
            return []
        
        precision = max(1, self.duration / FILE_POPULARITY_PRECISION)
        
        popularity = [0 for x in range(100 / FILE_POPULARITY_PRECISION)]
        
        last_state = None
        last_end = -1
        playing = False
        for state in self.states:
            if not playing and state.type == STATE_PLAYING:
                playing = True
                last_state = state
            elif playing and state.type != STATE_PLAYING:
                playing = False
                start = last_state.pos / precision
                if start == last_end:
                    start += 1
                
                if last_state.pos < state.pos:
                    end = (state.pos / precision)
                else:
                    end = (last_state.pos + (last_state.seconds - \
                           state.seconds)) / precision
                
                if end >= len(popularity):
                    end = len(popularity) - 1
                
                for x in range(start, end + 1):
                    try:
                        popularity[x] += 1
                    except Exception, e:
                        pass
                
                last_end = end
        
        if playing:
            start = (state.pos / precision)
            end = ((state.pos + self.elapsed - state.seconds) / precision)
            
            if end >= len(popularity):
                end = len(popularity) - 1
            
            for x in range(start, end + 1):
                try:
                    popularity[x] += 1
                except Exception, e:
                    pass
        
        return popularity
        
    def get_buffer_time(self):
        """
            We are given a list of states
            [ <State: 0, 0, 0>,
                <State: 1, 0, 0>,
                <State: 2, 0, 2>,
                <State: 1, 15, 17>,
                <State: 2, 15, 21>,
                <State: 1, 25, 31> ]
            
            The buffering time can be calculated as the difference
            between state 1 -> state 2 in real time. For the example above
            we see that the time spent buffering was:
            
            buffered = (2 - 0) + (21 - 17) = 6 seconds
        """
        if not self.duration or not self.states:
            return -1
        
        buffer_time = 0
        buffering = True
        last_state = State(STATE_BUFFERING, 0, 0)
        for state in self.states:
            if not buffering and state.type == STATE_BUFFERING:
                buffering = True
                last_state = state
            elif buffering and state.type != STATE_BUFFERING:
                buffering = False
                buffer_time += state.seconds - last_state.seconds
        
        if buffering:
            buffer_time += self.elapsed - state.seconds
        
        return buffer_time

class SummaryItem(object):
    """
        An object representing a summary of information about a single file
        in the log, such as the number of hits and average elapsed time.
    """
    def __init__(self):
        self.ips = []
        self.ip_hits = {}
        self.agents = []
        self.referers = []
        self.referer_hits = {}
        self.referer_host_hits = {}
        self.title = "undefined"
        self.hits = 0
        self.elapsed = []
        self.elapsed_avg = 0
        self.duration = 0
        self.percent_watched = 0
        self.popularity = []
        self.buffering = []
        self.buffering_avg = 0
    
    def __repr__(self):
        """
            Return a friendly representation of this SummaryItem object, for use
            e.g. inside the Python interactive interpreter.
        """
        return "<SummaryItem: hits=" + str(self.hits) + ", elapsed=" + \
               str(self.elapsed_avg) + ", duration=" + str(self.duration) + ">"
    
    def keys_ip_hits(self):
        """
            Return a list of keys sorted by the number of hits per ip in
            increasing order for a particular file, where the keys are
            ip addresses.
        """
        keys = self.ip_hits.keys()
        keys.sort(lambda x, y: cmp(self.ip_hits[y], self.ip_hits[x]))
        keys = keys[:MAX_IPS_DETAILED]
        
        return keys
    
    def keys_referer_hits(self):
        """
            Return a list of keys sorted by the number of hits per referer in
            increasing order for a particular file, where the keys are
            referer pages.
        """
        keys = self.referer_hits.keys()
        keys.sort(lambda x, y: cmp(self.referer_hits[y],
                                   self.referer_hits[x]))
        keys = keys[:MAX_REFERERS_DETAILED]
        
        return keys
    
    def keys_referer_host_hits(self):
        """
            Return a list of keys sorted by the number of hits per referer host
            in increasing order for a particular file, where the keys are the
            referer hostname (domains).
        """
        keys = self.referer_host_hits.keys()
        keys.sort(lambda x, y: cmp(self.referer_host_hits[y],
                                   self.referer_host_hits[x]))
        keys = keys[:GRAPH_ITEMS_MAX]
        
        return keys

class Summary(dict):
    """
        A dictionary of SummaryItems that represents a total summary of all
        files contained in the log. This object provides special methods for
        retrieving keys sorted on particular criteria, such as the most hits
        or longest average elapsed time.
    """
    def __init__(self):
        """
            Initialize dictionary, set sane defaults, clear all caches.
        """
        dict.__init__(self)
        self.pos = 0
        self.common_prefix_len = 0
        self.ip_hits = {}
        self.agent_hits = {}
        self.referer_hits = {}
        self.referer_host_hits = {}
        self.reset_caches()
        self.version = VERSION
        self.clients = {}
        self.versions = {}
    
    def reset_caches(self):
        """
            Clear all caches so that they are recalculated when sorted keys
            are requested. This should be called whenever the summary is
            updated with new information so that new caches are generated
            that take this new information into account.
        """
        self._keys_hits = None
        self._keys_elapsed_avg = None
        self._keys_percent_watched = None
        self._keys_ip_hits = None
        self._keys_agent_hits = None
        self._keys_referer_hits = None
        self._keys_referer_host_hits = None
        self._keys_clients = None
    
    def calculate_common_prefix(self):
        """
            Recalculate the common prefix length that all files share. This
            should be called whenever the summary object is updated from the
            log file. The common prefix length is used when returning file
            titles and names for simplifying their display in graphs and
            tables.
        """
        prefix = None
        if len(self) > 1:
            for key in self:
                if prefix == None:
                    prefix = key
                else:
                    for x in range(len(key), -1, -1):
                        if prefix.startswith(key[:x]):
                            prefix = key[:x]
                            break
                    if prefix == "":
                        break
        elif len(self) == 1:
            prefix = os.path.dirname(self.keys()[0]) + "/"
        
        if prefix:
            self.common_prefix_len = len(prefix)
        else:
            self.common_prefix_len = 0
    
    def keys_hits(self):
        """
            Return a list of keys sorted by the total number of hits per file,
            sorted from most hits to fewest.
        """
        if self._keys_hits:
            return self._keys_hits
    
        keys = self.keys()
        keys.sort(lambda x, y: cmp(self[y].hits, self[x].hits))
        #keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_hits = keys
        return keys
    
    def keys_hits_titles(self):
        """
            Return a list of keys sorted by the total number of hits per file,
            with each element either the title of the video (if available) or
            the file name of the video minus any common prefix that all file
            names have in common.
        """
        return [self[key].title != "undefined" and self[key].title or key[self.common_prefix_len:] for key in self.keys_hits()]
    
    def keys_elapsed_avg(self):
        """
            Return a list of keys sorted by the average elapsed time per file,
            sorted from longest to shortest.
        """
        if self._keys_elapsed_avg:
            return self._keys_elapsed_avg
        
        keys = self.keys()
        keys.sort(lambda x, y: cmp(self[y].elapsed_avg, self[x].elapsed_avg))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_elapsed_avg = keys
        return keys
    
    def keys_elapsed_avg_titles(self):
        """
            Return a list of keys sorted by the average_elapsed time per file,
            with each element either the title of the video (if available) or
            the file name of the video minus any common prefix that all file
            names have in common. 
        """
        return [self[key].title != "undefined" and self[key].title or key[self.common_prefix_len:] for key in self.keys_elapsed_avg()]
    
    def keys_percent_watched(self):
        """
            Return a list of keys sorted by the percent of the file that was
            watched, sorted from highest to lowest.
        """
        if self._keys_percent_watched:
            return self._keys_percent_watched
            
        keys = self.keys()
        keys.sort(lambda x, y: cmp(self[y].percent_watched, self[x].percent_watched))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_percent_watched = keys
        return keys
    
    def keys_percent_watched_titles(self):
        """
            Return a list of keys sorted by the percent of the file that was
            watched, with each element either the title of the video (if
            available) or the file name of the video minus any common prefix
            that all file names have in common.
        """
        return [self[key].title != "undefined" and self[key].title or key[self.common_prefix_len:] for key in self.keys_percent_watched()]
    
    def keys_ip_hits(self):
        """
            Return a list of IPs, sorted by the number of hits per IP, from
            highest to lowest.
        """
        if self._keys_ip_hits:
            return self._keys_ip_hits
        
        keys = self.ip_hits.keys()
        keys.sort(lambda x, y: cmp(self.ip_hits[y], self.ip_hits[x]))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_ip_hits = keys
        return keys
    
    def keys_referer_hits(self):
        """
            Return a list of referers, sorted by the number of hits per referer,
            from highest to lowest.
        """
        if self._keys_referer_hits:
            return self._keys_referer_hits
        
        keys = self.referer_hits.keys()
        keys.sort(lambda x, y: cmp(self.referer_hits[y], self.referer_hits[x]))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_referer_hits = keys
        return keys
    
    def keys_referer_host_hits(self):
        """
            Return a list of referer hosts, sorted by the number of hits per
            referer, from highest to lowest.
        """
        if self._keys_referer_host_hits:
            return self._keys_referer_host_hits
        
        keys = self.referer_host_hits.keys()
        keys.sort(lambda x, y: cmp(self.referer_host_hits[y],
                                   self.referer_host_hits[x]))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_referer_host_hits = keys
        return keys
    
    def keys_agent_hits(self):
        """
            Return a list of user agents, sorted by the number of hits per
            user agent, from highest to lowest.
        """
        if self._keys_agent_hits:
            return self._keys_agent_hits
        
        keys = self.agent_hits.keys()
        keys.sort(lambda x, y: cmp(self.agent_hits[y], self.agent_hits[x]))
        keys = keys[:GRAPH_ITEMS_MAX]
        self._keys_agent_hits = keys
        return keys
    
    def keys_hits_by_ip(self, ip):
        """
            Return a tuple of (hits, keys) where hits is a dict corresponding
            to the number of hits per file from a particular ip and keys are
            the keys (file names) sorted by hits in increasing order.
        """
        file_hits = {}
        for filename in self:
            for address in self[filename].ips:
                if address == ip:
                    if filename not in file_hits:
                        file_hits[filename] = 0
                    file_hits[filename] += 1
        
        top_files_keys = file_hits.keys()
        top_files_keys.sort(lambda x, y: cmp(file_hits[y], file_hits[x]))
        
        return (file_hits, top_files_keys)
    
    def keys_hits_by_ip_titles(self, ip):
        """
            Return a list of keys sorted by the number of hits per file, with
            each element either the title of the video (if available) or the
            file name of the video minus any common prefix that all file names
            have in common.
        """
        hits, keys = self.keys_hits_by_ip(ip)
        return [self[key].title != "undefined" and self[key].title or key[self.common_prefix_len:] for key in keys]
    
    def keys_clients(self):
        """
            Return a list of clients sorted by the number of hits.
        """
        if self._keys_clients:
            return self._keys_clients
        
        keys = self.clients.keys()
        keys.sort(lambda x, y: cmp(self.clients[y], self.clients[x]))
        self._keys_clients = keys
        return keys

class Graph(graph):
    """
        The default base graph for Simple Stats graphs that defines some
        color and style information and simplifies several methods.
    """
    def __init__(self, title):
        graph.__init__(self)
        self.bg_colour = GRAPH_BACKGROUND_COLOR
        
        self.y_max = 100
        self.title(title)
        
        self.set_x_axis_3d(12)
        self.x_axis_colour = GRAPH_GRID_COLOR
        self.x_grid_colour = GRAPH_GRID_COLOR
        self.y_axis_colour = GRAPH_GRID_COLOR
        self.y_grid_colour = GRAPH_GRID_COLOR
        
        self.set_x_label_style(8, GRAPH_LABEL_COLOR, 2)
        self.set_y_label_style(8, GRAPH_LABEL_COLOR)
        
        self.set_x_legend("Videos")
        self.set_y_legend("Hits")
    
    def title(self, text):
        graph.title(self, text, "{font-size:20px; color:%s;}" % GRAPH_TITLE_COLOR)
    
    def bar_3d(self, color, name):
        graph.bar_3d(self, GRAPH_BAR_OPACITY, color, name, 10)
    
    def set_y_legend(self, name):
        graph.set_y_legend(self, name, 12, GRAPH_LABEL_COLOR)
    
    def set_x_legend(self, name):
        graph.set_x_legend(self, name, 12, GRAPH_LABEL_COLOR)

class GraphObject(graph_object):
    """
        Override the default graph renderer.
    """
    def __init__(self, options):
        self.path = options.server_path
        
    def render(self, width, height, data_url):
        """
            Render this graph using the server path given to Simple Stats.
        """
        return graph_object.render(self, width, height, data_url, self.path + os.path.sep)

def title_constrain(title):
    """
        Constrain the length of a title to GRAPH_MAX_LABEL_LENGTH characters.
        This method also URL-escapes the string for graph use.
    """
    if len(title) > GRAPH_MAX_LABEL_LENGTH:
        title = "..." + title[-(GRAPH_MAX_LABEL_LENGTH - 3):]
        
    return quote(title)

def sec_to_str(sec):
    """
        Return a nice string given a number of seconds, such as 21:53. Takes
        into account hours, minutes, and seconds.
    """
    s = ""
    
    if sec < 0:
        return "Unknown"
    
    hours = sec / 3600
    min = (sec % 3600) / 60
    sec = sec % 60
    
    if hours:
        s += str(hours) + ":"
    
    if min and min < 10 and hours:
        s += "0" + str(min) + ":"
    elif min:
        s += str(min) + ":"
    
    if sec < 10 and min:
        s += "0"
    s += str(sec)
    
    return s

def percent_to_str(percent):
    """
        Return a nice string given a percentage.
    """
    s = ""
    
    if percent < 0:
        return "Unknown"
    
    return str(int(percent)) + "%"

def sanitize_filename(filename):
    """
        Return a sanitized file path with certain characters replaced.
    """
    return filename.replace(":", "_").replace("/", "_")

def query(query_string, data):
    """
        Return a query string for use in HTML links. For example, if
        query_string is "?page=foo" and data is "date=200807" this function
        will return "?page=foo&date=200807" while if query_string were "" it
        would return "?date=200807".
    """
    if query_string:
        if not query_string.startswith("?"):
            query_string = "?" + query_string
            
        if data:
            if (not data.startswith("&")) and (not query_string.endswith("&")):
                data = "&" + data
                
            return query_string + data
        else:
            return query_string
    else:
        if not data.startswith("?"):
            data = "?" + data
            
        return data

def load(filename, start=0):
    """
        Load and return a dictionary of log entries from a given file and
        starting position. The dictionary keys are the file names from the log
        entries.
    """
    stats = {}
    
    infile = open(filename)
    
    # If we are given a starting position, seek to it!
    if start:
        infile.seek(start) 
    
    # Process each line from the log
    count = 0
    total_count = 0
    for line in infile.readlines():
        # Set some defaults
        agent = "unknown"
        referer = "unknown"
        filename = "unknown"
        ip = "unknown"
        
        try:
            # Make sure the line isn't a comment or commented out entry.
            line = line.strip()
            if line and not line.startswith("#"):
                # Split each of the parts separated by commas
                try:
                    parts = line.split(", ")
                except:
                    print "Error with line %i: %s" % (total_count, line)
                    continue
                    
                for part in parts:
                    # Split each part into a key, value pair
                    try:
                        moreparts = part.split("=")
                        key, value = moreparts[0], "=".join(moreparts[1:])
                    except:
                        print "Error with line %i, part: %s" % (total_count, part)
                        continue
                    
                    # Process the key, value pair
                    if key == "ip":
                        if value.startswith("::ffff:"):
                            value = value[7:]
                        ip = value
                    elif key in ["agent", "referer", "filename",
                                 "client", "version"]:
                        try:
                            exec(key + " = '" + value + "'")
                        except:
                            print "Error setting %s to %s" % (key, value)
                        if key == "filename":
                            if not stats.has_key(filename):
                                stats[filename] = [Stat(filename)]
                            else:
                                stats[filename].append(Stat(filename))
                            stats[filename][-1].ip = ip
                            stats[filename][-1].agent = agent
                            stats[filename][-1].referer = referer
                        elif key in ["client", "version"]:
                            setattr(stats[filename][-1], key, value)
                    elif key in ["time", "playerid", "width", "height",
                                 "volume", "remaining"]:
                        # We currently do not care about these values!
                        continue
                    elif key in ["states", "volume"]:
                        # Process the state change lists in states and volume
                        more_parts = []
                        for state in value.split("."):
                            try:
                                stateinfo = state.split(":")
                                if len(stateinfo) == 2:
                                    current_state, seconds = stateinfo
                                    pos = -1
                                elif len(stateinfo) == 3:
                                    current_state, pos, seconds = stateinfo
                                
                                more_parts.append(State(current_state, pos,
                                                        seconds))
                            except:
                                print "Error with %s" % part
                        setattr(stats[filename][-1], key, more_parts)
                    else:
                        # Check for known integer values and convert
                        if key in ["width", "height", "duration", "elapsed",
                                   "percent", "time", "remaining", "hits"]:
                            try:
                                value = int(float(value))
                            except ValueError:
                                print "Error with %s" % part
                                continue
                                    
                        setattr(stats[filename][-1], key, value)
                count += 1
            total_count += 1
        except KeyError:
            print "Problem on line %i: %s" % (total_count, line)
	
    print "Loaded %i log entries" % count
    
    return stats, infile.tell()

def summary(filename, options):
    """
        Generate and return a Summary for a given log file name using and 
        updating a cache if possible.
    """
    s = Summary()
    
    # If a cache for this log file exists, load it!
    if options.cache and os.path.exists(filename + ".cache"):
        s = cPickle.load(open(filename + ".cache"))
        if hasattr(s, "version") and s.version == VERSION:
            statlog = os.stat(filename)
            statcache = os.stat(filename + ".cache")
            if statlog.st_mtime <= statcache.st_mtime:
                print "Cache is up to date"
                return s
        else:
            print "Cache version mismatch, regenerating..."
            s = Summary()
    
    print "Loading data..."
    
    stats, pos = load(filename, s.pos)
    
    # If no new stats were found, don't process!
    if not stats:
        return s
    
    s.pos = pos
    
    print "Generating summary..."
    
    # Reset the caches and generate a new summary
    s.reset_caches()
    
    for fname, statslist in stats.items():
        if fname not in s:
            item = SummaryItem()
            s[fname] = item
        else:
            item = s[fname]
        
        for stat in statslist:
            # Keep track of hits per IP
            if stat.ip not in s.ip_hits:
                s.ip_hits[stat.ip] = 0
            s.ip_hits[stat.ip] += 1
            
            # Keep track of hits per referer
            if stat.referer not in s.referer_hits:
                s.referer_hits[stat.referer] = 0
            s.referer_hits[stat.referer] += 1
            
            # Keep track of hits per referer's hostname
            #try:
            #    hostname = urlsplit(stat.referer).hostname
            #    if hostname not in s.referer_host_hits:
            #        s.referer_host_hits[hostname] = 0
            #    s.referer_host_hits[hostname] += 1
            #except:
            #    print "Error splitting hostname from %s" % stat.referer
            
            # Keep track of hits per user agent
            if stat.agent not in s.agent_hits:
                s.agent_hits[stat.agent] = 0
            s.agent_hits[stat.agent] += 1
            
            # Keep track of IP hits per file
            if stat.ip not in item.ip_hits:
                item.ip_hits[stat.ip] = 0
            item.ip_hits[stat.ip] += 1
            
            # Keep track of referers per file
            if stat.referer not in item.referer_hits:
                item.referer_hits[stat.referer] = 0
            item.referer_hits[stat.referer] += 1
            
            # Keep track of referer host hits per file
            #try:
            #    hostname = urlsplit(stat.referer).hostname
            #    if hostname not in item.referer_host_hits:
            #        item.referer_host_hits[hostname] = 0
            #    item.referer_host_hits[hostname] += 1
            #except:
            #    print "Error splitting hostname from %s" % stat.referer
            
            popularity = stat.get_popularity()
            if len(item.popularity) < len(popularity):
                item.popularity += [0 for x in range(len(popularity) - len(item.popularity))]
            
            for x in range(len(popularity)):
                item.popularity[x] += popularity[x]
            
            buffering = stat.get_buffer_time()
            if buffering != -1:
                item.buffering.append(buffering)
            
            item.ips.append(stat.ip)
            item.agents.append(stat.agent)
            item.referers.append(stat.referer)
            item.hits += stat.hits
            for hit in range(stat.hits):
                try:
                    item.elapsed.append(stat.elapsed / stat.hits)
                except ZeroDivisionError:
                    pass
            
            # Keep track of Flash and FLV player versions
            if stat.client:
                if not s.clients.has_key(stat.client):
                    s.clients[stat.client] = 0
                s.clients[stat.client] += 1
            if stat.version:
                if not s.versions.has_key(stat.version):
                    s.versions[stat.version] = 0
                s.versions[stat.version] += 1
                
        # Use the latest duration and title, as these values shouldn't change!
        for x in range(len(statslist) - 1, -1, -1):
            if statslist[x].duration:
                item.duration = statslist[x].duration
                break
        
        for x in range(len(statslist) - 1, -1, -1):
            if statslist[x].title:
                item.title = statslist[x].title
                break
        
        try:
            item.elapsed_avg = reduce(lambda x, y: x + y, item.elapsed) / \
                                                            len(item.elapsed)
        except ZeroDivisionError:
            item.elapsed_avg = -1
        except TypeError:
            item.elapsed_avg = -1
            
        try:
            item.percent_watched = item.elapsed_avg / float(item.duration) * 100.0
        except ZeroDivisionError:
            item.percent_watched = -1
        except TypeError:
            item.percent_watched = -1
        
        try:
            item.buffering_avg = reduce(lambda x, y: x + y, item.buffering, 0) / len(item.buffering)
        except ZeroDivisionError:
            item.buffering_avg = -1
        except TypeError:
            item.buffering_avg = -1
    
    # Update the common prefix length for files
    s.calculate_common_prefix()
    
    # Save the updated summary!
    if options.cache:
        cPickle.dump(s, open(filename + ".cache", "w"), 2)
    
    return s

def write_summary(summary, filename, options):
    """
        Generate graphs, tables, and output a summary page, which defaults to
        ./output/filenamedate.html and can be included as the content in another
        XHTML page.
    """
    date = options.date
    
    if not len(summary):
        print "No items to process..."
        return True
    
    if not os.path.exists("output"):
        os.mkdir("output")
    
    outfile = os.path.join("output", sanitize_filename(filename))
    outlink = os.path.join(options.server_path, outfile)
    
    # =====================
    # Total and unique hits
    # =====================
    g = Graph("Top Total and Unique Hits")
    
    g.bar_3d(GRAPH_TOTAL_HITS_COLOR, "Total")
    g.set_data([summary[key].hits for key in summary.keys_hits()[:GRAPH_ITEMS_MAX]])
    
    g.y_max = summary[summary.keys_hits()[0]].hits
    
    g.bar_3d(GRAPH_UNIQUE_HITS_COLOR, "Unique")
    g.set_data([len(set(summary[key].ips)) for key in summary.keys_hits()[:GRAPH_ITEMS_MAX]])
    
    g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in summary.keys_hits()[:GRAPH_ITEMS_MAX]])
    
    g.set_x_labels(map(title_constrain, summary.keys_hits_titles()[:GRAPH_ITEMS_MAX]))
    
    open(outfile + "_hits%s.txt" % date, "w").write(g.render())
    
    # ========================
    # Top average time watched
    # ========================
    g = Graph("Top Average Time Watched")
    
    g.bar_3d(GRAPH_ELAPSED_COLOR, "Seconds")
    g.set_data([summary[key].elapsed_avg for key in summary.keys_elapsed_avg()])
    
    g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in summary.keys_elapsed_avg()])
    
    g.y_max = summary[summary.keys_elapsed_avg()[0]].elapsed_avg
    
    g.set_x_labels(map(title_constrain, summary.keys_elapsed_avg_titles()))
    
    g.set_y_legend("Seconds")
    
    open(outfile + "_time_watched%s.txt" % date, "w").write(g.render())
    
    # ===========================
    # Top average percent watched
    # ===========================
    g = Graph("Top Average Percent Watched")
    
    g.bar_3d(GRAPH_PERCENT_COLOR, "Percent")
    g.set_data([summary[key].percent_watched for key in summary.keys_percent_watched()])
    
    g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in summary.keys_percent_watched()])
    
    g.y_max = max(100, summary[summary.keys_percent_watched()[0]].percent_watched)
    
    g.set_x_labels(map(title_constrain, summary.keys_percent_watched_titles()))
    
    g.set_y_legend("Percent Watched")
    
    open(outfile + "_percent_watched%s.txt" % date, "w").write(g.render())
    
    # ===========
    # Top viewers
    # ===========
    g = Graph("Top Viewers")
    
    g.bar_3d(GRAPH_IP_COLOR, "Hits")
    g.set_data([summary.ip_hits[key] for key in summary.keys_ip_hits()])
    
    g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in summary.keys_ip_hits()])
    
    g.y_max = summary.ip_hits[summary.keys_ip_hits()[0]]
    
    g.set_x_legend("IP Addresses")
    g.set_x_labels(summary.keys_ip_hits())
    
    open(outfile + "_ips%s.txt" % date, "w").write(g.render())
    
    # ===================
    # Top referer Domains
    # ===================
    #g = Graph("Top Referer Domains")
    
    #g.bar_3d(GRAPH_REFERER_COLOR, "Hits")
    #g.set_data([summary.referer_host_hits[key] for key in summary.keys_referer_host_hits()])
    
    #g.y_max = summary.referer_host_hits[summary.keys_referer_host_hits()[0]]
    
    #g.set_x_legend("Domain")
    #g.set_x_labels(summary.keys_referer_host_hits())
    
    #open(outfile + "_referers%s.txt" % date, "w").write(g.render())
    
    # =============
    # Details table
    # =============
    data_table = "<table><tr><th>Name</th><th>Hits</th><th>Unique Hits</th><th>Time Watched</th><th>Percent Watched</th></tr>\n"
    alt = False
    titles = summary.keys_hits_titles()
    keys = summary.keys_hits()
    for x in range(len(keys)):
        name = titles[x]
        item = summary[keys[x]]
        link = query(options.query_string, "query=%s&date=%s" % (keys[x], date))
        data_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td><td>%i</td><td>%s</td><td>%s</td>\n" % (alt and ' class="alt"' or "", link, name, item.hits, len(set(item.ips)), sec_to_str(item.elapsed_avg), percent_to_str(item.percent_watched))
        alt = not alt
    data_table += "</table>\n"
    
    # =================
    # Top viewers table
    # =================
    ip_keys = summary.keys_ip_hits()
    alt = False
    ip_table = "<table><tr><th>IP Address</th><th>Hits</th></tr>\n"
    for ip in ip_keys:
        link = query(options.query_string, "query=%s&date=%s" % (ip, date))
        ip_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", link, ip, summary.ip_hits[ip])
        alt = not alt
    ip_table += "</table>\n"
    
    # ==================
    # Top referers table
    # ==================
    referer_table = "<table><tr><th>Referer</th><th>Hits</th></tr>\n"
    alt = False
    for referer in summary.keys_referer_hits():
        referer_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", referer, referer, summary.referer_hits[referer])
        alt = not alt
    referer_table += "</table>\n"
    
    # =====================
    # Top user agents table
    # =====================
    agent_table = "<table><tr><th>User Agent</th><th>Hits</th></tr>\n"
    alt = False
    for agent in summary.keys_agent_hits():
        agent_table += "<tr%s><td class=\"name\">%s</td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", agent, summary.agent_hits[agent])
        alt = not alt
    agent_table += "</table>\n"
    
    # =======================
    # Flash client info table
    # =======================
    client_table = "<table><tr><th>Flash Version</th><th>Hits</th></tr>\n"
    alt = False
    for client in summary.keys_clients():
        client_table += "<tr%s><td class=\"name\">%s</td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", client, summary.clients[client])
        alt = not alt
    client_table += "</table>\n"
    
    # =======================
    # Write out XHTML summary
    # =======================
    g = GraphObject(options)
    
    open(outfile + date + ".html", "w").write("""
        <div class="graph">
            %s
        </div>
        <div class="graph">
            %s
        </div>
        <div class="graph">
            %s
        </div>
        <div class="graph">
            %s
        </div>
        <div id="detailed">
            <h2>Detailed Statistics</h2>
            %s
        </div>
        <div>
            <h2>Top Viewers</h2>
            %s
        </div>
        <div>
            <h2>Top Referer Pages</h2>
            %s
        </div>
        <div>
            <h2>Top User Agents</h2>
            %s
        </div>
        <div>
            <h2>Flash Client Summary</h2>
            %s
        </div>
""" % (g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_hits%s.txt" % date),
       g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_time_watched%s.txt" % date),
       g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_percent_watched%s.txt" % date),
       g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_ips%s.txt" % date),
       #g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_referers%s.txt" % date),
       data_table,
       ip_table,
       referer_table,
       agent_table,
       client_table))
       
    return False

def write_ip_summary(summary, options):
    """
        Get information on a specific IP if it has been logged.
    """
    ip = options.query
    date = options.date
    
    if not os.path.exists("output"):
        os.mkdir("output")
    
    if ip in summary.ip_hits:
        outfile = os.path.join("output", ip)
        outlink = os.path.join(options.server_path, outfile)
        
        if options.get_output_path:
            print outfile + "%s.html" % date
            return False
        
        # Create a summary of hits for each file from this IP
        top_files, keys = summary.keys_hits_by_ip(ip)
        titles = summary.keys_hits_by_ip_titles(ip)
        
        # ======================
        # Top videos for this IP
        # ======================
        g = Graph("Top Videos")
    
        g.bar_3d(GRAPH_TOTAL_HITS_COLOR, "Hits")
        g.set_data([top_files[key] for key in keys[:GRAPH_ITEMS_MAX]])
        
        g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in keys[:GRAPH_ITEMS_MAX]])
        
        g.y_max = top_files[keys[0]]
        
        g.set_x_labels(map(title_constrain, titles[:GRAPH_ITEMS_MAX]))
        
        open(outfile + "_hits%s.txt" % date, "w").write(g.render())
        
        # =========================
        # Top videos detailed table
        # =========================
        files_table = "<table><tr><th>Name</th><th>Hits</th></tr>\n"
        alt = False
        for pos in range(len(keys[:MAX_FILES_DETAILED])):
            name = keys[pos]
            title = titles[pos]
            link = query(options.query_string, "query=%s&date=%s" % (name, date))
            files_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", link, title, top_files[name])
            alt = not alt
        files_table += "</table>\n"
        
        # =======================
        # Write out XHTML IP info
        # =======================
        g = GraphObject(options)
        
        open(outfile + "%s.html" % date, "w").write("""
        <div class="graph">
            %s
        </div>
        <div id="detailed">
            <h2>Top Videos</h2>
            %s
        </div>
        """ % (g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + \
                        "_hits%s.txt" % date), files_table))
        
        return False
    
    print "Error: IP Address '%s' not found in log." % ip 
    return True

def write_file_summary(summary, options):
    """
        Get information on a specific filename if it has been logged.
    """
    filename = options.query
    date = options.date
    
    if not len(summary):
        print "No items to process..."
        return True
    
    if filename not in summary:
        # Look for similar filenames
        for name in summary:
            if name.endswith(filename):
                filename = name
                break
        
        if filename not in summary:
            for name in summary:
                if filename in name:
                    filename = name
    
    if filename not in summary:
        print "Error: File '%s' not found in log." % filename
        return True
    else:
        options.query = filename
        
        if not os.path.exists("output"):
            os.mkdir("output")
        
        sumitem = summary[filename]
        outfile = os.path.join("output", sanitize_filename(filename))
        outlink = os.path.join(options.server_path, outfile)
        
        if options.get_output_path:
            print outfile + "%s.html" % date
            return False
        
        ip_hits = sumitem.ip_hits
        ip_keys = sumitem.keys_ip_hits()
        
        referer_hits = sumitem.referer_hits
        referer_keys = sumitem.keys_referer_hits()
        
        #referer_host_hits = sumitem.referer_host_hits
        #referer_host_keys = sumitem.keys_referer_host_hits()
        
        # ========================================
        # Top total and unique hits for this video
        # ========================================
        g = Graph("Total / Unique / Top Viewer Hits")
    
        g.bar_3d(GRAPH_TOTAL_HITS_COLOR, "Total")
        g.set_data([sumitem.hits])
        
        g.bar_3d(GRAPH_UNIQUE_HITS_COLOR, "Unique")
        g.set_data([len(set(sumitem.ips))])
        
        g.bar_3d(GRAPH_IP_COLOR, "Top Viewer")
        g.set_data([ip_hits[ip_keys[0]]])
        
        g.y_max = sumitem.hits
        
        g.set_x_labels([sumitem.title != "undefined" and sumitem.title or filename[summary.common_prefix_len:]])
        
        g.set_x_legend("Video")
        
        g.x_label_style_orientation = 0
        
        open(outfile + "_hits%s.txt" % date, "w").write(g.render())
        
        # ==========================
        # Top viewers for this video
        # ==========================
        g = Graph("Top Viewers")
        
        g.bar_3d(GRAPH_IP_COLOR, "Hits")
        g.set_data([ip_hits[key] for key in ip_keys[:GRAPH_ITEMS_MAX]])
        
        g.set_links([query(options.query_string, "query=%s&date=%s" % (key, date)) for key in ip_keys[:GRAPH_ITEMS_MAX]])
        
        g.y_max = ip_hits[ip_keys[0]]
        
        g.set_x_legend("IP Addresses")
        g.set_x_labels(ip_keys[:GRAPH_ITEMS_MAX])
        
        open(outfile + "_ips%s.txt" % date, "w").write(g.render())
        
        # ===============================
        # Top referer hits for this video
        # ===============================
        
        #g = Graph("Top Referer Domains")
        
        #g.bar_3d(GRAPH_REFERER_COLOR, "Hits")
        #g.set_data([referer_host_hits[key] for key in referer_host_keys])
        
        #g.y_max = referer_host_hits[referer_host_keys[0]]
        
        #g.set_x_legend("Domain")
        #g.set_x_labels(referer_host_keys)
        
        #open(outfile + "_referers%s.txt" % date, "w").write(g.render())
        
        # =============================
        # Popularity map for this video
        # =============================
        g = Graph("Popularity Map")
        
        g.bar_3d(GRAPH_POPULARITY_COLOR, "Views")
        g.set_data(sumitem.popularity)
        
        try:
            g.y_max = max(sumitem.popularity)
        except ValueError:
            g.y_max = 0
        
        g.set_x_legend("File Region")
        
        labels = ["%i%%25" % percent for percent in range(0, 100, FILE_POPULARITY_PRECISION)]
        g.set_x_labels(labels)
        
        open(outfile + "_popularity%s.txt" % date, "w").write(g.render())
        
        # =============
        # Details table
        # =============        
        data_table = "<table class=\"detailed\"><tr><th>Property</th><th>Value</th></tr>\n"
        name = sumitem.title != "undefined" and sumitem.title or filename[summary.common_prefix_len:]
        rows = [
            ["Name", name],
            ["Total Hits", sumitem.hits],
            ["Unique Hits", len(set(sumitem.ips))],
            ["Duration", sec_to_str(sumitem.duration)],
            ["Average Time Watched", sec_to_str(sumitem.elapsed_avg)],
            ["Average Percent Watched", percent_to_str(sumitem.percent_watched)],
            ["Average Time Buffering", sec_to_str(sumitem.buffering_avg)],
        ]
        odd = False
        for item, value in rows:
            data_table += "<tr%s><td>%s</td><td>%s</td></tr>\n" % (odd and " class=\"alt\"" or "", item, str(value))
            odd = not odd
        data_table += "</table>\n"
        
        # =================
        # Top viewers table
        # =================
        alt = False
        ip_table = "<table><tr><th>IP Address</th><th>Hits</th></tr>\n"
        for ip in ip_keys:
            link = query(options.query_string, "query=%s&date=%s" % (ip, date))
            ip_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", link, ip, ip_hits[ip])
            alt = not alt
        ip_table += "</table>\n"
        
        # ==================
        # Top referers table
        # ==================
        alt = False
        referer_table = "<table><tr><th>Referer</th><th>Hits</th></tr>\n"
        for referer in referer_keys:
            referer_table += "<tr%s><td class=\"name\"><a href=\"%s\">%s</a></td><td>%i</td></tr>\n" % (alt and ' class="alt"' or "", referer, referer, referer_hits[referer])
            alt = not alt
        referer_table += "</table>\n"
        
        # =====================
        # Write XHTML file info
        # =====================
        g = GraphObject(options)
        
        open(outfile + "%s.html" % date, "w").write("""
        <div class="graph">
            %s
        </div>
        <div class="graph">
            %s
        </div>
        <div class="graph">
            %s
        </div>
        <div id="detailed">
            <h2>Details</h2>
            %s
        </div>
        <div>
            <h2>Top Viewers</h2>
            %s
        </div>
        <div>
            <h2>Top Referer Pages</h2>
            %s
        </div>
        """ % (g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_hits%s.txt" % date),
               g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_ips%s.txt" % date),
               #g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_referers%s.txt" % date),
               g.render(GRAPH_WIDTH, GRAPH_HEIGHT, outlink + "_popularity%s.txt" % date),
               data_table,
               ip_table,
               referer_table))
        
        return False

if __name__ == "__main__":
    """
        Handle commandline arguments and run the script when run as a program.
    """
    ip_re = re.compile("^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$")
    retval = 0
    
    parser = OptionParser(usage = "%prog [-s] [-v] [-q query] [-d date] log", version="Simple Stats Version " + str(VERSION))
    
    parser.add_option("-s", "--summary", action="store_true", default=False, 
                      dest="summary", help="Generate summary")
    parser.add_option("-q", "--query", dest="query", help="Query file or IP")
    parser.add_option("-d", "--date", dest="date", default="",
                      help="Generate links with a particular date")
    parser.add_option("-p", "--server-path", dest="server_path", default="",
                      help="Path on the server to prepend to links")
    parser.add_option("-n", "--no-cache", dest="cache", action="store_false",
                      default=True, help="Disable cache")
    parser.add_option("-g", "--query-string", dest="query_string",
                      help="Server query string to append")
    parser.add_option("-l", "--get-output-path", dest="get_output_path",
                      action="store_true", default=False,
                      help="Print the output path and exit")
    
    options, args = parser.parse_args()
    
    if len(args) != 1:
        parser.print_help()
        raise SystemExit(1)
    
    if not os.path.exists(args[0]):
        print args[0], "not found."
        raise SystemExit(1)
    
    if options.query:
        sum = summary(args[0], options)
        if ip_re.search(options.query):
            retval = write_ip_summary(sum, options)
        else:
            retval = write_file_summary(sum, options)
    else:
        if not options.summary:
            print "Assuming --summary..."
        sum = summary(args[0], options)
        retval = write_summary(sum, args[0], options)
    
    raise SystemExit(retval)

