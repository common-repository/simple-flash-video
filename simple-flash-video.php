<?php
/*
Plugin Name: Simple Flash Video
Version: 1.8
Plugin URI: http://www.simplethoughtproductions.com/category/simple-flash-video/
Description: Allows the addition of FLV and MP4 video to blog posts powered by Jeroen Wijering's FLV Media Player, SWFObject by Geoff Stearns, and Shadowbox by Michael J. I. Jackson
Author: Josh Chesarek & Daniel G. Taylor
Author URI: http://www.simplethoughtproductions.com

Copyright Josh Chesarek & Daniel G. Taylor 2008

This file is part of Simple Flash Video.

	Simple Flash Video is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	(at your option) any later version.

	Simple Flash Video is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
	
	
	Software used or referenced in the making of this plugin:
	
	JW FLV Player - http://www.jeroenwijering.com/?item=JW_FLV_Media_Player
	Shadowbox - http://jquery.com/demo/thickbox/
	Flash Video Player Plugin for Wordpress - http://www.mac-dev.net/blog/index.php
	
*/
if (!class_exists("SimpleFlashVideoPlugin"))
{
	class SimpleFlashVideoPlugin 
	{
				
		function SimpleFlashVideoPlugin() 
		{
			
		}
		
		function init() 
		{
			// Setup Simple Stats
			$dir = getcwd();
			chdir(dirname(__FILE__) . "/stats");
			require_once("functions.php");
			update_js(true);
			chdir($dir);
			
			 //Setup the default plugin settings
			 // Lets find out where the hell the plugins folder is
			 // Thanks to http://planetozh.com/
			 // Pre-2.6 compatibility
			 if ( !defined('WP_CONTENT_URL') )
			     define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
		     if ( !defined('WP_CONTENT_DIR') )
		  	     define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		 
			// Guess the location
			$plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
			$plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));;
			$sfvOptions = getAdminOptions();
			$sfvOptions['FULL_PLUGIN_URL'] = $plugin_url;

			
			//write the options
			writeAdminOptions($sfvOptions);
			
						
		}
			
		// Footer!
		function addFooterCode()
		{
			$sfvOptions = getAdminOptions();
			if ($sfvOptions['PLUGINS'] == 'ltas')
			{
				echo '<script type="text/javascript" src="http://www.ltassrv.com/serve/api5.4.asp?' . xmlentities($sfvOptions['LONGTAILNUMBER']) . '"></script>';	
			}
			
		}
		
		//Headers!
		function addHeaderCode() 
   		{
   			global $plugin_url;
			?>
				 
<!-- Start of Simple Flash Video Plugin -->
<script type="text/javascript" src="<?php echo $plugin_url ?>/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $plugin_url ?>/shadowbox/shadowbox.css"/>
<script type="text/javascript" src="<?php echo $plugin_url ?>/swfobject.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url ?>/stats/simplestats.js"></script>


<link rel="stylesheet" href="<?php echo $plugin_url ?>/shadowbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo $plugin_url ?>/swfobject.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url ?>/shadowbox/yui-utilities.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url ?>/shadowbox/shadowbox-yui.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url ?>/shadowbox/shadowbox.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url ?>/stats/simplestats.js"></script>

<script type="text/javascript">window.onload = function(){

	 var options = {
		resizeLgImages:	 true,
		autoplayMovies:	 false,
		animSequence: 		"sync",
		counterType:		"skip",
		loadingImage:	   "<?php echo $plugin_url ?>/shadowbox/loadingAnimation.gif"
		
	};


	Shadowbox.init(options);

};
</script>


<!-- End of Simple Flash Video Plugin -->

			<?php
	   
		}//End Headers
   
   		 			
	} // End class declaration
 
 
 
	 //Global Variables 
	 $videoid = 1;
	 $arr = array();
	 $cur_tag = ""; 
	 
	 // Lets find out where the hell the plugins folder is
	 // Thanks to http://planetozh.com/
	 // Pre-2.6 compatibility
	 if ( !defined('WP_CONTENT_URL') )
	     define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
     if ( !defined('WP_CONTENT_DIR') )
  	     define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
 
	 // Guess the location
	  $plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
	  $plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));


	//---------------------------------------------------------
	// Reads in XML file
	//---------------------------------------------------------
	function start_el_handler($parser,$name,$attribs)
	{
		global $cur_tag;
		$cur_tag = $name;
	}
				

	//---------------------------------------------------------
	// Reads in XML file
	//---------------------------------------------------------
	function end_el_handler($parser,$name)
	{
	
	}

	//---------------------------------------------------------
	// Reads in XML file
	//---------------------------------------------------------
	function char_handler($parser,$data) 
	{
		global $cur_tag, $arr;
		$arr[$cur_tag] .= trim($data);
	}
 
	//---------------------------------------------------------
	// Gets and returns the options from the Config.xml
	//---------------------------------------------------------
	function getAdminOptions()
	{
 		global $arr;
 		$arr = array();
 		
 		 		
 		//BITS ON THE RUN SUPPORT
		if ((substr($arguments['filename'],0,31) == "http://content.bitsontherun.com") || (($sfvOptions['BOTR'] == "true") && ($arguments['botr'] != "false")) || ($arguments['botr'] == "true"))
		{
			$parts = pathinfo($filename);		
			$$config_file_xml = 'http://content.bitsontherun.com/xml' . "/" . substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])) . "xml";
		}
 		//Not Bits just load the regular config
 		else
 		{
 					
   			$config_file_xml = dirname(__FILE__) . "/config.xml";
   		}
   		
   		//Read in the Config XML to get the Default Height and Width of the Video
 		$xml = file_get_contents($config_file_xml) or die("Can't open remote files!");
				
		$parser = xml_parser_create();
		xml_set_element_handler($parser, "start_el_handler", 'end_el_handler');
		xml_set_character_data_handler($parser, "char_handler");
		xml_parse($parser,$xml);
	
		return $arr;
	}

	//---------------------------------------------------------
	// Gets and returns the options from bits on the run
	//---------------------------------------------------------
	function getBOTROptions($filename)
	{
 		global $arr;
 		$arr = array();
 		
 		 		
 		
		$parts = pathinfo($filename);		
		$config_file_xml = 'http://content.bitsontherun.com/xml' . "/" . substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])) . "xml";
		
 		
   		//Read in the Config XML to get the Default Height and Width of the Video
 		$xml = file_get_contents($config_file_xml) or die("Can't open remote files!");
				
		$parser = xml_parser_create();
		xml_set_element_handler($parser, "start_el_handler", 'end_el_handler');
		xml_set_character_data_handler($parser, "char_handler");
		xml_parse($parser,$xml);
	
		return $arr;
	}
	
		
	//---------------------------------------------------------
	// Writes the settings to the config.xml
	//--------------------------------------------------------- 
	function writeAdminOptions($values)
	{
		
		
		//Write the settings to config.xml
			$config_file_xml = dirname(__FILE__) . "/config.xml";
			$fh = fopen($config_file_xml, 'w') or die("can't open file");
			$xml = "<?xml version=\"1.0\"?>\n\n<config>\n";
			
			foreach ($values as $key => $value)
			{
				if ($key != "CONFIG")
				{
										
					if ($value != "")
					{
						if($key == "PLUGIN_OPTIONS")
						{
							$array=array();
							$array = preg_split("/[=&]/", $values['PLUGIN_OPTIONS']);
							for($i = 0; $i < count($array); $i = $i + 2)
							{
								if ($array[$i+1] != "")
								{
									$xml .= "\t<" . strtolower($array["$i"]) . ">" . $array[$i+1] . "</" . strtolower($array[$i]) . ">\n";
								}
							}
							
						}	
					
					
						else
						{
							$xml .= "\t<" . strtolower($key) . ">" . $value . "</" . strtolower($key) . ">\n";
						}
					}
						
				}
			}
			
			
			
			$xml .= "</config>\n";
			fwrite($fh, $xml);
			fclose($fh);

		
	}
	
	//---------------------------------------------------------
	// Writes the SWFCode
	//--------------------------------------------------------- 
	function swfoCode($arguments, $thumb)
	{
		//get the options for the player
		$sfvOptions = getAdminOptions();
		global $videoid;
		global $arguments;
		
		//settings that we do not want passed through to the SWFOBject
		$plugin_settings = array("shadowbox", "filename", "longtail");
		
		//Setup the image to use when video is not playing.
		if (!array_key_exists('image', $arguments) && !array_key_exists('vid_image', $arguments))
		{
			$arguments['image'] = $thumb;
		}
		
		//ReSetup the output as all of them start the same
		$output ='<div class="ltas-ad" id="video'. $videoid. '"><a href="http://www.adobe.com/go/getflashplayer"><img src="' . $sfvOptions['FULL_PLUGIN_URL'] . '/upgrade.png" alt="Upgrade Flash to watch video" /></a></div>' . "\n";
								
		//Write the video player using the provided and the config.xml variables 
		$output .= '<script type="text/javascript">' . "\n";
		$output .= 'var s' . $videoid . ' = new SWFObject("'; 
		
		//BITS ON THE RUN SUPPORT
		if ((substr($arguments['filename'],0,31) == "http://content.bitsontherun.com") || (($sfvOptions['BOTR'] == "true") && ($arguments['botr'] != "false")) || ($arguments['botr'] == "true"))
		{
			
			$bfile = $arguments['filename'];
			$sfvOptions = getBOTROptions($bfile);
			$output .= $arguments['filename'] . '","sfvideo' . $videoid . '","';
			
			//setup the arguments to use options from the bits config if they are not set by the user. 
			if(!array_key_exists("title", $arguments))
			{
				$arguments['title'] = $sfvOptions['TITLE'];
			}
			
			if (!array_key_exists("description", $arguments))
			{
				$arguments['description'] = $sfvOptions['DESCRIPTION'];
			}
			
			if (!array_key_exists("height", $arguments))
			{
				$arguments['height'] = $sfvOptions['HEIGHT'];
				$arguments['width'] = $sfvOptions['WIDTH'];
			}	 
		}
		else
		{
			$output .= $sfvOptions['FULL_PLUGIN_URL'] . '/mediaplayer.swf' . '","sfvideo' . $videoid . '","'; 
		}
					
		//Check to see if new width has been given, if not use default
		if (!array_key_exists('width', $arguments) )
		{
			
				$output .= $sfvOptions['WIDTH'];
				$arguments['width'] = $sfvOptions['WIDTH'];
			
		}
					 
		else
		{
			$output .= $arguments['width'];
		}	
						
		//Check to see if a new height has been given, if not use default
		if (!array_key_exists('height', $arguments) )
		{
			$output .= '","' . $sfvOptions['HEIGHT'];
			$arguments['height'] = $sfvOptions['HEIGHT'];
		}
						
		else
		{
			$output .= '","' . $arguments['height'];
		}
							
		$output .= '","9.0.115");' . "\n";
		
		//if it is bits we need to setup the embed in a special way
		if ((substr($arguments['filename'],0,31) == "http://content.bitsontherun.com") || (($sfvOptions['BOTR'] == "true") && ($arguments['botr'] != "false")) || ($arguments['botr'] == "true"))
		{
			$parts = pathinfo($arguments['filename']);		
			$configfile = 'http://content.bitsontherun.com/xml' . "/" . substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])) . "xml";
		
			$output .= 's' . $videoid . '.addParam("allowfullscreen","true");' . "\n";
			$output .= 's' . $videoid . '.addParam("allowscriptaccess","always");' . "\n";
			$output .= 's' . $videoid . '.addParam("wmode","transparent");' . "\n";
			$output .= 's' . $videoid . '.addVariable("config", "' . $configfile . '");' . "\n";
		}
		
		//it isnt bits so do the standard setup
		else
		{
			$output .= 's' . $videoid . '.addParam("allowfullscreen","true");' . "\n";
			$output .= 's' . $videoid . '.addParam("allowscriptaccess","always");' . "\n";
			$output .= 's' . $videoid . '.addParam("wmode","transparent");' . "\n";
			$output .= 's' . $videoid . '.addVariable("file", "' . $arguments['filename'] . '");' . "\n";
			$output .= 's' . $videoid . '.addVariable("config", "' . $sfvOptions['FULL_PLUGIN_URL'] . '/config.xml");' . "\n";
		}
										
						
		if (!array_key_exists('title', $arguments) )
		{
			$arguments['title'] = get_the_title();
			//lets setup a title tht wont upset Longtail
			$arguments['title'] = str_replace("&nbsp;", " ", $arguments['title']);
		}
			
		else 
		{
			//lets setup a title tht wont upset Longtail
			$arguments['title'] = str_replace("&nbsp;", " ", $arguments['title']);	
			$arguments['description'] = str_replace("&nbsp;", " ", $arguments['description']);
		}			
							
		//Time to add the variables passed in. 				
		foreach($arguments as $name => $value)
		{
			if (array_search($name, $plugin_settings) === false)
			{
				if ($name == "vid_image") 
				{
					
					$output .= 's' . $videoid . '.addVariable("image", "' . $value . '");' . "\n";
	
				}
				else
				{
					$name = str_replace("_", ".", $name);
					$value = str_replace("%20;", " ", $value);
					$output .= 's' . $videoid . '.addVariable("'. $name . '", "' . $value . '");' . "\n";
				}
			}
		}
		
					
		$output .= 's' . $videoid . '.write("video' . $videoid . '");' . "\n";
		$output .= '</script>'. "\n";
		
		// Lets make some money! Google Checkout Integration
					if ((array_key_exists('enableselling', $arguments)) || ($sfvOptions['ENABLESELLING']=="true"))
					{
						//setup price and default type if they are not set in the post
						if (!array_key_exists('price', $arguments))
						{
							$arguments['price'] = $sfvOptions['DEFAULTPRICE'];
						}
						
						if (!array_key_exists('product_type', $arguments))
						{
							$arguments['product_type'] = $sfvOptions['DEFAULTMEDIATYPE'];
						}
						
						if ($arguments['enableselling'] == "false")
						{
						
						}
						else
						{
							$output .= '<div class="product"><input type="hidden" class="product-title" value="'. get_the_title() . '-' . $arguments['product_type'] .'"><input type="hidden" class="product-price" value="' . $arguments['price'] . '"><div class="googlecart-add-button" tabindex="0" role="button" title="Add to cart"></div><strong>Media Type: ' . $arguments['product_type'] . ' Price: $' . $arguments['price'] . '</strong></div>';
						}
					
					}
				
		return $output;
		
	}
	
	
	//---------------------------------------------------------
	// Deletes the log files generated by Simple Stats
	//--------------------------------------------------------- 
	function deleteLogs()
	{
 		$logs =  dirname(__FILE__) . '/stats/logs';
 		$outputlogs = dirname(__FILE__) . '/stats/output';
 	
 		`rm -rf $logs `;
 		`rm -rf $outputlogs `;
	}
 
	//---------------------------------------------------------
	// Gets the stats that have been generated by Simple Stats
	//---------------------------------------------------------
	function getStats()
	{
 		chdir(dirname(__FILE__) . "/stats");
	
		// Include the Simple Stats functions
		require_once("functions.php");
	
		// Check for GET/POST variables
		$vars = ss_getvars();
	
		// Render the page!
		echo '<div id="ss"><div id="container"><h1>Simple Stats</h1>';
		ss_menu($vars);
		ss_content($vars);
		echo '</div></div>';
		
		chdir("..");
	}

	//---------------------------------------------------------
	// Properly Escapes characters from XML and returns them
	//--------------------------------------------------------- 
	function xmlentities($string) 
	{
	   return str_replace ( array ( '&', '"', "'", '<', '>', '�' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string );
	}
 
	//---------------------------------------------------------
	 /*
		Get a listing of directory contents on the server. Match will filter
		based on suffixes, prematch will filter based on prefixes. The filter
		text is NOT included in the resulting list. revsort will sort the
		results in reverse (e.g. highest number to lowest).
	*/
	//---------------------------------------------------------
	function get_dir_list($path, $match = "", $prematch = "", $revsort = true)
	{
		$handle = opendir($path);
		$list = array();
		
		while (false !== ($file = readdir($handle)))
		{
			if ($match != "")
			{
				if (substr($file, strlen($file) - strlen($match)) == $match)
				{
					if ($prematch != "")
					{
						if (substr($file, 0, strlen($prematch)) == $prematch)
						{
							$list[count($list)] = substr($file, strlen($prematch), strlen($file) - (strlen($match) + strlen($prematch)));
						}
					}
					else
					{
						$list[count($list)] = substr($file, 0, strlen($file) - strlen($match));
					}
				}
			}
			else
			{
				if ($prematch != "")
				{
					if (substr($file, 0, strlen($prematch)) == $prematch)
					{
						$list[count($list)] = substr($file, strlen($prematch), strlen($file) - strlen($prematch));
					}
				}
				else
				{
					$list[count($list)] = $file;
				}
			}
		}
		
		if ($revsort)
		{
			rsort($list);
		}
		else
		{
			sort($list);
		}
		
		return $list;
	}
 
	//---------------------------------------------------------
	// Gets the URL of the blog and returns it
	//--------------------------------------------------------- 
	function getURL()
	{
	 	$parts = explode("?", $_SERVER["REQUEST_URI"]);
		$request = $parts[0];
		if (substr($request, strlen($request) - 3, 3) == "php")
		{
			$parts = explode("/", $request);
			$request = implode("/", array_slice($parts, 0, sizeof($parts) - 1));
		}
		if (substr($request, strlen($request) - 1, 1) == '/')
		{
			$request = substr($request, 0, strlen($request) - 1);
		}
	}
 
	//---------------------------------------------------------
	// Prints out the Admin(Settings) page for Simple Flash Video
	//--------------------------------------------------------- 
	function printAdminPage() 
	{				
		global $plugin_url;
		$sfvOptions = getAdminOptions();
		$sfvOptions['FULL_PLUGIN_URL'] = $plugin_url;
		
		//debug
		//print_r($sfvOptions);
		
		//Things that we don't want shown in the text box for extra settings
		$config_settings = array("CONFIG" => '0',"HEIGHT"=> '0', "WIDTH"=> '0', "OVERSTRETCH"=> '0', "DEFAULTPRICE"=> '0', "DEFAULTPRODUCTTYPE"=> '0', "GOOGLEMERCHANTID"=> '0', "FULLSCREEN"=> '0', "AUTOSTART"=> '0', "BUFFERLENGTH"=> '0', "REPEAT"=> '0', "QUALITY"=> '0', "VOLUME"=> '0', "SHUFFLE"=> '0', "CONTROLBAR"=> '0', "PLAYLIST"=> '0', "PLAYLISTSIZE"=> '0', "MUTE"=> '0', "STRETCHING"=> '0', "CHANNEL"=> '0', "FULL_PLUGIN_URL"=> '0', "REMOVE_PADDING"=> '0', "STREAMER"=> '0', "BACKCOLOR"=> '0', "FRONTCOLOR"=> '0', "LIGHTCOLOR"=> '0', "SCREENCOLOR" => '0', "SKIN"=> '0', "BOTR" => '0', "BITS_THUMB_SIZE"=> '0', "DEFAULT_CLICK_TITLE"=> '0', "LONGTAILNUMBER"=> '0', "LONGTAILENABLE" => '0', "DEFAULT_VID_IMAGE" => '0', "IMAGE" => '0', "LOGO" => '0', "CAPTIONS" => '0', "DISPLAYCLICK" => '0' , "LINKTARGET" => '0', "SHARINGPLUGINLINK" => '0', "PLUGINS" => '0', 'ENABLE_META_TAGS' => '0');
		
		//print_r($config_settings);
													
		if (isset($_POST['update_SimpleFlashVideoSettings']))
		{	
			echo '<div class="updated"><p><strong>' . __('Options saved.', 'simple-flash-video') . '</strong></p></div>';
			//delete log files if set
			if ($_POST['deletelogs'] == 'true') 
			{
				echo '<div class="updated"><p><strong>' . __('Logs Deleted!', 'simple-flash-video') . '</strong></p></div>';
				deleteLogs();
			}
			//clear out old info in array
			$sfvOptions= array();
			
			//set the array back up. 
			$sfvOptions['HEIGHT'] = $_POST['vid_height'];
			$sfvOptions['WIDTH'] = $_POST['vid_width'];
			$sfvOptions['IMAGE'] = $_POST['image'];
			$sfvOptions['AUTOSTART'] = $_POST['autostart'];
			$sfvOptions['CONTROLBAR'] = $_POST['controlbar'];
			$sfvOptions['SKIN'] = $_POST['skin'];
			$sfvOptions['LOGO'] = $_POST['logo'];
			$sfvOptions['PLAYLIST'] = $_POST['playlist'];
			$sfvOptions['PLAYLISTSIZE'] = $_POST['playlistsize'];
			$sfvOptions['CAPTIONS'] = $_POST['captions'];
			$sfvOptions['DISPLAYCLICK'] = $_POST['displayclick'];
			$sfvOptions['MUTE'] = $_POST['mute'];
			$sfvOptions['QUALITY'] = $_POST['quality'];
			$sfvOptions['BACKCOLOR'] = $_POST['backcolor'];
			$sfvOptions['FRONTCOLOR'] = $_POST['frontcolor'];
			$sfvOptions['LIGHTCOLOR'] = $_POST['lightcolor'];
			$sfvOptions['SCREENCOLOR'] = $_POST['screencolor'];			
			$sfvOptions['LINKTARGET'] = $_POST['linktarget'];
			$sfvOptions['STRETCHING'] = $_POST['stretching'];
			$sfvOptions['FULLSCREEN'] = $_POST['fullscreen'];
			$sfvOptions['BUFFERLENGTH'] = $_POST['bufferlength'];
			$sfvOptions['STREAMER'] = $_POST['streamer'];
			$sfvOptions['REPEAT'] = $_POST['repeat'];
			$sfvOptions['VOLUME'] = $_POST['volume'];
			$sfvOptions['SHUFFLE'] = $_POST['shuffle'];
			$sfvOptions['CHANNEL'] = $_POST['channel'];
			$sfvOptions['PLUGINS'] = $_POST['plugins'];
			$sfvOptions['LONGTAILENABLE'] = $_POST['longtailenable'];			
			$sfvOptions['LONGTAILNUMBER'] = xmlentities($_POST['longtailnumber']);
			$sfvOptions['BITS_THUMB_SIZE'] = $_POST['bits_thumb_size'];
			$sfvOptions['BOTR'] = $_POST['botr'];
			$sfvOptions['FULL_PLUGIN_URL'] = $_POST['full_plugin_url'];
			$sfvOptions['REMOVE_PADDING'] = $_POST['remove_padding'];
			$sfvOptions['DEFAULT_VID_IMAGE'] = $_POST['default_vid_image'];
			$sfvOptions['DEFAULT_CLICK_TITLE'] = $_POST['default_click_title'];
			$sfvOptions['ENABLE_META_TAGS'] = $_POST['enable_meta_tags'];
			$sfvOptions['PLUGIN_OPTIONS'] = $_POST['plugin_options'];
			$sfvOptions['SHARINGPLUGINLINK'] = $_POST['sharingpluginlink'];
			$sfvOptions['ENABLESELLING'] = $_POST['enableselling'];
			$sfvOptions['DEFAULTPRICE'] = $_POST['defaultprice'];
			$sfvOptions['DEFAULTPRODUCTTYPE'] = $_POST['defaultproducttype'];
			$sfvOptions['GOOGLEMERCHANTID'] = $_POST['googlemerchantid'];
			
			//setup the Plugin Options so we can properly write these
			$sfvOptions['PLUGIN_OPTIONS'] = str_replace("\r\n", "&", $sfvOptions['PLUGIN_OPTIONS']);
			
			//write these options to file
			writeAdminOptions($sfvOptions);
			
			//setup the Plugin Options so we can properly display them in the options tex box
			$sfvOptions['PLUGIN_OPTIONS'] = str_replace("&", "\r\n", $sfvOptions['PLUGIN_OPTIONS']);
			
			
		}
		
		$skins = get_dir_list( dirname(__FILE__) . "/skins/", ".swf", "", false);
		
		?>
		
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<h2>Preview of Player with Settings:</h2>
		This may require you to refresh your cache to get the new config.xml
		<center>
		<a href="<?php echo $plugin_url ?>/video.php?height=430&amp;width=730&amp;file_name=http://www.simplethoughtproductions.com/wp-content/uploads/SimpleShorts/Morning_Mail/morning_mail.mp4&amp;height=430&amp;width=730&amp;streamer=&amp;" title="Morning Mail" rel="shadowbox;height=430;width=730"><img src="http://www.simplethoughtproductions.com/wp-content/uploads/SimpleShorts/Morning_Mail/morning_mail.jpg" alt="Click here To Watch Video" /><br /> <?php echo $sfvOptions['DEFAULT_CLICK_TITLE'] ?></a>
		</center>
				
		<h2>Simple Flash Video Plugin Settings from config.xml</h2>
		
		<h2>SFV Plugin Settings</h2>
		<hr>
		<h3>Remove Padding from video window.</h3>
		<p><label for="remove_padding"><input type="radio" id="remove_padding_true" name="remove_padding" value="true" <?php if ($sfvOptions['REMOVE_PADDING'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="remove_padding_false"><input type="radio" id="remove_padding_false" name="remove_padding" value="false" <?php if ($sfvOptions['REMOVE_PADDING'] == "false") { _e('checked="checked"'); }?>/> False</label></p>

		<h3>Default Image to use in link if no image is found:</h3>
		<textarea name="default_vid_image" COLS=80 ROWS=3><?php echo $sfvOptions['DEFAULT_VID_IMAGE']?></textarea>
		
		<h3>Default Title for Text under image:</h3>
		<textarea name="default_click_title" COLS=80 ROWS=3><?php echo $sfvOptions['DEFAULT_CLICK_TITLE']?></textarea>
		
		<h3>Enable Meta Data</h3>
		If this is enabled the plugin will add 3 bits of meta data to each post pertaining to the Video title, Shadowbox width for video and the arguments soa  theme can use the information
		<p><label for="enable_meta_tags"><input type="radio" id="enable_meta_tags_true" name="enable_meta_tags" value="true" <?php if ($sfvOptions['ENABLE_META_TAGS'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="enable_meta_tags_false"><input type="radio" id="enable_meta_tags" name="enable_meta_tags" value="false" <?php if ($sfvOptions['ENABLE_META_TAGS'] == "false") { _e('checked="checked"'); }?>/> False</label></p>

		<h3>Full Plugin URL</h3>
		<textarea name="full_plugin_url" COLS=80 ROWS=3><?php echo $sfvOptions['FULL_PLUGIN_URL']?></textarea>
		<hr>
		
		<h3>Player Settings</h3>
		<hr>
		<h3>Width of video:</h3>	
		<textarea name="vid_width" COLS=10 ROWS=1><?php echo $sfvOptions['WIDTH']?></textarea>
		<h3>Height of video:</h3>
		<textarea name="vid_height" COLS=10 ROWS=1"><?php echo $sfvOptions['HEIGHT']?></textarea>
		<h3>Skin of player:</h3>	
		<select name="skin">
				
		<?php
		foreach ($skins as $value)
		{
			echo '<option value="' . $plugin_url . '/skins/' . $value . '.swf" '; 
			
			if ($sfvOptions['SKIN'] == $plugin_url . '/skins/' . $value . '.swf' ) 
			{
				echo 'SELECTED >' . $value .'</option>';
			}
			
			else
			{
				echo '>' . $value .'</option>';
			}
		}
		
		echo '<option value=""';
		if ($sfvOptions['SKIN'] == '')
		{
			echo 'SELECTED >No Skin</option>';
		}
				
		else
		{
			echo '>No Skin</option>';
		}
		
		$sfvOptions['FULL_PLUGIN_URL'] = $plugin_url;
				
		?>
		
		</select>
		<h3>Backcolor of player:</h3>	
		<textarea name="backcolor" COLS=10 ROWS=1><?php echo $sfvOptions['BACKCOLOR']?></textarea>
		<h3>Frontcolor of player:</h3>	
		<textarea name="frontcolor" COLS=10 ROWS=1><?php echo $sfvOptions['FRONTCOLOR']?></textarea>
		<h3>Lightcolor of player:</h3>	
		<textarea name="lightcolor" COLS=10 ROWS=1><?php echo $sfvOptions['LIGHTCOLOR']?></textarea>
		<h3>Screencolor of player:</h3>	
		<textarea name="screencolor" COLS=10 ROWS=1><?php echo $sfvOptions['SCREENCOLOR']?></textarea>

		
		<h3>Controlbar Location:</h3>
		<p><label for="controlbar_bottom"><input type="radio" id="controlbar_bottom" name="controlbar" value="bottom" <?php if ($sfvOptions['CONTROLBAR'] == "bottom") { _e('checked="checked"'); }?>/> Bottom of video</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="controlbar_over"><input type="radio" id="controlbar_over" name="controlbar" value="over" <?php if ($sfvOptions['CONTROLBAR'] == "over") { _e('checked="checked"'); }?>/> Over the Video</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="controlbar_none"><input type="radio" id="controlbar_none" name="controlbar" value="none" <?php if ($sfvOptions['CONTROLBAR'] == "none") { _e('checked="checked"'); }?>/> Do not display Controlbar</label>&nbsp;&nbsp;&nbsp;&nbsp;</p>
		<h3>Playlist Location:</h3>
		<p><label for="playlist_bottom"><input type="radio" id="playlist_bottom" name="playlist" value="bottom" <?php if ($sfvOptions['PLAYLIST'] == "bottom") { _e('checked="checked"'); }?>/> Bottom of video</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="playlist_over"><input type="radio" id="playlist_over" name="playlist" value="over" <?php if ($sfvOptions['PLAYLIST'] == "over") { _e('checked="checked"'); }?>/> Over the Video</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="playlist_right"><input type="radio" id="playlist_right" name="playlist" value="right" <?php if ($sfvOptions['PLAYLIST'] == "right") { _e('checked="checked"'); }?>/> Right of the Video</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="playlist_none"><input type="radio" id="playlist_none" name="playlist" value="none" <?php if ($sfvOptions['PLAYLIST'] == "none") { _e('checked="checked"'); }?>/> Do not display Playlist</label></p>
		<h3>Stretching</h3>
		<p><label for="stretching_true"><input type="radio" id="stretching_none" name="stretching" value="none" <?php if ($sfvOptions['STRETCHING'] == "none") { _e('checked="checked"'); }?>/> None</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="stretching_exactfit"><input type="radio" id="stretching_exactfit" name="stretching" value="exactfit" <?php if ($sfvOptions['STRETCHING'] == "exactfit") { _e('checked="checked"'); }?>/> exactfit</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="stretching_uniform"><input type="radio" id="stretching_uniform" name="stretching" value="uniform" <?php if ($sfvOptions['STRETCHING'] == "uniform") { _e('checked="checked"'); }?>/> Uniform</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="stretching_fill"><input type="radio" id="stretching_fill" name="stretching" value="fill" <?php if ($sfvOptions['STRETCHING'] == "fill") { _e('checked="checked"'); }?>/> fill</label></p>
		<h3>Allow Fullscreen</h3>
		<p><label for="fullscreen_true"><input type="radio" id="fullscreen_true" name="fullscreen" value="true" <?php if ($sfvOptions['FULLSCREEN'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="fullscreen_false"><input type="radio" id="fullscreen_false" name="fullscreen" value="false" <?php if ($sfvOptions['FULLSCREEN'] == "false") { _e('checked="checked"'); }?>/> False</label></p>
				
		<h3>Quality</h3>
		<p><label for="quality_true"><input type="radio" id="quality_true" name="quality" value="true" <?php if ($sfvOptions['QUALITY'] == "true") { _e('checked="checked"'); }?>/> True(Helps videos look better but uses more CPU)</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="quality_false"><input type="radio" id="quality_false" name="quality" value="false" <?php if ($sfvOptions['QUALITY'] == "false") { _e('checked="checked"'); }?>/> False(Quality will drop unless you have a high bitrate video bug it will use less CPU)</label></p>
			
		<h3>Default Volume:</h3>
		<textarea name="volume" COLS=10 ROWS=1><?php echo $sfvOptions['VOLUME']?></textarea>
				
		<h3>Mute by Default</h3>
		<p><label for="mute_true"><input type="radio" id="mute_true" name="mute" value="true" <?php if ($sfvOptions['MUTE'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="mute_false"><input type="radio" id="mute_false" name="mute" value="false" <?php if ($sfvOptions['MUTE'] == "false") { _e('checked="checked"'); }?>/> False</label></p>
				
		<h3>Size of Playlist in Pixels:</h3>	
		<textarea name="playlistsize" COLS=10 ROWS=1><?php echo $sfvOptions['PLAYLISTSIZE']?></textarea>
				
		<h3>Autoplay Video:</h3>
		<p><label for="autostart"><input type="radio" id="autostart_true" name="autostart" value="true" <?php if ($sfvOptions['AUTOSTART'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="autostart_false"><input type="radio" id="autostart_false" name="autostart" value="false" <?php if ($sfvOptions['AUTOSTART'] == "false") { _e('checked="checked"'); }?>/> False</label></p>
				
		<h3>Repeat Playback</h3>
		<p><label for="repeat_true"><input type="radio" id="repeat_true" name="repeat" value="true" <?php if ($sfvOptions['REPEAT'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="repeat_false"><input type="radio" id="repeat_false" name="repeat" value="false" <?php if ($sfvOptions['REPEAT'] == "false") { _e('checked="checked"'); }?>/> False</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="repeat_list"><input type="radio" id="repeat_list" name="repeat" value="list" <?php if ($sfvOptions['REPEAT'] == "list") { _e('checked="checked"'); }?>/> List</label></p>
				
		<h3>Shuffle on playlists</h3>
		<p><label for="shuffle_true"><input type="radio" id="shuffle_true" name="shuffle" value="true" <?php if ($sfvOptions['SHUFFLE'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="shuffle_false"><input type="radio" id="shuffle_false" name="shuffle" value="false" <?php if ($sfvOptions['SHUFFLE'] == "false") { _e('checked="checked"'); }?> /> False</label></p>
			
		<h3>URL of Logo to place at top right corner of all videos:</h3>	
		<textarea name="logo" COLS=80 ROWS=3><?php echo $sfvOptions['LOGO']?></textarea>
				
		<h3>Link to Image to display before any video starts:</h3>
		<textarea name="image" COLS=80 ROWS=3><?php echo $sfvOptions['IMAGE']?></textarea>
			
		<h3>Buffer Length (Nuber of Seconds)</h3>
		<textarea name="bufferlength" COLS=10 ROWS=1><?php echo $sfvOptions['BUFFERLENGTH']?></textarea>				
				
		<h3>Streamer:</h3>
		<textarea name="streamer" COLS=80 ROWS=3><?php echo $sfvOptions['STREAMER']?></textarea>	
				
		<hr>
		
		<h2>Enable Selling</h2>
		<p><label for="enableselling"><input type="radio" id="enableselling_true" name="enableselling" value="true" <?php if ($sfvOptions['ENABLESELLING'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="enableselling_false"><input type="radio" id="enableselling" name="enableselling" value="false" <?php if ($sfvOptions['ENABLESELLING'] == "false") { _e('checked="checked"'); }?>/> False</label></p>
		
		<h2>Google Checkout Shoping Cart:</h2>
		To Use Google Checkout Shoping Cart you must have a Google Checkout account.<br />
		<h3>Shoping Cart code</h3>
		Replace $MERCHANT_ID with your merchant ID and then put this code into a Text Widget or somewhere on your page template.
		<textarea name="checkoutcode" COLS=80 ROWS=3><script  id='googlecart-script' type='text/javascript' src='https://checkout.google.com/seller/gsc/v2_2/cart.js?mid=$MERCHANT_ID' integration='jscart-wizard' post-cart-to-sandbox='false' currency='USD' productWeightUnits='LB'></script></textarea> 
		
		<h3>Default Product Type:</h3>
		<textarea name="defaultproducttype" COLS=80 ROWS=3><?php echo $sfvOptions['DEFAULTPRODUCTTYPE']?></textarea>
		
		<h3>Default Price:</h3>
		<textarea name="defaultprice" COLS=80 ROWS=3><?php echo $sfvOptions['DEFAULTPRICE']?></textarea>
		
		<hr>
		
		<h2>Plugins:</h2>
		<h3>Enable Sharing Plugin (Link only)</h3>
		<p><label for="sharingpluginlink_true"><input type="radio" id="sharingpluginlink_true" name="sharingpluginlink" value="true" <?php if ($sfvOptions['SHARINGPLUGINLINK'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="sharingpluginlink_false"><input type="radio" id="sharingpluginlink_false" name="sharingpluginlink" value="false" <?php if ($sfvOptions['SHARINGPLUGINLINK'] == "false") { _e('checked="checked"'); }?>/> False</label></p>
		
		<h3>Plugin Names</h2>
		Add all plugins that you wish to be run on all videos seperated by a comma<br />
		<textarea name="plugins" COLS=80 ROWS=3><?php echo $sfvOptions['PLUGINS']?></textarea>
		
		<h3>Plugin Options</h3>
		Enter each option on a new line<br />
		<textarea name="plugin_options" COLS=80 ROWS=10><?php 
		$plug = "";
		foreach ($sfvOptions as $key => $value)
		{
			//echo $key . "\r\n";
			//print_r(array_values($config_settings));
						
			if (!array_key_exists($key, $config_settings))
			{	
				if ($key == "PLUGIN_OPTIONS")
				{
					$plug .=  $value;
				}
				
				else
				{
					$plug .= $key . "=" . $value . "\r\n";
				}
			}
			
		}
		echo $plug;
		
		?></textarea>
		<hr>
		
		<h2>Bits On the Run.com Support:</h2>
		<hr>
		<h3>Default Thumbnail Width (in px):</h3>
		<textarea name="bits_thumb_size" COLS=10 ROWS=1><?php echo $sfvOptions['BITS_THUMB_SIZE']?></textarea>	
		
		<h3>Default videos to be powered by bits on the run (if most of your vidoes are hosted by bits turn this on)</h3>
		<p><label for="botr_true"><input type="radio" id="botr_true" name="botr" value="true" <?php if ($sfvOptions['BOTR'] == "true") { _e('checked="checked"'); }?>/> True</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="botr_false"><input type="radio" id="botr_false" name="botr" value="false" <?php if ($sfvOptions['BOTR'] == "false") { _e('checked="checked"'); }?> /> False</label></p>
		<hr>
		
		<h2>Longtail Ad Solutions:</h2>
		<hr>
				
		<h3>Channel Number to use:</h3>
		<textarea name="channel" COLS=10 ROWS=1><?php echo $sfvOptions['CHANNEL']?></textarea>				
				
		<h3>Longtail Number:</h3>
		<textarea name="longtailnumber" COLS=40 ROWS=1><?php echo $sfvOptions['LONGTAILNUMBER']?></textarea>	
				
		<h2>Simple Flash Video Stats: </h2>
		<hr>
		<h3>Delete Simple Stats Logs:</h3>
		<p><label for="deletelogs"><input type="radio" id="deletelogs_true" name="deletelogs" value="true" <?php if ($deletelogs == 'true' ) { _e('checked="checked"'); }?>/> Check and Save Options to Delete Stats Logs for SFV</label>&nbsp;&nbsp;&nbsp;&nbsp;
				
		<div class="submit">
		<input type="submit" name="update_SimpleFlashVideoSettings" value="Save Changes Yo!" /></div>
		</form>
 		</div>

 				
		<?php
		
	} // End function printAdminPage()
			
				
	//---------------------------------------------------------
	//Find the Sections of the post that need to be replaced
	// *Flash Video Player Code*
	//--------------------------------------------------------- 
	function FlashVideo_Parse($content) 
	{
		$content = preg_replace_callback("/\[video ([^]]*)\/\]/i", "FlashVideo_Render", $content);
		return $content;
	}
 	
 	//---------------------------------------------------------
	// This method takes a string of arguments as input and parses them into a
    // mapped array, allowing for the use of quotes to specify values with spaces
    // Note: Values which do not follow the X=Y formula are silently ignored!
	//---------------------------------------------------------
	function splitargs($argument_string)
	{
    preg_match_all('/(?:[^ =]+?)=(?:["\'].+?["\']|[^ ]+)/', $argument_string, $items);

    $args = array();

    foreach ($items[0] as $item)
    {
        $parts = explode("=", $item);
        $name = $parts[0];
        $value = implode("=", array_slice($parts, 1));
        $args[$name] = trim($value, "\"'");
    }

    return $args;
	}



	//---------------------------------------------------------
	//Replace all the post entries with videos
	//--------------------------------------------------------- 
	function FlashVideo_Render($matches) 
	{
		//Variable Love
		global $plugin_url;
		global $videoid;
		global $arguments;
		global $post;
		//global $wp_query;
		//$thePostID = $wp_query->post->ID;
		
		//settings that we do not want passed through to the video.php
		$plugin_settings = array("shadowbox");
		
		//Get Setings from the config
	   	$sfvOptions = getAdminOptions();
	   	
	   	//set stuff to nothing
		$output = '';
		$thumbexists = false;
		$setathumb = false;
		
		//Lets go!		
		//More Voodoo - *Flash Video Player Code*
		//$matches[1] = str_replace(array('&#8221;','&#8243;'), '', $matches[1]);
		preg_match_all('/([\.\w]*)=(.*?) /i', $matches[1], $attributes);
		$arguments = array();

		//Put all of the arguments into an array
		/*foreach ( (array) $attributes[1] as $key => $value ) 
		{
			// Strip out legacy quotes
			$arguments[$value] = str_replace('"', '', $attributes[2][$key]);
		}
		*/
		
		$arguments = splitargs($matches[1]);
		
		//If no file name is provided put an error in the post *Flash Video Player Code*
		if ( !array_key_exists('filename', $arguments) )
		{
			return '<div style="background-color:#f99; padding:10px;">Error: Required parameter "filename" is missing!</div>';
			exit;
		}
				
		//setup for an RSS Feed
		$parts = pathinfo($arguments['filename']);
		if ($parts["extension"] == 'flv')
		{
			$rss_output = '<a href="' . htmlspecialchars(get_permalink()) . '">' . get_the_title() . '</a>';
		}
			
		else 
		{
			$rss_output = '<a href="' . htmlspecialchars($arguments['filename']) . '">'.get_the_title() . '</a>' ;
		}
				
				
		//Get the file name of the picture to use if it exists
		$trimmed = trim($arguments['filename'], "/");
		$parts = pathinfo($trimmed);		
		$thumb = $parts["dirname"] . "/" . substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])) . "jpg";
		
		//Do we want to show a picture?
		if (substr($thumb,0,4) == "http")
		{
			if (substr($thumb,0,18) == "http://www.youtube")
			{
				$yturl = (substr($thumb,31,-3));
				$thumb = 'http://i.ytimg.com/vi/' . $yturl . '/default.jpg'; 
			}
			
			if (substr($thumb,0,14) == "http://blip.tv")
			{
				//Check PHP Version to see if we can use blip. Code used from php.net - http://us.php.net/manual/en/function.phpversion.php
				// PHP_VERSION_ID is available as of PHP 5.2.7, if our 
				// version is lower than that, then emulate it

				if(!defined('PHP_VERSION_ID'))
				{
					$version = PHP_VERSION;

					define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
				}

				if(PHP_VERSION_ID < 50000)
				{
					return '<div style="background-color:#f99; padding:10px;">Error: PHP version 5 or greater is required to use BLIP.TV</div>';
					exit;
				}
			
				else
				{
					$xml = simplexml_load_file($arguments['filename']);
			
					//Get thumbnail URL
					$thumb_blip = $xml->xpath('/rss/channel/item/media:thumbnail/@url');
				
					//Get M4V URL
					$video_blip = $xml->xpath("/rss/channel/item/enclosure/@url");
				
			
					$thumb = $thumb_blip[0];
				
					//set filename to a valid URL
					$arguments['filename'] = (string)$video_blip[0];
									 
				}
			}
		
			if ((substr($thumb,0,31) == "http://content.bitsontherun.com") || ($arguments['botr'] == "true"))
			{
				$hash = implode("-", array_slice(explode("-", basename($thumb)), 0, -2));
				
				$thumb =  'http://content.bitsontherun.com/thumbs/' . $hash . '-'. $sfvOptions['BITS_THUMB_SIZE'] .'.jpg'; 
				
				$sfvOptions = getBOTRoptions($arguments['filename']);
				//setup the variables to use stuff from bits
				if(!array_key_exists('height', $arguments))
				{
					$arguments['height'] = $sfvOptions['HEIGHT'];
					$arguments['width'] = $sfvOptions['WIDTH'];
				}
				
				if(!array_key_exists("title", $arguments))
				{
					$arguments['title'] = $sfvOptions['TITLE'];
				}
			
				if (!array_key_exists("description", $arguments))
				{
					$arguments['description'] = $sfvOptions['DESCRIPTION'];
				}
			}
			
			if (($sfvOptions['BOTR'] == "true") && ($arguments['botr'] != "false"))
			{
				$hash = implode("-", array_slice(explode("-", basename($thumb)), 0, -2));
				
				$thumb =  'http://content.bitsontherun.com/thumbs/' . $hash . '-'. $sfvOptions['BITS_THUMB_SIZE'] .'.jpg'; 
				$sfvOptions = getBOTRoptions($arguments['filename']);
				//setup the variables to use stuff from bits
				if(!array_key_exists('height', $arguments))
				{
					$arguments['height'] = $sfvOptions['HEIGHT'];
					$arguments['width'] = $sfvOptions['WIDTH'];
				}
				
				if(!array_key_exists("title", $arguments))
				{
					$arguments['title'] = $sfvOptions['TITLE'];
				}
			
				if (!array_key_exists("description", $arguments))
				{
					$arguments['description'] = $sfvOptions['DESCRIPTION'];
				}
			}
			
			
						
			$thumbexists = true;
		}
					
		else 				
		{
			if (@fclose(@fopen($thumb, "r"))) 
			{
				$thumbexists = true;
			} 
			
			else 
			{
				$thumbexists = false;
			} 
			
			$thumb = '/' . $thumb;
			 
			
		}
		
		

		//Setup the output as all of them start the same
		$output .=  '<a href="' . htmlspecialchars( $plugin_url . '/video.php?');
		
		//See if theUser turned off shadowbox. If so just post the video
		if ($arguments['shadowbox'] == 'off') 
		{
			$output = swfoCode($arguments, $thumb);
				
			//next video please
			$videoid += 1;
		}
			
			//Shadowbox is on!
			if (!array_key_exists('shadowbox', $arguments) )
			{		
						
				//Set padding for the width to allow Shadowbox to be large enough in all browsers if it is turned on
						if($sfvOptions['REMOVE_PADDING'] == 'false')
				 	 	{
				 	 		$sfvOptions['HEIGHT'] += 70;
		  			 		$sfvOptions['WIDTH'] += 90;
		  			 		
		  			 		if(array_key_exists('height', $arguments))
		  			 		{
		  			 			$arguments['height'] += 70;
		  			 			$arguemtns['width'] += 90;
		  			 		}
				 	 	}
						
						//If the user gave a custom height and width user it. If not, use the defaults
						if(array_key_exists('height', $arguments))
						{
							$output .=  htmlspecialchars('height=' . $arguments['height'] . '&width=' . $arguments['width']);
						}
						
						
						//Lets see if there is a thumb and if there is use it. If the user sepecified a thumb then use that.
						if (array_key_exists('vid_image', $arguments))
						{
							$output .= htmlspecialchars('&vid_image=' . $arguments['vid_image']);
							$setathumb = true;
							if ( $sfvOptions['ENABLE_META_TAGS'] == 'true')
							{
								update_post_meta($post->ID, 'sfv_thumb', $arguments['vid_image']);
							}
						}
						
						elseif(array_key_exists('image', $arguments))
						{
							$output .= htmlspecialchars('&vid_image=' . $arguments['image']);
							if ($sfvOptions['ENABLE_META_TAGS'] == 'true')
							{
								update_post_meta($post->ID, 'sfv_thumb', $arguments['image']);
							}
							$setathumb = true;
						}
						
						elseif($thumbexists == true)
						{
							$output .= htmlspecialchars('&vid_image=' . $thumb);
							if ($sfvOptions['ENABLE_META_TAGS'] == 'true')
							{
								update_post_meta($post->ID, 'sfv_thumb', $thumb);
							}
							$setathumb = true;
						}
						
												
						/*
						lets setup a title and description that wont upset Longtail. First check to see if it exists
						If not create one that is equal to the post title. Then repalce the &nbsp; with %20 so it can
						be passed as an argument via PHP and then it will be cleaned up on the other side.
						*/
						if (!array_key_exists('title', $arguments))
						{
							 $arguments['title'] = get_the_title();
						}
						
						if (!array_key_exists('description', $arguments))
						{
							 $arguments['description'] = get_the_title();
						}
						
						//Clean up the Title and Description
						$arguments['title'] = str_replace("&nbsp;", "%20", $arguments['title']);
						$arguments['description'] = str_replace("&nbsp;", "%20", $arguments['description']);
						
						/*
						Now we need to loop through each variable that we set. Some get special treatment.
						If the argument is in the variable of $plugin_settings it is not passed as it is only
						for SFV and not for the JW Player
						*/
						
						
						//If sharing is turned on add the link to the page of the video.
						if ($sfvOptions['SHARINGPLUGINLINK'] == 'true')
						{
							//Put sharing back in!
							$arguments['plugins'] .= ',sharing';
							
							if (!array_key_exists('sharing.link', $arguments))
							{
								$arguments['sharing.link'] = get_permalink();	
							}
						}
						
						//Serialize the array of parameters so we can pass it to the video.php file
						$parameters = urlencode(serialize($arguments));
						
						$output .= '&arguments=' . $parameters;
						
						//setup the post meta info incase a theme wants to use it (like the STP website)
						if ($sfvOptions['ENABLE_META_TAGS'] == 'true')
						{
							$videolink = $plugin_url . "/video.php?arguments=" . $parameters;
							update_post_meta($post->ID, 'sfv_video', $videolink, true);
						}
						
												
						/*
						Now that all variables are passed we need to setup the rest of the Shadowbox command
						Shadowbox needs the &nbsp; back as it does not understand the %20
						*/
						if (array_key_exists('title', $arguments))
						{
							//lets setup a title that wont upset Shadowbox
							$arguments['title'] = str_replace("%20", "&nbsp;", $arguments['title']);
							$output .= '" title="' . $arguments['title'] . '" rel="shadowbox;';
						}
						
						//Set the Height and width of shadowbox based on the default or provided numbers
						if(array_key_exists('height', $arguments))
						{
							$output .= 'height=' . $arguments['height'] . ';width=' . $arguments['width']  . '">';
							//setup shadowboxinfo for the meta info
							$shadowboxinfo = 'shadowbox;height='. $arguments['height'] . ';width=' . $arguments['width'];
							
						}
						
						else
						{
							$output .= 'height=' . $sfvOptions['HEIGHT'] . ';width=' . $sfvOptions['WIDTH']  . '">';
							//setup shadowboxinfo for the meta info
							$shadowboxinfo = 'shadowbox;height='. $sfvOptions['HEIGHT'] . ';width=' . $sfvOptions['WIDTH'];
						}
						
						//set value of shadowbox and post to meta and title
						if ( $sfvOptions['ENABLE_META_TAGS'] == 'true')
						{
							update_post_meta($post->ID, 'sfv_shadowbox', $shadowboxinfo);
							update_post_meta($post->ID, 'sfv_title', get_the_title());
						}
						
						
						//If a Thumbnail or Video image was used 
						if ($setathumb == true)
						{
							$output .=  '<img src="';
							if (array_key_exists('vid_image', $arguments))
							{
								$output .= $arguments['vid_image']; 
								$setathumb = true;
							}
						
							elseif(array_key_exists('image', $arguments))
							{
								$output .= $arguments['image'];
								$setathumb = true;
							}
						
							elseif($thumbexists == true)
							{
								$output .= $thumb;
								$setathumb = true;
							}
							
							$output .=  '"' . 'alt="' . htmlspecialchars('Click here To Watch Video') . '"/>';
 						}
 						
 					 // End the Entry with the click to watch variable
 					if (array_key_exists('click_title', $arguments))
					{
						$output .= '<br /> '. $arguments['click_title'] . '</a>' . "\n";
					}
					else
					{
						$output .= '<br /> '. $sfvOptions['DEFAULT_CLICK_TITLE'] . '</a>' . "\n";
					}
					
					// Lets make some money! Google Checkout Integration
					if ((array_key_exists('price', $arguments)) || ($sfvOptions['ENABLESELLING']=="true"))
					{
						//setup price and default type if they are not set in the post
						if (!array_key_exists('price', $arguments))
						{
							$arguments['price'] = $sfvOptions['DEFAULTPRICE'];
						}
						
						if (!array_key_exists('product_type', $arguments))
						{
							$arguments['product_type'] = $sfvOptions['DEFAULTPRODUCTTYPE'];
						}
						
						if ($arguments['enableselling'] == "false")
						{
							
						}
						else
						{
							$output .= '<div class="product"><input type="hidden" class="product-title" value="'. $arguments['title'] . '-' . $arguments['product_type'] .'"><input type="hidden" class="product-price" value="' . $arguments['price'] . '"><div class="googlecart-add-button" tabindex="0" role="button" title="Add to cart"></div><strong>Media Type: ' . $arguments['product_type'] . ' Price: $' . $arguments['price'] . '</strong></div>';
						}
					
					}
			
			}
	
		//Return the final link to be used	
		if(is_feed()) 
		{
			return $rss_output;	
		}
				
		else
		{
			return $output;
		}

	} // end FlashVideo_Render Function

    //---------------------------------------------------------
    //Admin Header
    //--------------------------------------------------------- 
    function sfv_admin_header()
    {
    	global $plugin_url;
    	
    	echo '<link rel="stylesheet" type="text/css" media="screen" href="' . $plugin_url . '/stats/simplestats.css"/>';	
    }

} // End if from line 35

if (class_exists("SimpleFlashVideoPlugin")) 
{
	$sfv_plugin = new SimpleFlashVideoPlugin();
}
	
	
//Initialize the admin panel
if (!function_exists("SimpleFlashVideoPlugin_ap")) 
{
	function SimpleFlashVideoPlugin_ap() 
	{
		global $sfv_plugin;
		if (!isset($sfv_plugin)) 
		{
			return;
	 	}
	
	if (function_exists('add_options_page')) 
	 {
	 	add_submenu_page('index.php', __('SFV Stats'), __('SFV Stats'), 9 , 'sfvstats', 'getStats');
		add_options_page('Simple Flash Video Plugin', 'Simple Flash Video Plugin', 9, basename(__FILE__),'printAdminPage');	
	 }
	
	}   
}

//Actions and Filters   
if (isset($sfv_plugin))
 {
	//Actions
	 add_action('admin_menu', 'SimpleFlashVideoPlugin_ap');
	 add_action('activate_simple-flash-video/simple-flash-video.php',  array(&$sfv_plugin, 'init'));
	 add_action('wp_head', array(&$sfv_plugin, 'addHeaderCode'), 1);
	 add_action('admin_head', array(&$sfv_plugin, 'addHeaderCode'), 1);
	 add_action( 'wp_footer',  array(&$sfv_plugin, 'addFooterCode'), 1);
	 add_action("admin_head", "sfv_admin_header");
	 register_activation_hook( __FILE__, 'init' );

	   
	//Filters
	add_filter('the_content', 'FlashVideo_Parse');
	
 }
 
?>
