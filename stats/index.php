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

$starttime = microtime_float();

$vars = ss_getvars();

$date = $vars["date"];
$action = $vars["action"];
$query = $vars["query"];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<!--
    Simple Stats - Keep track of JW FLV view stats
    Copyright 2008 Daniel G. Taylor and Josh Chesarek
-->

<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Simple Stats<?php if ($query) echo " - " . $query; ?></title>
    <link rel="Stylesheet" type="text/css" href="simplestats.css"/>
</head>
<body>
    <div id="container">
        <h1>Simple Stats</h1>
        <?php
        
            ss_menu($vars);
        
        ?>
        <div class="clear"></div>
        <?php
        
            ss_content($vars);
        
        ?>
        <div id="footer">
            Copyright 2008 <a href="http://programmer-art.org/">Daniel G. Taylor</a> &amp; <a href="http://www.simplethoughtproductions.com/">Josh Chesarek</a> | Generated in <?php echo round(microtime_float() - $starttime, 3); ?> seconds
        </div>
    </div>
</body>
</html>
