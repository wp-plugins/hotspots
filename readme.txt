=== HotSpots ===
Contributors: dpowney
Donate link:
Tags: hotspot, hot, spot, mouse click, click, usability, heat map
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

HotSpots is a plugin which draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing user behaviour.

== Description ==

HotSpots is a simple plugin which draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing which buttons or links are popular and easy to use.

To show the heat map on the web page, add <i>?drawHotSpots=true</i> to the URL (i.e. www.mywebsite.com?drawHotSpots=true). Make sure the enable option is checked. The hot spots are shown as a heat map with a colour range from green (cold), to orange (warm) and red (hot). Each mouse click is represented as a coloured spot or circle. The colour of the spot is calculated based on how many other spots it is touching	within it's radius (i.e if a spot is touching another spot, then it has a heat value of 1. If it is touching two spots, then it has a heat value of 2 and so on).

The drawing of the heap map is done using HTML5 canvas. AJAX is used to send information about mouse clicks to the server in the background.

Tested using Google Chrome v23, Firefox v10 and Internet Explorer v9.

If you find any bugs, or have any enhancement ideas, please e-mail danielpowney@gmail.com.

Some limitations:
Currently does not cater for responsive design. Websites must be HTML5 compliant to view the heat map and browsers need to support HTML5 canvas. This plugin should not be used where performance is critical as an additional server request is made for each mouse click.
	
== Installation ==

1. Download plugin to plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the options page (Settings > HotSpots) and check the enabled option to start recording mouse clicks. To view the mouse clicks, add query parameter drawHotSpots=true to the URL (i.e. www.mywebsite.com?drawHotSpots=true)

== Frequently Asked Questions ==

== Screenshots ==

1. Heat map of mouse clicks on a Wordpress website. This is an example of 200 saved mouse clicks. As you can see, the navigation menu bar and top search input are highly used but the second search input is rarely used.

2. HotSpots plugin settings page

== Changelog ==

= 1.2 =
* Made the hot spots display for each page on website
* Fixed bug related to heat colour of hot spots
* Fixed admin styles
* Added updated and error messages on admin actions.
* Added AJAX to admin actions
* Fixed canvas top absolute position if wordpress admin bar is present

= 1.1 =
* Canvas caters for scrolling
* Fixed refresh database bug

= 1.0 =
* Initial release