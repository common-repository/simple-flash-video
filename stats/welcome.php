<?php

require_once("functions.php");

$player_version = $_REQUEST["player_version"];

?>
<h2>Congratulations!</h2>
<p>
    Simple Stats has been installed and configured successfully. What next?
</p>
<div class="half left">
    <h3>Javascript Setup</h3>
    <p>
        Include the following javascript link on pages you you wish to have monitored. It should be placed inside the '&lt;head&gt;' element of your web page where you would normally include other scripts and should be included last.
    </p>
    <p>
        The generated link below can be copied and pasted safely.
    </p>
    <div class="code">
        &lt;script type=&quot;text/javascript&quot; src=&quot;<?php echo get_js_link(); ?>&quot;&gt;&lt;/script&gt;
    </div>
</div>
<div class="half right">
    <?php
    
    if ($player_version == "3")
    {
    
    ?>
    <h3>Player Setup (3.x Series)</h3>
    <p>
        When creating the video player you must be sure to enable Javascript support and give each player a Javascript ID. See an example below:
    </p>
    <pre class="code">&lt;div id="<em>videocontainer1</em>"&gt;This text will be replaced&lt;/div&gt;

&lt;script type="text/javascript"&gt;
  var so = new SWFObject('/embed/mediaplayer.swf',<em>'video1'</em>,'400','320','8');
  <strong>so.addParam('allowscriptaccess','always');</strong>
  so.addVariable('width','400');
  so.addVariable('height','320');
  so.addVariable('file','/videos/test.mp4');
  <strong>so.addVariable('enablejs','true');</strong>
  <strong>so.addVariable('javascriptid',<em>'video1'</em>);</strong>
  so.write(<em>'videocontainer1'</em>);
&lt;/script&gt;
    </pre>
    <a class="imagelink" href="?action=welcome">Show an example for the 4.x series player</a>
    <?php
    
    }
    else
    {
    
    ?>
    <h3>Player Setup (4.x Series)</h3>
    <p>
        Below is an example showing how to setup the video player.
    </p>
    <pre class="code">&lt;div id="<em>videocontainer1</em>"&gt;This text will be replaced&lt;/div&gt;

&lt;script type="text/javascript"&gt;
  var so = new SWFObject('/embed/mediaplayer.swf',<em>'video1'</em>,'400','320','8');
  <strong>so.addParam('allowscriptaccess','always');</strong>
  so.addVariable('width','400');
  so.addVariable('height','320');
  so.addVariable('file','/videos/test.mp4');
  so.write(<em>'videocontainer1'</em>);
&lt;/script&gt;
    </pre>
    <p>
        <a class="imagelink" href="?action=welcome&player_version=3">Show an example for the 3.x series player</a>
    </p>
    <?php
    
    }
    
    ?>
</div>
<div class="clear"></div>
<p>
    Continue to statistics by selecting Overview from the menu or <a href="<?php echo get_existing_query_string(); ?>">refreshing the page</a>.
</p>
