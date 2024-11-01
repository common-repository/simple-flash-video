/*
    JW FLV Player Statistics Script Version 1.2.2
    =============================================
    Keep statistics such as play time and count on JW FLV players
    embedded in a web page, sending the stats data to a logging
    script when the page is unloaded.
    
    Contributors
    ------------
    Daniel G. Taylor <dan@programmer-art.org>
    Josh Chesarek <josh@simplethoughtproductions.com>
    
    License
    -------
    Copyright 2009 Daniel G. Taylor <dan@programmer-art.org

    Released under the Creative Commons Attribution Noncommercial Share Alike
    license. For more information please see the following: 
    
    http://creativecommons.org/licenses/by-nc-sa/3.0/
    
    This work is NOT licensed for commercial use. If you use it to manage stats
    on a commercial site, or provide stats information to customers through the
    use of this script, then you must buy a commercial license. Commercial
    licenses are affordable, simple to obtain, and help provide further
    development of Simple Stats. Please see the following:
    
    http://www.simplethoughtproductions.com/simple-stats-licensing/

*/

/*
    This is the URI that is sent the stats data when the page is
    unloaded. Set this to your PHP or other logging script.
*/
var LOGSCRIPT = "logger.php";

/*
    The stats object contains information about the various
    FLV player instances on the page. Each player instance
    can have multiple videos (e.g. from a playlist) and each
    video will have statistics. The structure looks like:
    
    stats
    {
        "playerid1":
        [
            "filename1"
            {
                "timer": 8327452,
                "hits": 1,
                "item": 0,
                "title": "undefined",
                "percent": 42,
                "width": 640,
                "height": 480,
                "states": ["0:0:0", "1:0:0", "2:0:1", "1:16:14", "2:16:16"],
                "elapsed_previous": 21,
                "elapsed": 22,
                "remianing": 10,
                "duration": 16,
                "volume": ["100:0:0"],
            },
            "filename2"
            {
                "timer": 38421231,
                "hits": 3,
                "item": 1,
                "title": "undefined",
                "percent": 12,
                "width": 320,
                "height": 240,
                "states": ["0:0:0", "1:0:0", "2:1:1", "1:14:14", "2:14:16"],
                "elapsed_previous": 21,
                "elapsed": 22,
                "remianing": 10,
                "duration": 16,
                "volume": ["100:0:0", "80:5:5", "100:9:22"],
            },
        ]
    }
    
    Stats Explanations
    ------------------
    timer: Time at which the video was started
    item: The index in the playlist (or 0 if there is only one file)
    title: The title of the video or "undefined"
    percent: The percent of the video buffered at unload
    width: The width of the player
    height: The height of the player
    state: A list of state changes where 0 = stopped, 1 = buffering, 
        2 = playing.
    elapsed_previous: The previously recorded elapsed time used to
        calculate the entire elapsed video playback time.
    elapsed: The entire elapsed video playback time.
    remaining: The amount of time remaining on unload.
    duration: The total duration of the video in seconds.
    volume: A list of volume states where 100 = full volume, 0 = muted
    
    The state and volume lists consist of the state, the position in the stream
    in seconds and the real time in seconds since the start of the stream,
    separated by colons.
    
    The current filenames structure contains the current filename
    for a given player id and is filled with the first video when
    the player is instantiated.
*/
var stats = null;
var current_filenames = [];

/*
    Get the time difference in seconds between a time and now.
*/
function tdiff(time)
{
    var date = new Date();
    var diff = (date.getTime() - time) / 1000;
    
    return Math.round(diff);
}

/*
    Return an SWFObject given an ID.
*/
function get_swf_object(swf)
{
    if (navigator.appName.indexOf("Microsoft") !== -1)
    {
        return window[swf];
    }
    else
    {
        return document[swf];
    }
}

/*
    Get update messages from the player and save the values for the next time
    the status is updated.
*/
function getUpdate(typ, pr1, pr2, swf, version)
{
    /*
        If we have not yet gotten a filename update from a particular
        player, do not set any stats. The index and filename info
        should be the first to get sent!
    */
	//alert("getUpdate");
	
    if (typ !== "item" && !current_filenames.hasOwnProperty(swf))
    {
        return;    
    }
    
    var stat = null;
    
    if (stats === null)
    {
        stats = [];
    }
    
    if (typeof(version) === "undefined")
	{
	    version = 3;
	}
    
    if (typ === "item")
    {
        var swfobj = get_swf_object(swf);
        var movie = null;
        
        if (version === 3)
        {
            movie = swfobj.itemData(pr1);
        }
        else if (version === 4)
        {
            movie = swfobj.getPlaylist()[pr1];
        }
        
        if (typeof(movie) === "undefined")
        {
            alert("Movie object not found " + swfobj + " " + pr1);
        }
        
        current_filenames[swf] = movie.file;
        if (!stats.hasOwnProperty(swf))
        {
            stats[swf] = {};
        }
        if (!stats[swf].hasOwnProperty(movie.file))
        {
            stats[swf][movie.file] = {};
        }
        
        stat = stats[swf][movie.file];
        
        var date = new Date();
        stat.timer = date.getTime();
        
        if (!stat.hasOwnProperty("hits"))
        {
            stat.hits = 1;
        }
        else
        {
            stat.hits += 1;
        }
        
        stat.item = pr1;
        stat.title = movie.title;
        
        // Set some defaults!
        if (!stat.hasOwnProperty("states"))
        {
            stat.states = [];
        }
        if (!stat.hasOwnProperty("elapsed"))
        {
            stat.elapsed = 0;
        }
        if (!stat.hasOwnProperty("elapsed_previous"))
        {
            stat.elapsed_previous = 0;
        }
        if (!stat.hasOwnProperty("volume"))
        {
            stat.volume = [];
        }
        if (!stat.hasOwnProperty("client") && version === 4)
        {
            stat.client = swfobj.getConfig().client.replace(",", ".");
        }
        if (!stat.hasOwnProperty("version") && version === 4)
        {
            stat.version = swfobj.getConfig().version;
        }
    }
    else
    {
        stat = stats[swf][current_filenames[swf]];
        
        if (typ === "load")
        {
            stat.percent = pr1;
        }
        else if (typ === "size")
        {
            stat.width = pr1;
            stat.height = pr2;
        }
        else if (typ === "state")
        {
            if (version == 4)
            {
                if (pr1 === "IDLE")
                {
                    pr1 = 0;
                }
                else if (pr1 === "BUFFERING")
                {
                    pr1 = 1;
                }
                else if (pr1 === "PLAYING")
                {
                    pr1 = 2;
                }
                else if (pr1 === "COMPLETED")
                {
                    pr1 = 3;
					if (typ == "state" && pr1 == "3") 
					{ 
						setTimeout("parent.Shadowbox.close()", 3000);
					}
                }
                else
                {
                    return;
                }
            }
            stat.states.push(pr1 + ":" + stat.elapsed_previous + ":" + tdiff(stat.timer));
        }
        else if (typ === "time")
        {
            if (Math.round(pr1) != stat.elapsed_previous)
            {
                stat.elapsed += 1;
            }
            
            stat.elapsed_previous = Math.round(pr1);
            stat.remaining = Math.round(pr2);
            stat.duration = Math.round(pr1 + pr2);
        }
        else if (typ === "volume")
        {
            stat.volume.push(pr1 + ":" + stat.elapsed_previous + ":" + tdiff(stat.timer));
        }
    }
}

// Gets the browser specific XmlHttpRequest Object.
// From http://www.dynamicajax.com/fr/AJAX_Hello_World-.html
function getXmlHttpRequestObject()
{
    if (window.XMLHttpRequest)
    {
        return new XMLHttpRequest(); //Not IE
    }
    else if (window.ActiveXObject)
    {
        return new ActiveXObject("Microsoft.XMLHTTP"); //IE
    }

    return null;
}

/*
    Turn a stats object into a parseable list of parameters. The
    format is as follows:
    
    playerid=X`filename=Y`key=value`key=value`key=value`...
    
    Values that are lists are delimited by '.' such as state and volume,
    e.g. states=0:0:0.1:0:1.2:0:5.1:14:19
    
    Example
    -------
    playerid=1`filename=foo.mp4`index=0`title=Test`video`filename=foo2.mp4  index=1`...`playerid=2`...
*/
function stats_to_params()
{
    var params = "stats=";
    
    for (var playerid in stats)
    {
        if (stats.hasOwnProperty(playerid))
        {
            params += "playerid=" + playerid + "`";
            for (var filename in stats[playerid])
            {
                if (stats[playerid].hasOwnProperty(filename))
                {
                    params += "filename=" + filename + "`";
                    for (var stat in stats[playerid][filename])
                    {
                        if (!stats[playerid][filename].hasOwnProperty(stat) || stat === "elapsed_previous" || stat === "timer")
                        {
                            continue;
                        }
                        
                        if (stat === "states" || stat === "volume")
                        {
                            params += stat + "=";
                            for (var state in stats[playerid][filename][stat])
                            {
                                params += stats[playerid][filename][stat][state] + ".";
                            }
                            params = params.substr(0, params.length - 1);
                            params += "`";
                        }
                        else
                        {
                            params += stat + "=" + stats[playerid][filename][stat] + "`";
                        }
                    }
                    params = params.substr(0, params.length - 1);
                    params += "`";
                }
            }
            params = params.substr(0, params.length - 1);
            params += "`";
        }
    }
    params = params.substr(0, params.length - 1);
    
    return params;
}

/*
    Log the current status to the server with an XmlHttpRequest.
*/
function log_status()
{
    if (stats === null)
    {
        return;
    }
    var req = getXmlHttpRequestObject();
    
    if (req !== 0 && (req.readyState === 4 || req.readyState === 0))
    {
        var params = stats_to_params();
        var async = false;
        if (window.ActiveXObject)
        {
            async = true;
        }
        req.open("POST", LOGSCRIPT, async);
        req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        req.setRequestHeader("Content-Length", params.length);
        req.setRequestHeader("Connection", "close");
        req.send(params);
    }
}

/*
    The following maps the new 4.x player functionality back onto the 3.x
    functionality so that everything is logged the same.
*/
function playerReady(obj)
{
    var player = get_swf_object(obj.id);
    
    /*
        Sometimes the player can start playing with autoplay=true BEFORE the
        javascript connection is initialized which will cause the js to never
        see the item update for the currently playing item. In this case we
        can manually call getUpdate when the player is not idle!
    */
    if (player.getConfig().state != "IDLE")
    {
        getUpdate('item', player.getConfig().item, null, player.id, 4);
    }
    
    player.addControllerListener("ITEM", "function (obj) { getUpdate('item', obj.index, null, obj.id, 4); }");
    player.addModelListener("LOADED", "function (obj) { getUpdate('load', obj.loaded, null, obj.id, 4); }");
    player.addControllerListener("RESIZE", "function (obj) { getUpdate('size', obj.width, obj.height, obj.id, 4); }");
    player.addModelListener("STATE", "function (obj) { getUpdate('state', obj.newstate, null, obj.id, 4); }");
    player.addModelListener("TIME", "function (obj) { getUpdate('time', obj.position, obj.duration - obj.position, obj.id, 4); }");
    player.addControllerListener("VOLUME", "function (obj) { getUpdate('volume', obj.percentage, null, obj.id, 4); }");
	
}

/*
	Close Shadowbox once video is complete. 
*/
function closeSB(typ, pr1, pr2, pid) { if (typ == "state" && pr1 == "COMPLETED") { setTimeout("parent.Shadowbox.close()", 2000)} }

/*
    Setup the script to be run when the window is unloaded. If you need
    another function to run on unload or use a javascript library such
    as MooTools you may need to add the log_status function to its
    unload functionality!
*/
if (navigator.appName.indexOf("Microsoft") !== -1)
{
    window.attachEvent("onunload", log_status);
}
else
{
    window.addEventListener("unload", log_status, false);
}

