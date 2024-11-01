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

require_once("functions.php");

$LOG = get_log_filename();

function client_info()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $time = time();
    $agent = str_replace(",", " ", $_SERVER['HTTP_USER_AGENT']);
    $referer = str_replace(",", " ", $_SERVER['HTTP_REFERER']);
    
    return "ip=" . $ip . ", time=" . $time . ", agent=" . $agent . ", referer=" . $referer;
}

function handle_stats($dict)
{
    return implode(", ", explode("`", $dict["stats"]));
}

$handle = fopen($LOG, "a");
if ($_GET)
{
    fwrite($handle, client_info() . ", " . handle_stats($_GET) . "\n");
}
if ($_POST)
{
    fwrite($handle, client_info() . ", " . handle_stats($_POST) . "\n");
}
fclose($handle);

?>
