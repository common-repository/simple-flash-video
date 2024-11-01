#!/usr/bin/env python

"""
    Generate a randomized log file where the number of entries is the first
    argument passed and generated.log is created.
    
    Use this to test Simple Stats and such. :-)
"""

import random
import sys
import time

o = open("generated.log", "w")

files = [
    ["foo.mp4", 300],
    ["foo.mp4", 300],
    ["foo.mp4", 300],
    ["foo2.mp4", 180],
    ["bar.mp4", 500],
    ["baz.mp4", 600],
    ["test1.mp4", 90],
    ["test2.mp4", 120],
    ["test2.mp4", 120],
    ["test2.mp4", 120],
    ["test2.mp4", 120],
    ["video.mp4", 60],
    ["funny.mp4", 240],
    ["funny.mp4", 240],
]

ips = []
for x in range(max(int(sys.argv[1]) / 10, 100)):
    octets = []
    for x in range(4):
        octets.append(str(random.randint(0, 255)))
    ip = ".".join(octets)
    ips.append(ip)

referers = [
    "http://www.google.com",
    "http://www.amazon.com",
    "http://www.wikipedia.org",
    "http://www.creativecommons.org",
    "http://www.slashdot.org",
    "http://www.xkcd.com",
    "http://www.xkcd.com",
    "http://www.programmer-art.org",
    "http://www.programmer-art.org",
    "http://www.programmer-art.org",
    "http://www.simplethoughtproductions.com",
    "http://www.simplethoughtproductions.com"
]

agents = [
    "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_2; en-us) AppleWebKit/525.18 (KHTML  like Gecko) Version/3.1.1 Safari/525.18",
    "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9b5) Gecko/2008050509 Firefox/3.0b5"
]

for x in range(int(sys.argv[1])):
    ip = ips[random.randint(0, len(ips) - 1)]
    t = time.time()
    agent = agents[random.randint(0, len(agents) - 1)]
    referer = referers[random.randint(0, len(referers) - 1)]
    playerid = "player" + str(random.randint(0, 5))
    filename_id = random.randint(0, len(files) - 1)
    filename = files[filename_id][0]
    hits = 1
    item = random.randint(0, 5)
    title = "undefined"
    states = "1:1:1.2:2:2"
    elapsed = random.randint(50, files[filename_id][1])
    volume = "100:0"
    percent = 100
    width = 480
    height = 360
    remaining = random.randint(0, files[filename_id][1])
    duration = files[filename_id][1]
    entry = "ip=%s, time=%i, agent=%s, referer=%s, playerid=%s, filename=%s, hits=%i, item=%i, title=%s, states=%s, elapsed=%i, volume=%s, percent=%i, width=%i, height=%i, remaining=%i, duration=%i\n" % (ip, t, agent, referer, playerid, filename, hits, item, title, states, elapsed, volume, percent, width, height, remaining, duration)
    o.write(entry)
    
