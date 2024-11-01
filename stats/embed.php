<?php

/*
    Simple Stats Embed Example
    ==========================
    Embed Simple Stats into your own site. It's quick and easy!
*/

// Include the Simple Stats functions
require_once("functions.php");

// Check for GET/POST variables
$vars = ss_getvars();

// Render the page!
ss_menu($vars);
ss_content($vars);

?>
