<?php

/*
    Simple Flash Video Player
    =========================
    Display a video.
    
    License
    -------
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
    along with Simple Flash Video.  If not, see <http://www.gnu.org/licenses/>.
    
*/

//Turn off Error Reporint
error_reporting(0); 

// Get the Arguments passed by plugin or by the user. This allows the new seralized links and the old ones. 
$arguments = array();

if (array_key_exists("arguments", $_GET))
{
	$arguments = unserialize(stripslashes($_GET['arguments']));
}

else
{	
	//Get everything from $_GET then make sure the key parts are in correctly where we want them
	//Support some old embeds that used file_name
	$arguments = $_GET;
	$keys = array_keys( $arguments );
	$values = array_values( $arguments );
	$replace = array_search( 'file_name', $keys );
	if ( $replace !== FALSE ) 
	{
  		$keys[ $replace ] = 'filename';
	}	
	$arguments = array();
	foreach ($keys as $key1 => $value1) 
	{
	    $arguments[$value1] = $values[$key1];
	}
	
}

$sfvOptions = array();

// Open the Config.xml file and read in the default Height and Width
$config_file_xml = dirname(__FILE__) . "/config.xml";
$xml = file_get_contents($config_file_xml) or die("Can't open remote files!");
$cur_tag = "";

function start_el_handler($parser,$name,$attribs)
{
    global $cur_tag;
    $cur_tag = $name;
}

function end_el_handler($parser,$name)
{

}

 // XML Entity Mandatory Escape Characters
function xmlentities($string) 
{
   return str_replace ( array ( '&', '"', "'", '<', '>', '�' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string );
}

function char_handler($parser,$data)
{
    global $cur_tag, $sfvOptions;
    $sfvOptions[$cur_tag] .= trim($data);
}

//Used to get the config from a bits on the run file 
function getBOTROptions($filename)
{
	unset($GLOBALS['sfvOptions']);
		
	$parts = pathinfo($filename);		
	$config_file_xml = 'http://content.bitsontherun.com/xml' . "/" . substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])) . "xml";
		
 		
   	//Read in the Config XML to get the Default Height and Width of the Video
 	$xml = file_get_contents($config_file_xml) or die("Can't open remote files!");
			
	$parser = xml_parser_create();
	xml_set_element_handler($parser, "start_el_handler", 'end_el_handler');
	xml_set_character_data_handler($parser, "char_handler");
	xml_parse($parser,$xml);
	
}


	
$parser = xml_parser_create();
xml_set_element_handler($parser, "start_el_handler", 'end_el_handler');
xml_set_character_data_handler($parser, "char_handler");
xml_parse($parser,$xml);

echo $sfvOptions['height'];

// settings that we do not want passed through to the video.php
$plugin_settings = array("file_name", "filename", "height", "width", "random", "embed", "longtail", "full_plugin_url", "plugin_url");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Simple Flash Video Player</title>
    <style type="text/css">
	<?php if ($sfvOptions['REMOVE_PADDING'] == 'false') echo '.flashvideo { margin: 35px auto; text-align: center; }'?>
	
	body { margin: 0; }
       
	</style>
</head>

<body style="background-color: #333;">



<?php
 //debug
 //print_r(unserialize(stripslashes($_GET['arguments'])));
 //print_r($sfvOptions);


// Write the video player using the provided and the config.xml variables using SWFObject
	$videoid = '1';
	
	$output ='<div id="mediaspace" class="flashvideo"><a href="http://www.adobe.com/go/getflashplayer"><img src="' . $sfvOptions['FULL_PLUGIN_URL'] . '/upgrade.png" alt="Upgrade Flash to watch video" /></a></div>';
	$output .= '<script type="text/javascript" src="' . dirname($_SERVER["PHP_SELF"]) . '/swfobject.js"></script>' . "\n";
	$output .= '<script type="text/javascript" src="' . dirname($_SERVER["PHP_SELF"]) . '/stats/simplestats.js"></script>' . "\n";
	$output .= "\n" . '<span id="sfvcontainer' . $videoid . '" class="flashvideo"/>' . "\n";
	$output .= '<script type="text/javascript">' . "\n";
	$output .= 'var s' . $videoid . ' = new SWFObject("';
	
	//BITS ON THE RUN SUPPORT
		if ((substr($arguments['filename'],0,31) == "http://content.bitsontherun.com") || (($sfvOptions['BOTR'] == "true") && (!isset($arguments['botr']))) || ($arguments['botr'] == "true"))
		{
			
			$output .= $arguments['filename'] . '","sfvideo' . $videoid . '","';
			
			//since it is BOTR we need to load up the config that is for the player that you requested
			 getBOTRoptions($arguments['filename']);
			
			//setup the arguments to use options from the bits config if they are not set by the user. 
			if(!array_key_exists("title", $arguments))
			{
				$arguments['title'] = $sfvOptions['TITLE'];
			}
			
			if (!array_key_exists("description", $arguments))
			{
				$arguments['description'] = $sfvOptions['DESCRIPTION'];
			}
			
			if (!array_key_exists('height', $arguments))
			{
				$arguments['height'] = "";
				$arguments['width'] = "";
				$arguments['height'] = $sfvOptions['HEIGHT'];
				$arguments['width'] = $sfvOptions['WIDTH'];
			}
				 
			 
		}
		else	
		{
			$output .= dirname($_SERVER["PHP_SELF"]) . '/mediaplayer.swf' . '","sfvideo' . $videoid . '","'; 
		}
		
	 
	
	//did the user give a new width and height if so use it. If not use the one from config
	if (array_key_exists("width", $arguments))
	{
		if($sfvOptions['REMOVE_PADDING'] == 'false')
		{
			$arguments['width'] -= 90;
		}
		
		$output .= $arguments['width']; 
	}
	else
	{
		 $output .= $sfvOptions['WIDTH'];
	}

	if (array_key_exists("height", $arguments) )
	{
		if($sfvOptions['REMOVE_PADDING'] == 'false')
		{
			$arguments['height'] -= 70;
		}
		
		$output .= '","' . $arguments['height'];
	}
	else
	{
		 $output .= '","' .  $sfvOptions['HEIGHT']; 
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
			$output .= 's' . $videoid . '.addVariable("config", "' . $configfile . '/config.xml");' . "\n";
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
	
	
	if ($arguments['height'] != "")
	{ 
		$output .= 's' . $videoid . '.addVariable("height", "' . $arguments['height'] . '");' . "\n";
	}
	
	if ($arguments['width'] != "")
	{
		$output .= 's' . $videoid . '.addVariable("width", "' . $arguments['width'] . '");' . "\n";
	}
	
	foreach($arguments as $name => $value)
	{
		if (array_search($name, $plugin_settings) === false)
		{
			if ($name == "vid_image") 
			{
				
			//echo "<p>" . "image" . ": " . $value . "</p>";
	    	$output .= 's' . $videoid . '.addVariable("image", "' . $value . '");' . "\n";

			}
			
			
			else
			{
				
				//$value = str_replace("%20;", " ", $value);
				//echo "<p>" . $name . ": " . $value . "</p>";
	    		$output .= 's' . $videoid . '.addVariable("'. $name . '", "' . $value . '");' . "\n";
			}
		}
	}

	$output .= 's' . $videoid . '.write("mediaspace");' . "\n";

	$output .= '</script>' . "\n";


echo $output;

echo '</div>';

if (isset($arguments['longtailenable']))
{
	if ($arguments['longtailenable'])
	{
		echo '<script type="text/javascript" src="http://www.ltassrv.com/serve/api5.4.asp?' . xmlentities($sfvOptions['LONGTAILNUMBER']) . '"></script>';
	}
}

elseif (($sfvOptions['LONGTAILENABLE'] == 'true') && ($arguments['longtailenable'] == 'false'))
{
    	echo '<script type="text/javascript" src="http://www.ltassrv.com/serve/api5.4.asp?' . xmlentities($sfvOptions['LONGTAILNUMBER']) . '"></script>';
    	
}
?>




</body>
 
        	
</html>
