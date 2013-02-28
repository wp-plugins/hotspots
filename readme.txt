=== HotSpots ===
Contributors: dpowney
Donate link: http://www.danielpowney.com
Tags: mouse click, tap, touch, click, usability, heat map, tracker, analytics
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

View a heat map of mouse clicks and touch screen taps overlayed on your webpage allowing you to improve usability by analysing user behaviour.

== Description ==

Google Analytics can show you what page a visitor went to, but HotSpots will show you which link a visitor clicked to get there. This can also give insight into which buttons or links are popular and easy to use including the effectiveness of advertising placement. 

= Features =
* Saves mouse click and touch screen tap information
* Each page on your website has it's own heat map
* Different heat maps are drawn when you resize the window, modify zoom levels and device pixel ratios to cater for responsive design
* You can configure how many mouse clicks or touch screen taps are necessary to be hot, the size and also the opacity of the circle
* You can apply URL filters to enable or disable the plugin for specific pages on your website (this can be useful for performance reasons)
* It's free and there's no sign up or registration required! All data is stored on your own WordPress database

= Notes =
The drawing of the heap map is done using HTML5 canvas. AJAX is used to send information about mouse clicks and touch screen taps to the server in the background.

This plugin should not be used where performance is critical as an additional server request is made for each mouse click and touch screen tap. Websites must be HTML5 compliant to view the heat map and browsers need to support HTML5 canvas.

== Installation ==

1. Download the plugin and put it in the plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the plugin options page (Settings > HotSpots)
1. To view the heat map of mouse clicks or touch screen taps, go to the Heat Maps section and then click View Heat Map for an available URL in the table. You can also manually add query parameter drawHeatMap=true to the URL of your page (i.e. www.mywebsite.com?drawHeatMap=true or www.mywebsite.com?cat=1&drawHeatMap=true).

== Frequently Asked Questions ==
**I cannot see the heat map when adding URL query parameter drawHeatMap=true.**
You cannot view the heat maps if your theme is not HTML5 compliant and you need to use an Internet browser which supports HTML5 canvas. Most modern browsers support HTML5 canvas now. Make sure the *Enable drawing heat map* option is turned on. If you see a box with the current browser window width, zoom level and device pixel information at the bottom right corner of your webpage, then there is no matching heat map data. You may need to resize the window, modify the zoom levels and device pixel ratios to match the heat map data collected. You can view the heat map data on the plugin options page (left menu Settings -> HotSpots). 
 
**What is device pixel ratio?** 
Device pixel ratios is a comparison between a device's pixel density to the style of the website using CSS. A screen has a limited number of pixels it can display, and a limited space over which to display them. Therefore a higher device pixel ratio means a higher quality display.

== Screenshots ==

1. Heat map of mouse clicks on a WordPress website. As you can see, the navigation menu bar and top search input are highly used but the second search input is rarely used.

2. Heat map of mouse clicks and touch screen taps on a responsive WordPress website

3. HotSpots plugin Settings page options for enabling features and configuring the heat map.

4. HotSpots plugin Settings page allows you to open pages on your website and draw the heat maps for different window widths, zoom levels and device pixel ratios.

5. HotSpots plugin Settings page allows you to apply URL filters

== Changelog ==

= 2.1.3 =
* Added data migration from old database tables

= 2.1.2 =
* Removed custom database table prefix and changed to WordPress database table prefix

= 2.1.1 =
* Added users count for widths and split of mouse clicks and touch screen taps count to heat maps table in Settings page

= 2.1 =
* Added touch screen tap support
* Added whitelist and blacklist URL filters
* Removed isResponsive option
* Changed form submit for clearing database
* Removed only enable for home page option as this is now replaced by URL filters
* Changed default drawing heat map option to true when plugin is activiated
* Chaned query param from drawHotSpots to drawHeatMap
* Removed select window width view heat maps and replaced with manual window, zoom level and device pixel resizing to draw different heat maps. This has changed due browser incompatibilities and behavioural differences.
* Added information panel with current width, zoom level and device pixel ratios when viewing heat maps

= 2.0.6 =
* Fixed bug recording mouse clicks in front-end when not logged in

= 2.0.5 =
* Fixed this:: in tables.php for older versions of PHP

= 2.0.4 
* Fixed some double colon scope issues for older versions of PHP
* Added pagination to URL table

= 2.0.3 =
* Fixed register activate hook for PHP versions <= 5.2

= 2.0.2 =
* Fixed accessing class constants
* Removed check for windowReady URL query parameter when drawing hot spots

= 2.0.1 = 
* Fixed tables.php import

= 2.0 =
* Refactored code
* Heat value calculation moved to server side to improve performance
* Split enabled option into two options to save mouse clicks and to be able to draw the hotspots
* Renamed showOnClick option to debug
* Added table in settings page to view URL's, counts of mouse clicks and available window sizes.
* Added feature on the settings page to open a new window and draw the hot spots for specific URL's with a selected window size width

= 1.3.2 =
* Fixed colour fill bug with red amount for < warm

= 1.3.1 =
* Fixed saving checkbox options

= 1.3 =
* Fixed admin AJAX actions bug
* Added new option for home page only

= 1.2.8 =
* Fixed issue where hot spots were being drawn after a window resize event ignoring the presence of the drawHotSpots URL query parameter and not checking the enabled option
* Clarified enable and show on click options in plugin settings page

= 1.2.7 =
* Resolved issue with drawing hotspots in administrator backend
* Do not delete records in DB on plugin activation
* Minor wording changes in plugin settings page

= 1.2.6 =
* Added responsivene design awareness

= 1.2.5 =
* Fixed stripping ? and & when removing drawHotSpots query parameter from URL

= 1.2.4 =
* Fixed IE canvas top position when wordpress admin bar is present

= 1.2.3 =
* Ensured opacity of elements is unchanged if < 1
* Check z-index to ensure heat map is overlayed on top of all elements

= 1.2.2 =
* Fixed current id bug when drawing hot spots and calculating heat value
* Added where clause by url when getting all mouse clicks to draw the heat map
* URLs are now escaped when they're saved to the database

= 1.2.1 =
* Minor bug fixes

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