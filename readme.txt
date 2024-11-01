=== Simple Flash Video ===
Contributors: SimpleThought
Link: http://www.simplethoughtproductions.com/sfv-plugin/
Tested up to: 2.8
Requires at least: 2.3.1
Stable tag: 1.7
Tags: JW, FLV, MP4, Flash, Simple, Video, Shadowbox, Simple, Stats, Longtail, Advertisements

== Description == 
The Simple Flash Video Plugin builds on the plugins that all ready allow easy posting of .flv or .mp4 files on the popular WordPress platform. Simple Flash Video allows for all of the JW FLV options to be utilized via its config.xml file and post level overrides. With this plugin you can easily post .flv or .mp4 videos to your blog and have your viewers instantly watch the video without having to fully download the video before watching. It also combines the popular Shadowbox utility to allow for the videos to float over the website content for a clean look. Additionally it now includes Simple Stats which allow for highly detailed information on the viewing of your videos that are hosted played on your site. SFV can also utilizes Longtail Video advertisement system which allows you to make money with your videos if you buy a licence for the player at [Longtail Video Site](http://www.longtailvideo.com/referral.aspx?page=pubreferral&ref=chckorwtpopjizb "Longtail Video")   This plugin was built from the ground up to take full advantage of the JW FLV Player and Shadowbox. Code has been used from the Flash Video Player by Joshua Eldridge as per the licence agreemnet. When you put the [video...] code in your plugin it will be replaced by a Link to the video that will open in a Shadowbox window. If a .jpg file is in the same directory with the same name as the flv it will also use that photo in the link.  If no image is found it will simply place a Text link in your post. If you wish you can also disable the Shadowbox feature in the post command. 

 
== Installation ==

1. Download and unzip Simple Flash Video.
1. Transfer Simple-Flash-Video directory to your `/wp-content/plugins/` directory
1. CHMOD the congig.xml file to be writeable by all 666 or higher
1. CHMOD the stats folder to be writable by all 666 or higher
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin as you desire via the options menu. Click save to keep the file. You can also edit the config.xml manually
1. That's it! You're done. You can now enter the following into a post or page in WordPress to insert a video: [video filename=/video/video.flv /]
1. After a while check the SFV Stats link on your admin home page to see the latest stats

== Frequently Asked Questions ==

= I have activated the plugin, but don't see the video player. What do I do? =

Check and make sure that you have the appropriate hook in your template file for the header: `<?php wp_head(); ?>` without the ''

= When Shadowbox opens the video I get a 404 page what is wrong? =

Your web host has some security setttings that are preventing the video.php script from running. Email your support and they will usually approve the script and it will start running as expected.

= How do I change a setting for a single post? =

If you just want to change a single value for one video you can do it by modifying the code in your post. To Change the hight you would simply put: [video filename=video/video.flv height=500 /]
You can put any of the variables in this way using all lower case variablenames without spaces in the name. 

= How do I change the defaults? =

The config.xml file contains all of the current variables of JW FLV 3.16. You can modify this file to change any of the default settings that the plugin will use. 

= Help! I can't figure this out!? =

No problem! Give us a shout using the contact form @ http://www.simplethoughtproductions.com/contact/ and we will see if we cant help you. We also recommend posting a comment on the version of Simple Flash Video player to allow the community to assist as well. Find the post for your version @ http://www.simplethoughtproductions.com/category/simple-flash-video/

== Usage ==

To use the plugin simply put:  [video filename=/path/to/file/whatever.flv /] into your blog post replacing the path with the real path to the file. You can add additional parameters to code to be passed onto the flash player as well as control the plugin. All player options are supported. You can also pass thickbox=off to turn off thick box and simply have the player show in the post. You can also set embed=true to use embed code instead of the SWFObject code. Example of more advance usage: [video filename=/path/to/file/whatever.flv thickbox=off embed=true width=400 height=320 /] Any variable that the Longtail video player can use can be passed in through the command.

== Arguments ==

While you can use any argument in your video command that is supported by JW FLV 4.4 there are also a few that only apply to SFV.

* vid_image (http link to image) - use this to set a different image for the link
* botr (true false)- use this to enable support for bits on the run. This is needed if you are using domain masking and have not defaulted to bits on the run in the config.
* shadowbox (off on) - enables or disables shadowbox for the single video
* click_title (words spaced by &nbsp; ) this will allow you set a custom text below the image that is used for the video
* enableselling (true false) - enables selling of products. Selling is via google check out and requires that you put the shopping cart code found in the SFV Admin area into a text widget or somewhere on each page via your theme template
* price (number value) - The price to use when selling a product Must best a valid dollar amount ex: 15.50. DO NOT include the $. This will override the default price (if set) in the admin area
* product_type (string) - This is Displayed along with the price so people know what they are getting. Ex: DVD, VHS, CD, Download. This will also help you identify the proper product when processing orders. 


 
== Changelog ==

= 1.8 =
* ADDED Shopping Cart utilizing Google Checkout
* ADDED the ability to put space in via "quotes" which makes it easier to do multiword variables
* UPDATED to JW FLV 4.6

= 1.7 =
* ADDED meta data for shadowbox, video title, and arguments so theme authors can intergrate SFV into a theme.
* ADDED Option in Admin for meta info. Defaults to false (off). 
* Bits on the run now includes height and width in the config.xml so if you do not override the setting in the command it use what bits is set to. If you want to change the default of that player change it @ bitsontherun.com and all players using that tempalte will get updated.
* ADDED click_title as argument for command line so you can override the default click title.
* ADDED Plugin Setting on Admin page where you can enter multiple plugins separated by commas. 
* ADDED Additional Settings on Admin Page where you can input any extra settings for plugins or other variables placing each on a new line in a variablename=value style.
* ADDED setting to make all videos default to being power by BOTR. Use this if all of your videos are for BOTR but you use domain masking this way the plugin knows to use Bits even though its a different domain
* FIXED issue where title and description was not set properly for BOTR files
* REMOVED embed option - plugin will default to swfobject code. embed was never able to validate. 
* REMOVED Bitsontherun.com support using mp4 link.
* ADDED Bitsontherun.com support using .swf link (provides stats tracking and allows the changes made on their servers to come to your blog without needing an update)
* ADDED Blip support (PHP 5 Required)
* ADDED Customizable Title under the image that used to be hard coded to: Click to Watch. User can now set it in the admin to anything or nothing.
* UPGRADED to Longtail JW FLV Player Version 4.4.198
* Image Variable now set using the image that the plugin finds or one set via vid_image. This is displayed before the video starts playing, and afterwards. 
* Shadowbox now closes when videos are complete.
* Updated to latest Simple Stats
* Fixed issue where the swfobject code did not have a DIV of mediaspace for ads.
* Fixed issues where div was not closed properly in video.php for swfobject code.
* Fixed Title to properly have spaces for Longtail Premium Ads
* Reduced Main replacement Function by 50% and reduced complexity. 


= 1.6 =
* Fixed  when EMBED code was always called when shadowbox is off
* Fixed Text box width and height in SFV config page in WP 2.7
* Fixed issue when shadowbox was disable and custom sizes were ignored
* Fixed security issue with stream.php
* Simple Stats now tracks Flash Version Numbers of the visitors
* Fixed issue where Longtail code was not always displayed properly
* Updated to JWFLV 4.2 - Updated Streamer Variable and Colors
* Added the variable in the settings for a default image to use if none is found for the video
* Added ability to set SWFObject or Embed code as the default
* Updated to Simple Stats 1.2.1
* Added ability to remove padding from shadowbox around the video
* Changed Preview to display link and utilize shadowbox
* Added Youtube Ability 
* Require 9.0.115 Flash Version
* Added Link to update flash if proper Version is not installed
* Added Delete Logs Option
* Added Longtail adverts
* Added Options Saved Notification
* Fixed Centering Bug for videos in Shadowbox
* RSS Feeds now get a proper .mp4 file or link to FLV
* Fixed issue when users moved wp-content directory in WP 2.6
* Added vid_image variable to allow the use of any photo on a video link