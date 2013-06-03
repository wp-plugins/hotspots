=== Hotspots User Tracker ===
Contributors: dpowney
Donate link: http://www.danielpowney.com
Tags: mouse click, tap, touch, click, usability, heat map, tracker, analytics, tracking
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hotspots User Tracker draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing user behaviour.

== Description ==

View a heat map of mouse clicks and touch screen taps overlayed on your webpage allowing you to improve usability by analysing user behaviour. Google Analytics can show you what page a visitor went to, but Hotspots User Tracker will show you which link a visitor clicked to get there. This can also give insight into which buttons or links are popular and easy to use including the effectiveness of advertising placement. 

= Features =
* Saves mouse click and touch screen tap information
* Each page on your website has it's own heat map
* It's free and there's no sign up or registration required!
* All data is stored on your own WordPress database
* Different heat maps are drawn when you resize the window, modify zoom levels and device pixel ratios to cater for responsive design
* You can configure how many mouse clicks or touch screen taps are necessary to be hot, the size and also the opacity of the circle
* You can apply URL filters to enable or disable the plugin for specific pages on your website (this can be useful for performance reasons)

= Demo =
Here's a demo of the heat map of mouse clicks and touch screen taps overlayed on the Home page of a website for 1600px width, go to http://danielpowney.com/?drawHeatMap=true&width=1600&devicePixelRatio=1&zoomLevel=1. Make sure you resize width to 1600px (there's always an information panel at the bottom right of the page to help you).

= Notes =
This plugin should not be used where performance is critical as an additional server request is made for each mouse click and touch screen tap. To be able to view the heat maps, your WordPress theme must be HTML5 compliant and you need to use an Internet browser which supports HTML5 canvas.
== Installation ==

1. Download the plugin and put it in the plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the plugin menu page in the admin panel
1. To view the heat maps, go to the Heat Maps tab or add URL query parameter drawHeatMap=true (i.e. http://www.danielpowney.com?drawHeatMap=true) to the URL

== Frequently Asked Questions ==

**I cannot see the heat map when adding URL query parameter drawHeatMap=true.**

You cannot view the heat maps if your theme is not HTML5 compliant and you need to use an Internet browser which supports HTML5 canvas. Most modern browsers support HTML5 canvas now. Make sure the *Enable drawing heat map* option is turned on in the General options tab. If you see a box with the current browser window width, zoom level and device pixel information at the bottom right corner of your webpage, then there is no matching heat map data. You may need to resize the window, modify the zoom levels and device pixel ratios to match the heat map data collected. You can use the Heat Maps tab to find matching heat map data and to view the heat maps.

**Plugin update does not seem to be working**

If you're using a caching plugin such as W3TC then empty all page cache. Also empty your browser cache to ensure the latest JavaScript files are loaded.

**What is device pixel ratio?**

The device pixel ratio is the ratio between logical pixels and physical pixels (for websites that is the display device pixel density compared with CSS pixels). For instance, the iPhone 4 and iPhone 4S reports a device pixel ratio of 2, because the physical linear resolution is double the logical resolution (physical resolution: 960 x 640 and logical resolution: 480 x 320). A higher device pixel ratio means a higher quality display (effectively more dots per inch on the display screen).

**How do I view the heat maps for different devices**

If you have heat map data with various device pixel ratios, then you can use the actual device or find a device emulator to view the the heat maps. There are also options to ignore the device pixel ratio, ignore the zoom level and to ignore the width, but these options will not provide accurate heat map results.

**Do I have to resize the window to the exact width?**

No. There is an option to allow up to 20 pixels each side of your target width to display the heat map. This amount can be changed and is defaulted to 6 pixels.

**My screen resolution is not large enough to display some of the heat maps**

You can try ignoring the device pixel ratio and ignore the zoom level options, then do a browser zoom out to increase the website width. However, the heat map may not be entirely accurate if you do this. For heat map data with a device pixel ratio of 1:1 and a zoom level of 100%, viewing larger website widths using this method works OK.

== Screenshots ==
1. Heat map of clicks and taps on a WordPress website. As you can see, the navigation menu bar and top search input are highly used but the second search input is rarely used.

2. Heat map of clicks and taps on a responsive WordPress website for mobiles.

3. Heat Maps tab. There's different heat maps for each width, device pixel ratio (device pixels compared to website pixels) and zoom level.

4. Advanced options tab. You can customise the display of the heat maps and refresh the database.

5. General options tab.

== Changelog ==

= 3.0.1 =
* Fix to show target information for different widths, device pixel ratios and zoom levels per URL when clicking the View Heat Map button
* Added icon to plugin menu

= 3.0 =
* Major release and refactoring of plugin code
* DB tables and columns renamed
* Improved UI including tabs
* Options are now managed via the Settings API
* Additional information displayed to easier target heat map
* Slight table changes to heat maps
* Added width allowance option
* New name for plugin Hotspots User Tracker - previously known as HotSpots
* Plugin page added as a top level menu item in the admin panel
* Added ignore width option

= 2.2.5 =
* Ignore hash # in URL when retrieving URL query params in JS

= 2.2.4 = 
* Overlayed information panel always on top
* Updated detect-zoom.js to version 1.0.4

= 2.2.3 =
* Added normalizing of URL's in DB tables on activation

= 2.2.2 =
* Fixed whitelist to work with normalized URLs

= 2.2.1 =
* Allow detect zoom JavaScript function to be at top as some plugins such as W3 Total Cache minify JS and override the location of the JavaScript.

= 2.2 =
* Added ignore zoom level and device pixel ratio options for drawing heat map
* Improved coding style as per WordPress coding convention guidelines
* Added created date column in the hotspots database table when saving clicks and taps
* Added maximum number of clicks and taps saved per URL option
* Added URL normalization

= 2.1.5 =
* Fixed data migration issues
* Added column defaults to hotspot table

= 2.1.4 =
* Fixed bug recording mouse clicks

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

= 2.0.4 =
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