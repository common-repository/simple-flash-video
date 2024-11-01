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

// These have to be declared BEFORE being set for some things. A good example
// of this is embedding in WordPress *grumble*
global $LOG_FILE_BASE;
global $LOG_USE_DATES;
global $REGENERATE_INTERVAL;
global $VERSION;

/*
     Write files so that they can be deleted. At the moment this creates files
     with full permissions for EVERYBODY, which is BAD. TODO: fix this.
*/
umask(0000);

/*
    Some PHP setups don't have file_get_contents and file_put_contents, so
    define them!
    
    We must do this BEFORE including config.php!
*/
if(!function_exists('file_put_contents'))
{
    function file_put_contents($filename, $data, $file_append = false)
    {
        $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
        if(!$fp)
        {
            trigger_error('file_put_contents cannot write in file.', E_USER_ERROR);
            return;
        }
        
        fputs($fp, $data);
        fclose($fp);
    }
}

if (!function_exists('file_get_contents'))
{
    function file_get_contents($filename, $incpath = false, 
    $resource_context = null)
    {
        if (false === $fh = fopen($filename, 'rb', $incpath))
        {
            trigger_error('file_get_contents() failed to open stream: No 
such file or directory', E_USER_WARNING);
            return false;
        }

        clearstatcache();
        if ($fsize = @filesize($filename))
        {
            $data = fread($fh, $fsize);
        }
        else
        {
            $data = '';
            while (!feof($fh))
            {
                $data .= fread($fh, 8192);
            }
        }

        fclose($fh);
        return $data;
    }
}

/*
    Get the configuration!
*/
require_once("config.php");

/*
    Return the current time as a float (e.g. 4631216.25487 seconds)
*/
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/*
    Sanitize a filename by replacing certain characters.
*/
function sanitize_filename($filename)
{
    return str_replace(array(":", "/"), array("_", "_"), $filename);
}

/*
    Get the current link to the logger script. This is relative to the
    document root and starts with a '/'.
*/
function get_logger_link()
{
    return get_public_location() . "/logger.php";
}

/*
    Get the current link to the javascript. This is relative to the
    document root and starts with a '/'.
*/
function get_js_link()
{
    return get_public_location() . "/simplestats.js";
}

/*
    Get the currently set location of the logger from the javascript.
    This is used later to make sure it is set to the proper location, which
    can change (e.g. site or folder moved).
*/
function get_js_logger()
{
    $lines = file(dirname(__FILE__) . "/simplestats.js");

    foreach ($lines as $line)
    {
        if (ereg("^var LOGSCRIPT = \"(.*)\";", $line, $regs))
        {
            return $regs[1];
        }
    }
    
    return "Unknown";
}

/*
    Update the location of the logger script in the javascript to the latest
    position on the server.
*/
function update_js()
{
    $logger_js = get_js_logger();
    $logger_cur = get_logger_link();
    
    if ($logger != "Unknown" && $logger_js != $logger_cur)
    {
        $data = file_get_contents("simplestats.js");
        $data = str_replace($logger_js, $logger_cur, $data);
        file_put_contents("simplestats.js", $data);
        
        print_info("Logger location has changed and been updated in simplestats.js!");
    }
}

/*
    Return information about Simple Stats and the server (PHP, Python)
*/
function get_server_info()
{
    global $VERSION;
    
    // Get Simple Stats Install Info
    $info = "Simple Stats Install Info\n" .
            "-------------------------\n" .
            "Simple Stats version " . $VERSION . "\n" .
            "Installed to " . dirname(__FILE__) . "\n" .
            "Access via " . get_public_location() . "\n" .
            "simplestats.js points to " . get_js_logger() . "\n" .
            "PHP version " . phpversion() . "\n" .
            "Using " . shell_exec("python -V 2>&1");
    
    return $info;
}

/*
    Print a nicely formatted informational message.
*/
function print_info($msg)
{
    echo '<div class="info"><span>' . $msg . '</span></div>' . "\n";
}

/*
    Print a nicely formatted error message.
*/
function print_error($title, $msg)
{
    $replace = array("&", '"', "\n", "<br/>", "<pre>", "</pre>");
    $with = array("%26", "%22", "%0A", "%0A", "", "");
    
    $text = $msg . "<br/><br/><pre>" . get_server_info() . "</pre>";
    
    echo '<h2>' . $title . '</h2><div class="error">' . '<a class="email" href="mailto:simplestats@programmer-art.org?subject=Simple%20Stats%20Bug%20Report&body=' . str_replace($replace, $with, $text) . '">Email this error output as a bug report</a>' . $text . '</div>' . "\n";
}

/*
    Make sure the required files are available, check permissions, etc.
    Returns true if permissions are okay, false otherwise.
*/
function permissions_okay()
{
    global $LOG_FILE_BASE;

    $existence_check = array("simplestats.py", "simplestats.js", "logger.php",
                             dirname($LOG_FILE_BASE), "about.php",
                             "welcome.php");
    foreach ($existence_check as $path)
    {
        if ($path != "" && !file_exists($path))
        {
            print_error("File/Folder Missing!", "The required file or folder `" . $path . "` cannot be found. If it has been deleted or moved, please restore it.");
            return false;
        }
    }
    
    $write_check = array(dirname(__FILE__), "simplestats.js",
                         dirname($LOG_FILE_BASE), get_log_filename(),
                         ".configured");
    foreach ($write_check as $path)
    {
        if (file_exists($path))
        {
            if (!is_writable($path))
            {
                $type = is_dir($path) ? "directory" : "file";
                
                print_error("Unable to Write!", "Cannot write to " . $type . " `" . $path . "` with the current permissions. Please modify the permissions to allow the web server write access.<br/><br/>An insecure way to do this is to allow everyone write access. You can do this via your sftp client or the terminal if you have shell access, e.g.<br/><br/>chmod a+w " . realpath($path) . "<br/><br/>When the permissions are set properly please refresh the page.");
                return false;
            }
        }
    }
    
    $execute_check = array(dirname(__FILE__), dirname($LOG_FILE_BASE));
    foreach ($execute_check as $path)
    {
        if (file_exists($path))
        {
            if (!is_executable($path))
            {
                $type = is_dir($path) ? "directory" : "file";
                
                print_error("Unable to Execute!", "Cannot execute " . $type . " `" . $path . "` with the current permissions. Please modify the permissions to allow the web server execute access.<br/><br/>An insecure way to do this is to allow everyone execute access. You can do this via your sftp client or the terminal if you have shell access, e.g.<br/><br/>chmod a+x " . realpath($path) . "<br/><br/>When the permissions are set properly please refresh the page.");
                return false;
            }
        }
    }
    
    return true;
}

/*
    Return a log file name given a time. Takes into account whether or not
    to use dates in the log name. If time is not given, assume now.
*/
function get_log_filename($date = "")
{
    global $LOG_FILE_BASE;
    global $LOG_USE_DATES;
    
    if ($date == "")
        $date = date("Ym");
    
    if ($LOG_USE_DATES)
    {
        $log = $LOG_FILE_BASE . "-" . $date;
    }
    else
    {
        $log = $LOG_FILE;
    }
    
    return $log . ".log";
}

/*
    Get a nicely formatted date from a string like 200806 (YYYYMM).
*/
function get_nice_date($date)
{
    $parts = strptime($date, "%Y%m");
    return date("F Y", mktime(0, 0, 0, $parts["tm_mon"] + 1, 15, $parts["tm_year"]));
}

/*
    Return a nicely formatted date from a log file.
*/
function get_log_date($log)
{
    global $LOG_FILE_BASE;
    global $LOG_USE_DATES;
    
    if (!$LOG_USE_DATES)
    {
        return "";
    }
    
    return get_nice_date(substr($log, strlen($LOG_FILE_BASE) + 1, 6));
}

/*
    Return a list of dates for which log files are available. The dates
    are formatted as YYYYMM.
*/
function get_log_file_dates()
{
    global $LOG_FILE_BASE;
    
    $dates = array();
    
    $handle = opendir(dirname($LOG_FILE_BASE));
    while (($file = readdir($handle)) !== false)
    {
        $pattern = "/^" . str_replace("/", "\/", basename($LOG_FILE_BASE)) . "\-([0-9][0-9][0-9][0-9][01][0-9])\.log$/";
        if (preg_match($pattern, $file, $groups))
        {
            $date = $groups[1];
            $dates[] = $date;
        }
    }
    
    rsort($dates);
    
    return $dates;
}

/*
    Return the public path on the server.
*/
function get_public_location()
{
    $root = $_SERVER["DOCUMENT_ROOT"];
    $path = dirname(__FILE__);
    
    $loc = substr($path, strlen($root));
    
    if (substr($loc, 0, 1) != "/")
    {
        $loc = "/" . $loc;
    }
    
    return $loc;
}

/*
    Return an existing query string minus the Simple Stats variables.
*/
function get_existing_query_string()
{
    parse_str($_SERVER["QUERY_STRING"], $qs);
    
    foreach(array("query", "date", "action") as $key)
    {
        if (array_key_exists($key, $qs))
        {
            unset($qs[$key]);
        }
    }
    
    $s = "?";
    foreach($qs as $key => $value)
    {
        $s .= $key . "=" . $value . "&";
    }
    
    return $s;
}

/*
    Load the date, action, and query from GET/POST data and return an
    associative array of these values.
    
    The return value of this function can be directly passed to ss_content().
*/
function ss_getvars()
{
    $vars = array();
    
    $vars["date"] = $_REQUEST["date"];
    $vars["action"] = $_REQUEST["action"];
    $vars["query"] = $_REQUEST["query"];

    if ($vars["date"] == "")
    {
        $vars["date"] = date("Ym");
    }
    
    if (!file_exists(".configured"))
    {
        $vars["action"] = "welcome";
    }

    return $vars;
}

/*
    Render the Simple Stats menu for a given set of variables.
*/
function ss_menu($vars)
{
    $date = $vars["date"];
    $action = $vars["action"];
    $query = $vars["query"];
    $existing_query = get_existing_query_string();
    
?>
<div id="menusearch">
    <ul id="menu">
        <li><a href="<?php echo $existing_query; ?>date=<?php echo $date; ?>">Overview</a></li>
        <?php if (($action != "about") && ($action != "welcome")) { ?>
        <li><a href="#detailed">Details</a></li>
        <li><a href="<?php echo $existing_query; ?>action=regenerate&query=<?php echo $query; ?>&date=<?php echo $date; ?>">Regenerate</a></li>
        <?php } ?>
        <li><a href="<?php echo $existing_query; ?>action=about">About</a></li>
    </ul>
    <div id="ipsearch">
        <form method="post" action="<?php echo $existing_query; ?>"><input class="txt" name="query" value="<?php if ($query != "") { echo $query; } else { echo 'IP Address or File'; } ?>" onFocus="javascript:if(this.value=='IP Address or File') this.value = ''; else this.select()" onBlur="javascript:if(this.value=='') this.value='<?php if ($query != "") { echo $query; } else { echo 'IP Address or File'; } ?>'"/><input type="hidden" name="date" value="<?php echo $date; ?>"/><input type="submit" value="Show Details"/></form>
    </div>
</div>
<?php
}

/*
    Render the Simple Stats page for a given set of variables (date, action,
    query) taking into account permissions and other checks, including
    error reporting when something goes wrong.
*/
function ss_content($vars)
{
    global $LOG_USE_DATES;
    global $REGENERATE_INTERVAL;

    $date = $vars["date"];
    $action = $vars["action"];
    $query = $vars["query"];
    
    if (permissions_okay())
    {
        update_js();
        
        if ($action == "" || $action == "regenerate")
        {
            $dates = array();
            
            if ($LOG_USE_DATES)
            {
                $dates = get_log_file_dates();
            }
            
            $log = get_log_filename($date);
            
            if (!file_exists($log))
            {
                if (sizeof($dates))
                {
                    $not_found = get_log_date($log);
                    $date = $dates[sizeof($dates) - 1];
                    $log = get_log_filename($date);
                    
                    print_info("Log for " . $not_found . " was not found. Showing " . get_log_date($log) . " instead.");
                }
                else
                {
                    print_error("No Logs Found", "No log files have been found. Please make sure your site is setup correctly and that there have been visits this month.<br/><br/><a href='" . get_existing_query_string() . "action=welcome'>Return to the setup page</a>");
                    return;
                }
            }

            if ($query != "")
                $run_file = realpath(".") . "/output/" . str_replace("/", "_", str_replace(array(":", "/"), array("_", "_"), $query)) . ".lastrun";
            else
                $run_file = realpath(".") . "/" . $log . ".lastrun";

            $retval = 0;
            $output = array();
            if ($action == "regenerate" || !file_exists($run_file) || ((filemtime($run_file) + ($REGENERATE_INTERVAL * 60)) < time()))
            {
                $existing_qs = get_existing_query_string();
                
                $cmd = "python simplestats.py ";
                
                if ($query != "")
                {
                    $cmd .= "--query='" . $query ."'";
                }
                else
                {
                    $cmd .= "--summary";
                }
                
                $cmd .= " --date='" . $date . "' -p '" . get_public_location() . "' -g '" . $existing_qs . "' " . $log . " 2>&1";
                
                exec($cmd, $output, $retval);
                
                if (!$retval)
                {
                    touch($run_file);
                }
            }
            
            if (sizeof($dates) > 1)
            {
                echo '<form id="dates" method="post" action=""><span>Available Logs: <select name="date">';
                foreach($dates as $d)
                {
                    $selected = "";
                    if ($date == $d)
                    {
                        $selected = ' selected="selected"';
                    }
                    echo '<option value="' . $d . '"' . $selected . '>' . get_nice_date($d) . '</option>';
                }
                echo '</select><input type="submit" value="View"/></span></form>';
            }
            if ($retval)
            {
                print_error("Error Output", "<pre>" . implode("\n", $output) . "</pre>");
            }
            else
            {
                if ($query != "")
                {
                    exec("python simplestats.py --query='" . $query . "' --date='" . $date . "' --get-output-path " . $log, $output, $retval);
                    include($output[sizeof($output) - 1]);
                }
                else
                {
                    include("output/" . sanitize_filename($log) . $date . ".html");
                }
            }
        }
        elseif ($action == "about")
        {
            include("about.php");
        }
        elseif ($action == "welcome")
        {
            $can_write = true;
            if (!file_exists(".configured"))
            {
                $can_write = touch(".configured");
            }
            
            if (!$can_write)
            {
                print_error("Unable to Write", "There was an error trying to write '.configured' so that Simple Stats knows it has been configured and no longer shows the initial setup page. Permissions should have already been checked so perhaps the disk is full, your quota has been reached, or another problem is causing this issue.");
            }
            else
            {
                include("welcome.php");
            }
        }
        else
        {
            print_error("Invalid Action!", "Unknown action! ('" . $action . "')");
        }
    }
}

?>
