<?php

/*
    This file is part of Simple Stats, a statistics package for the JW FLV
    player.
    
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
*/

// ------------------------------- Configuration -------------------------------

// The log file all the stats go to
$LOG_FILE_BASE = "logs/stats";

// Whether log files are date-based
$LOG_USE_DATES = true;

// The interval in minutes between cache refreshes
$REGENERATE_INTERVAL = 15;

// -----------------------------------------------------------------------------

$VERSION = trim(file_get_contents("VERSION"));

?>
