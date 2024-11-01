<?php

require_once("functions.php");

global $VERSION;

?>
<h2><a href="http://www.simplethoughtproductions.com/simple-stats/">Simple Stats Version <?php echo $VERSION ?></a></h2>
<div class="half left">
    <h3>About</h3>
    <p>
        Simple Stats is a simple to install and easy to use statistics package for <a href="http://www.jeroenwijering.com/?item=JW_FLV_Player">Jeroen Wijering's FLV player</a>. It originally started as a simple Ruby script and has since evolved into a high quality professional package that is much, much faster through the use of aggressive caching and smarter code.
    </p>
    <p>
        Simple stats consists of Javascript, PHP, and Python components. The Javascript component gathers statistics while the PHP and Python components log and process the log, respectively.
    </p>
    <p>
        Please consider a donation if this software has helped you!
    </p>
<form id="donate" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="danielgtaylor@gmail.com">
<input type="hidden" name="item_name" value="Simple Stats">
<input type="hidden" name="no_shipping" value="0">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="tax" value="0">
<input type="hidden" name="lc" value="US">
<input type="hidden" name="bn" value="PP-DonationsBF">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
<div class="half right">
    <h3>Contributers</h3>
    <p>
        The following people are responsible for Simple Stats:
    </p>
    <dl>
        <dt><a href="http://programmer-art.org/">Daniel G. Taylor</a></dt>
        <dd>design, programming, theme</dd>
        <dt><a href="http://www.simplethoughtproductions.com/">Josh Chesarek</a></dt>
        <dd>planning, design, testing</dd>
        <dt>Jenny Pettman</dt>
        <dd>food, rum, putting up with us</dd>
    </dl>
</div>
<div class="clear"></div>
<div class="half left">
    <h3>License</h3>
    <p>
        Simple Stats is released under the <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Creative Commons Attribution Noncommercial Share Alike license</a>.<br/><br/>
    This work is NOT licensed for commercial use. If you use it to manage stats
    on a commercial site, or provide stats information to customers through the
    use of this script, then you must buy a commercial license. Commercial
    licenses are affordable, simple to obtain, and help provide further
    development of Simple Stats.
    <br/><br/>
    <a href="http://www.simplethoughtproductions.com/simple-stats-licensing/">Obtain a commercial license</a>
    </p>
</div>
<div class="half right">
    <h3>Technologies Used</h3>
    <ul>
        <li><a href="http://www.python.org">Python</a></li>
        <li><a href="http://www.php.net">PHP</a></li>
        <li><a href="http://en.wikipedia.org/wiki/JavaScript">Javascript</a></li>
        <li><a href="http://teethgrinder.co.uk/open-flash-chart/">Open Flash Chart</a></li>
        <li><a href="http://www.adobe.com/products/flash/about/">Adobe Flash</a></li>
        <li><a href="http://en.wikipedia.org/wiki/XHTML">XHTML</a></li>
        <li><a href="http://en.wikipedia.org/wiki/CSS">CSS</a></li>
    </ul>
</div>
<div class="clear"></div>
<p>
    <a href="<?php echo get_existing_query_string(); ?>action=welcome">Return to the initial setup page</a>
</p>
