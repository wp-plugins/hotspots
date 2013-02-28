<?php 
/*
Plugin Name: HotSpots
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: View a heat map of mouse clicks and touch screen taps overlayed on your webpage allowing you to improve usability by analysing user behaviour.
Version: 2.1.4
Author: Daniel Powney
Auhtor URI: www.danielpowney.com
License: GPL2
*/
?>
<?php


require dirname(__FILE__).DIRECTORY_SEPARATOR .'tables.php';

// TODO separate plugin admin code
//if (is_admin()) {
//	include('admin.php');
//}


/**
 * HotSpots class
 *
 * @since 2.0
 * @author dpowney
 */
class HotSpots {

	// constants
	const
	VERSION 						= '2.1.4',
	DB_VERSION						= '2.1.2',
	ID					 			= 'hotspots',

	/* Front end */
	HOTSPOTS_DATA 					= 'hotSpotsData',
	DRAW_HOTSPOTS_QUERY_PARAM_NAME 	= 'drawHeatMap',

	/* database */
	HOTSPOTS_TBL_NAME 				= 'hotspot',
	ID_COLUMN						= "id",
	X_COLUMN						= 'x',
	Y_COLUMN						= 'y',
	URL_COLUMN						= 'url',
	WIDTH_COLUMN					= 'screenWidth',
	ZOOM_LEVEL_COLUMN				= 'zoomLevel',
	IS_TOUCH_COLUMN					= 'isTouch',
	IP_ADDRESS_COLUMN				= 'ipAddress',
	DEVICE_PIXEL_RATIO_COLUMN		= "devicePixelRatio",
	
	/* options */
	SAVE_MOUSE_CLICKS_OPTION 		= 'savedMouseClicks',
	DRAW_HOTSPOTS_ENABLED_OPTION 	= 'drawHotSpotsEnabled',
	DEBUG_OPTION 					= 'debug',
	HOT_VALUE_OPTION 				= 'hotValue',
	SPOT_OPACITY_OPTION 			= 'spotOpacity',
	SPOT_RADIUS_OPTION 				= 'spotRadius',
	FILTER_TYPE_OPTION				= "filterType",
	APPLY_FILTERS_OPTION			= "applyFilters",
	DB_VERSION_OPTION				= "dbVersion",
	
	/* defaults*/
	DEFAULT_SAVE_MOUSE_CLICKS 		= true,
	DEFAULT_DRAW_HOTSPOTS_ENABLED 	= true,
	DEFAULT_DEBUG					= false,
	DEFAULT_HOT_VALUE				= '20',
	DEFAULT_SPOT_OPACITY			= '0.2',
	DEFAULT_SPOT_RADIUS				= '8',
	DEFAULT_APPLY_FILTERS			= false,
	
	// filter type values
	WHITELIST_FILTER_TYPE			= "whitelist",
	BLACKLIST_FILTER_TYPE			= "blacklist";
	
	// URL query params which are ignored by the plugin
	public static $ignoreQueryParams = array('drawHeatMap');

	
	/**
	 * Constructor
	 *
	 * @since 2.0
	 */
	function __construct() {

		// Create settings page, add JavaScript and CSS
		if(is_admin()) {
			add_action('admin_menu', array($this, 'createSettingsPage'));
			add_action('admin_enqueue_scripts', array($this, 'adminAssets'));
		} else {
			add_action('wp_enqueue_scripts', array($this, 'assets'));
		}

		// Activation hook
		register_activation_hook(__FILE__, array($this, 'activatePlugin'));
		
		// Uninstall hook
		register_uninstall_hook(__FILE__, array($this, 'uninstallPlugin'));
		
		// No deactivate hook needed

		// Setup AJAX calls
		$this->addAjaxActions();
	}

	
	/**
	 * Uninstall plugin
	 * 
	 * @since 2.1.4
	 */
	function uninstallPlugin() {
		// Delete options
		delete_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION);
		delete_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION);
		delete_option(HotSpots::DEBUG_OPTION);
		delete_option(HotSpots::HOT_VALUE_OPTION);
		delete_option(HotSpots::SPOT_OPACITY_OPTION);
		delete_option(HotSpots::SPOT_RADIUS_OPTION);
		delete_option(HotSpots::FILTER_TYPE_OPTION);
		delete_option(HotSpots::APPLY_FILTERS_OPTION);
		delete_option(HotSpots::DB_VERSION_OPTION);
		
		// Drop tables
		global $wpdb;
		$wpdb->query("DROP TABLE IF_EXISTS " . $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME);
		$wpdb->query("DROP TABLE IF_EXISTS " . $wpdb->prefix . FilterTable::FILTER_TBL_NAME);
	}
	
	/**
	 * Activates the plugin by setting up DB tables and adding options
	 * 
	 * @since 2.0
	 */
	function activatePlugin() {
		global $wpdb;	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
		// Create database tables if they doe not exist
		$sql1 = "CREATE TABLE " . $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME . " (
		".HotSpots::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
		".HotSpots::X_COLUMN." int(11) NOT NULL,
		".HotSpots::Y_COLUMN." int(11) NOT NULL,
		".HotSpots::URL_COLUMN." varchar(255),
		".HotSpots::WIDTH_COLUMN." int(11),
		".HotSpots::IS_TOUCH_COLUMN." tinyint(1),
		".HotSpots::IP_ADDRESS_COLUMN." varchar(255),
		".HotSpots::ZOOM_LEVEL_COLUMN." double precision,
		".HotSpots::DEVICE_PIXEL_RATIO_COLUMN." double precision,
		PRIMARY KEY (".HotSpots::ID_COLUMN.")
		) ENGINE=InnoDB AUTO_INCREMENT=1;";
		dbDelta($sql1);
	
		$sql2 = "CREATE TABLE ".$wpdb->prefix.FilterTable::FILTER_TBL_NAME." (
		".FilterTable::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
		".FilterTable::URL_COLUMN." varchar(255),
		PRIMARY KEY (id)
		) ENGINE=InnoDB AUTO_INCREMENT=1;";
		dbDelta($sql2);
		
		// Migrate old data if necessary
		try {
			if($wpdb->get_var('SHOW TABLES LIKE "hsp_hotspot"') == "hsp_hotspot") {
				$wpdb->query("INSERT INTO ".$wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME." SELECT * FROM hsp_hotspot");
				$wpdb->query("DROP TABLE IF_EXISTS hsp_hotspot");
			}
			if($wpdb->get_var('SHOW TABLES LIKE "hsp_filter"') == "hsp_filter") {
				$wpdb->query("INSERT INTO " .$wpdb->prefix . FilterTable::FILTER_TBL_NAME ." SELECT * FROM hsp_filter");
				$wpdb->query("DROP TABLE IF_EXISTS hsp_filter");
			}
		} catch(Exception $e) {
			// do nothing
		}
	
		// Add options
		add_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION, HotSpots::DEFAULT_SAVE_MOUSE_CLICKS, '', 'yes');
		add_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, HotSpots::DEFAULT_DRAW_HOTSPOTS_ENABLED, '', 'yes');
		add_option(HotSpots::DEBUG_OPTION, HotSpots::DEFAULT_DEBUG, '', 'yes');
		add_option(HotSpots::HOT_VALUE_OPTION, HotSpots::DEFAULT_HOT_VALUE, '', 'yes');
		add_option(HotSpots::SPOT_OPACITY_OPTION, HotSpots::DEFAULT_SPOT_OPACITY, '', 'yes');
		add_option(HotSpots::SPOT_RADIUS_OPTION, HotSpots::DEFAULT_SPOT_RADIUS, '', 'yes');
		add_option(HotSpots::FILTER_TYPE_OPTION, HotSpots::WHITELIST_FILTER_TYPE, '', 'yes');
		add_option(HotSpots::APPLY_FILTERS_OPTION, HotSpots::DEFAULT_APPLY_FILTERS, '', 'yes');
		
		// add a DB version so we know which DB structure was previously used
		add_option(HotSpots::DB_VERSION_OPTION, HotSpots::DB_VERSION, '', 'yes');
		
	}
	
	
	/**
	 * Register AJAX call actions
	 *
	 * @since 1.2
	 */
	public function addAjaxActions() {
		if (is_admin()) {
			add_action('wp_ajax_add_mouse_click', array($this, 'addMouseClick'));
			add_action('wp_ajax_get_mouse_clicks',  array($this, 'getMouseClicks'));
			add_action('wp_ajax_refreshDatabase',  array($this, 'refreshDatabase'));
			add_action('wp_ajax_saveChanges', array($this, 'saveChanges'));
			add_action('wp_ajax_addFilter', array($this, 'addFilter'));
			add_action('wp_ajax_setFilterType', array($this, 'setFilterType'));
			add_action('wp_ajax_nopriv_add_mouse_click',  array($this, 'addMouseClick'));
			add_action('wp_ajax_nopriv_get_mouse_clicks',  array($this, 'getMouseClicks'));
			add_action('wp_ajax_nopriv_refreshDatabase',  array($this, 'refreshDatabase'));
			add_action('wp_ajax_nopriv_saveChanges', array($this, 'saveChanges'));
			add_action('wp_ajax_nopriv_addFilter',  array($this, 'addFilter'));
			add_action('wp_ajax_nopriv_setFilterType', array($this, 'setFilterType'));
		}
	}

	
	/**
	 * Records mouse click/touch screen tap information and saves to database
	 *
	 * @since 2.0
	 */
	public function addMouseClick() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];

		if (wp_verify_nonce($ajaxNonce, HotSpots::ID.'-nonce')) {
			$x = isset($_POST['x']) ? $_POST['x'] : '';
			$y = isset($_POST['y']) ? $_POST['y'] : '';
			$url = isset($_POST['url']) ? $this->removeQueryStringParams(addslashes($_POST['url']), HotSpots::$ignoreQueryParams) : '';
			$width = isset($_POST['width']) ? intval($_POST['width']) : '';
			$isTouch = isset($_POST['isTouch']) ? (($_POST['isTouch'] == "true") ? true : false) : false;
			$zoomLevel = isset($_POST['zoomLevel']) ? doubleval($_POST['zoomLevel']) : 1;
			$ipAddress = $this->getIPAddress();
			$devicePixelRatio = isset($_POST['devicePixelRatio']) ? doubleval($_POST['devicePixelRatio']) : 1;
			
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME, 
					array( 	HotSpots::X_COLUMN => $x, 
							HotSpots::Y_COLUMN => $y, 
							HotSpots::URL_COLUMN => $url, 
							HotSpots::WIDTH_COLUMN => $width, 
							HotSpots::ZOOM_LEVEL_COLUMN => $zoomLevel, 
							HotSpots::IS_TOUCH_COLUMN => $isTouch,
							HotSpots::IP_ADDRESS_COLUMN => $ipAddress,
							HotSpots::DEVICE_PIXEL_RATIO_COLUMN => $devicePixelRatio
						)
				);
			$id = $wpdb->insert_id;
				
			$debug = get_option(HotSpots::DEBUG_OPTION);
			if ($debug == true) {
				// get all mouse clicks and touch screen touches and calculate heat value for added mouse click
				$query = "SELECT " . HotSpots::ID_COLUMN . ", ".HotSpots::X_COLUMN.", ".HotSpots::Y_COLUMN.", "
						. HotSpots::URL_COLUMN.", ".HotSpots::WIDTH_COLUMN." FROM "
						. $wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE ".HotSpots::URL_COLUMN." = '" . $url . "'";

				$range = 6; // allow a range of 6 pixels either side to be the same
				$width = isset($_POST['width']) ? intval($_POST['width']) : 0;
				$diffLeft = $width - $range;
				$diffRight = $width + $range;
				
				$query .= ' AND '.HotSpots::WIDTH_COLUMN.' >= ' . $diffLeft . ' AND '.HotSpots::WIDTH_COLUMN.' <= '. $diffRight;
				
				$rows = $wpdb->get_results($query);
				$heatValue = $this->calculateHeatValue($x, $y, $id, $rows);
				$response = array('id' => $id, 'heatValue' => $heatValue);
			} else {
				$response = array('id' => $id);
			}
				
			echo json_encode($response);
		}
		die();
	}
	

	/**
	 * Gets all mouse clicks/touch screen taps
	 *
	 * @since 1.0
	 */
	public function getMouseClicks() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];
		$rows = null;
		if (wp_verify_nonce($ajaxNonce, HotSpots::ID .'-nonce')) {
			$url = isset($_POST['url']) ? $this->removeQueryStringParams(addslashes($_POST['url']), HotSpots::$ignoreQueryParams) : '';
			
			$query = "SELECT " . HotSpots::ID_COLUMN . ", ".HotSpots::X_COLUMN.", ".HotSpots::Y_COLUMN.", "
						. HotSpots::URL_COLUMN.", ".HotSpots::WIDTH_COLUMN." FROM "
						. $wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE ".HotSpots::URL_COLUMN." = '" . $url . "'";

			$range = 6; // allow a range of 6 pixels either side to be the same
			$width = isset($_POST['width']) ? intval($_POST['width']) : 0;
			$diffLeft = $width - $range;
			$diffRight = $width + $range;
			$devicePixelRatio = isset($_POST['devicePixelRatio']) ? doubleval($_POST['devicePixelRatio']) : 1;
			$zoomLevel = isset($_POST['zoomLevel']) ? doubleval($_POST['zoomLevel']) : 1;
			
			$query .= ' AND '.HotSpots::WIDTH_COLUMN.' >= ' . $diffLeft . 
				' AND '.HotSpots::WIDTH_COLUMN.' <= '. $diffRight .
				' AND ' . HotSpots::ZOOM_LEVEL_COLUMN . ' = ' . $zoomLevel . 
				' AND ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ' = ' . $devicePixelRatio;
			
			$rows = $wpdb->get_results($query);
				
			// create mouse clicks array with heat values
			$mouseClicks = array();
			$index = 0;
			foreach ($rows as $row) {
				$id = $row->id;
				$x = $row->x;
				$y = $row->y;
				
				$url = $row->url;
				$width = $row->screenWidth;
				$heatValue = $this->calculateHeatValue($x, $y, $id, $rows);
				$mouseClicks[$index++] = array('id' => $id, 'x' => $x, 'y' => $y, 'width' => $width, 'url' => $url, 
						'heatValue' => $heatValue);
			}
		}
		echo json_encode($mouseClicks);
		die();
	}


	/**
	 * Calculates the heat value given closeness of existing mouse clicks
	 *
	 * @param x
	 * @param y
	 * @param id
	 * @param rows
	 */
	public function calculateHeatValue($x, $y, $id, $rows) {
		$heatValue = 0;
		$spotRadius = get_option(HotSpots::SPOT_RADIUS_OPTION);

		foreach ($rows as $row) {
			$currentX = $row->x;
			$currentY = $row->y;
			$currentId = $row->id;

			// skip if comparing the same mouse click
			if ($id == $currentId) {
				continue;
			}
				
			// Check if the spot is touching other spots
			$diffX = $x - $currentX;
			$diffY = $y - $currentY;
			$hotX = ($diffX > - $spotRadius && $diffX < $spotRadius);
			$hotY = ($diffY > - $spotRadius && $diffY < $spotRadius);
			if ($hotX && $hotY) {
				$heatValue++;
			}
		}
		return $heatValue;
	}

	
	/**
	 * Save settings changes
	 *
	 * @since 1.2
	 */
	public function saveChanges() {

		$errors = "";
		// draw hotspots enabled option
		if (isset($_POST['drawHotSpotsEnabled'])) {
			if ($_POST['drawHotSpotsEnabled'] == "true") {
				update_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, true);
			} else {
				update_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, false);
			}
		}

		// Save mouse clicks option
		if (isset($_POST['saveMouseClicks'])) {
			if ($_POST['saveMouseClicks'] == "true") {
				update_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION, true);
			} else {
				update_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION, false);
			}
		}

		// debug option
		if (isset($_POST['debug'])) {
			if ($_POST['debug'] == "true") {
				update_option(HotSpots::DEBUG_OPTION, true);
			} else {
				update_option(HotSpots::DEBUG_OPTION, false);
			}
		}
		
		// apply filters option
		if (isset($_POST['applyFilters'])) {
			if ($_POST['applyFilters'] == "true") {
				update_option(HotSpots::APPLY_FILTERS_OPTION, true);
			} else {
				update_option(HotSpots::APPLY_FILTERS_OPTION, false);
			}
		}
		
		// hot value option
		if (isset($_POST['hotValue'])) {
			if (is_numeric($_POST['hotValue'])) {
				$hotValue = intval($_POST['hotValue']);
				if ($hotValue > 0) {
					update_option(HotSpots::HOT_VALUE_OPTION, $hotValue);
				} else {
					$errors .= "<p>Hot value must be numeric greater than 0.</p>";
				}
			} else {
				$errors .= "<p>Hot value must be numeric greater than 0.</p>";
			}
		}

		// spot opacity option
		if (isset($_POST['spotOpacity'])) {
			if (is_numeric($_POST['spotOpacity'])) {
				$spotOpacity = floatval($_POST['spotOpacity']);
				if ($spotOpacity >= 0 && $spotOpacity <= 1) {
					update_option(HotSpots::SPOT_OPACITY_OPTION, $spotOpacity);
				} else {
					$errors .= "<p>Spot opacity must be numeric between 0 and 1.</p>";
				}
			} else {
				$errors .= "<p>Spot opacity must be numeric between 0 and 1.</p>";
			}
		}

		// spot radius option
		if (isset($_POST['spotRadius'])) {
			if (is_numeric($_POST['spotRadius'])) {
				$spotRadius = intval($_POST['spotRadius']);
				if ($spotRadius >= 1 && $spotRadius <= 25) {
					update_option(HotSpots::SPOT_RADIUS_OPTION, $spotRadius);
				} else {
					$errors .= "<p>Spot radius must be numeric between 1 and 25.</p>";
				}
			} else {
				$errors .= "<p>Spot radius must be numeric between 1 and 25.</p>";
			}
		}

		if (strlen($errors) > 0) {
			echo json_encode(array('success' => false, 'errors' => $errors));
		} else {
			echo json_encode(array('success' => true));
		}

		die();
	}
	
	
	/**
	 * Sets the filter type either whitelist or blacklist
	 *
	 * @since 2.1
	 */
	public function setFilterType() {
	
		$errors = "";
		// filter type option
		if (isset($_POST['filterType'])) {
			if ($_POST['filterType'] == HotSpots::WHITELIST_FILTER_TYPE) {
				update_option(HotSpots::FILTER_TYPE_OPTION, HotSpots::WHITELIST_FILTER_TYPE);
			} else {
				update_option(HotSpots::FILTER_TYPE_OPTION, HotSpots::BLACKLIST_FILTER_TYPE);
			}
		} else {
			$errors .'<p>Could not set filter type.</p>';
		}
		
		if (strlen($errors) > 0) {
			echo json_encode(array('success' => false, 'errors' => $errors));
		} else {
			echo json_encode(array('success' => true));
		}
		
		die();
	}

	/**
	 * Javascript and CSS used by the plugin
	 *
	 * @since 2.0
	 */
	public function assets(){
		wp_enqueue_script('jquery');
		
		wp_enqueue_style('hotspots-style', plugins_url('hotspots.css', __FILE__));
		wp_enqueue_script('detect-zoom', plugins_url('detect-zoom.js', __FILE__), array(), false, true);
		wp_enqueue_script(HotSpots::ID, plugins_url('hotspots.js', __FILE__), array('jquery', 'detect-zoom'), HotSpots::VERSION, true);
		
		$drawHotSpotsEnabled = get_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION);
		$saveMouseClicks = get_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION);

		// Check options if applying filters
		$applyFilters = get_option(HotSpots::APPLY_FILTERS_OPTION);
		// Also check if at least one of the options is true to improve performance
		if ($applyFilters == true && ($drawHotSpotsEnabled == true || $saveMouseClicks == true)) {
			// check if enabled
			$filterType = get_option(HotSpots::FILTER_TYPE_OPTION);
			$currentURL = $this->removeQueryStringParams(addslashes($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]), HotSpots::$ignoreQueryParams);
		
			global $wpdb;
			$query = "SELECT " .FilterTable::URL_COLUMN . " FROM " . $wpdb->prefix.FilterTable::FILTER_TBL_NAME;
			$rows = $wpdb->get_results($query);
			
			if ($filterType == HotSpots::BLACKLIST_FILTER_TYPE) { // excludes
				foreach ($rows as $row) {
					$url = $row->url;
				
					// If it's in the blacklist, we disable the options
					if ($url == $currentURL) {
						$drawHotSpotsEnabled = 0;
						$saveMouseClicks = 0;
						break;
					}
				}
			} else { // whitelist (includes)
				// check if the current url is in the whitelist
				$found = false;
				foreach ($rows as $row) {
					$url = $row->url;
					
					// If it's not in the whitelist, we disable the options
					if ($url == $currentURL) {
						$found = true;
						break;
					}
				}
				if ($found == false) {
					$drawHotSpotsEnabled = 0;
					$saveMouseClicks = 0;
				}
			}
		}
		
		$config_array = array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'ajaxNonce' => wp_create_nonce(HotSpots::ID.'-nonce'),
				'drawHotSpotsEnabled' => $drawHotSpotsEnabled,
				'saveMouseClicks' => $saveMouseClicks,
				'debug' => get_option(HotSpots::DEBUG_OPTION),
				'hotValue' => get_option(HotSpots::HOT_VALUE_OPTION),
				'spotOpacity' => get_option(HotSpots::SPOT_OPACITY_OPTION),
				'spotRadius' => get_option(HotSpots::SPOT_RADIUS_OPTION),
				'filterType' => get_option(HotSpots::FILTER_TYPE_OPTION)
		);
		wp_localize_script(HotSpots::ID, HotSpots::HOTSPOTS_DATA, $config_array);
	}
	
	
	/**
	 * Admin assets
	 *
	 * @since 1.2.8
	 */
	public function adminAssets() {
		$config_array = array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'ajaxNonce' => wp_create_nonce(HotSpots::ID.'-nonce')
		);
		wp_enqueue_script('jquery');
	
		if (is_admin()) {
			wp_enqueue_style('hotspots-admin-style', plugins_url('hotspots-admin.css', __FILE__));
			wp_enqueue_script(HotSpots::ID.'-admin', plugins_url('hotspots-admin.js', __FILE__), array('jquery'));
			wp_localize_script(HotSpots::ID.'-admin', HotSpots::HOTSPOTS_DATA, $config_array);
	
		}
	
	}

	
	/**
	 * Creates the Settings page
	 *
	 * @since 2.0
	 */
	public function createSettingsPage() {
		add_options_page(__('HotSpots', HotSpots::ID), __('HotSpots', HotSpots::ID), 'manage_options', HotSpots::ID, array($this, 'settingsPage'));
	}


	/**
	 * Shows the admin options page
	 *
	 * @since 2.0
	 */
	public function settingsPage() {
		
		// Process form submits
		global $wpdb;
		$success = '';
		$errors = '';
		if (isset($_POST['addFilterSubmit']) && $_POST['addFilterSubmit'] === "true") {
			// Add URL filter
			$url = isset($_POST['urlFilter']) ? trim($_POST['urlFilter']) : '';

			if (strlen($url) == 0) {
				$errors .= '<p>URL field is missing.</p>';
			} else {
				$url = addslashes($url);
				
				try {
					// first make sure URL has not already been added
					$query = 'SELECT * FROM '.$wpdb->prefix.FilterTable::FILTER_TBL_NAME . ' where '. FilterTable::URL_COLUMN .' = "' .$url . '"';
					$count = $wpdb->query($query);
						
					if ($count > 0) {
						$errors .= '<p>URL filter for ' . $url .' already exists.</p>';
					} else {
						$wpdb->flush();
						$results = $wpdb->insert( $wpdb->prefix.FilterTable::FILTER_TBL_NAME, array( 'url' => $url ) );
						$success .= '<p>URL filter added successfully.</p>';
					}
				} catch (Exception $e) {
					$errors .= '<p>An error has occured. ' . $e->getMessage() .' </p>';
				}
			}
		} else if (isset($_POST['clearDatabaseSubmit']) && $_POST['clearDatabaseSubmit'] === "true") {
			// Clear database table
			try {
				$rows = $wpdb->get_results( "DELETE FROM ".$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE 1" );
				$success .= '<p>Database cleared successfully.</p>';
			} catch (Exception $e) {
				$errors .= '<p>An error has occured. ' . $e->getMessage() . '</p>';
			}
		}
		
		// current option values
		$current_drawHotSpotsEnabled = get_option(HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION);
		$current_saveMouseClicks = get_option(HotSpots::SAVE_MOUSE_CLICKS_OPTION);
		$current_debug = get_option(HotSpots::DEBUG_OPTION);
		$current_hotValue = get_option(HotSpots::HOT_VALUE_OPTION);
		$current_spotOpacity = get_option(HotSpots::SPOT_OPACITY_OPTION);
		$current_spotRadius = get_option(HotSpots::SPOT_RADIUS_OPTION);
		$current_filterType = get_option(HotSpots::FILTER_TYPE_OPTION);
		$current_applyFilters = get_option(HotSpots::APPLY_FILTERS_OPTION);
		?>
		
		<div id="hotSpotOptions" class="wrap">
			<div class="icon32" id="icon-tools"><br /></div>
			
			<h1>HotSpots</h1>
			
			<p>HotSpots is a plugin which draws a heat map of mouse clicks and
				touch screen taps overlayed on your webpage. This can allow you to
				improve usability by analysing user behaviour including which buttons
				or links are popular and easy to use and the effecfiveness of
				advertising placement.</p>
	
			<!-- messages are shown in this div from the server -->
			<div id="messages">
			<?php 
			if (strlen($success) > 0) {
				echo '<div class="updated">' . $success . '</div>';
			} else if (strlen($errors) > 0) {
				echo '<div class="error">' . $errors . '</div>';
			}
			?>
			</div>
			
			<form method="post" action="" id="optionsForm">
			
				<!-- TODO wp_nonce_field and check_admin_referer -->
			
				<!-- hidden inputs for checking form submit -->
				<input type="hidden" name="addFilterSubmit" id="addFilterSubmit" value="false" />
				<input type="hidden" name="clearDatabaseSubmit" id="clearDatabaseSubmit" value="false" />
			
				<h3>Options</h3>
				<ul>
					<li>
						<input type="checkbox" value="<?php echo $current_saveMouseClicks ?>" name="saveMouseClicks" id="saveMouseClicks" <?php if ($current_saveMouseClicks) { ?> checked="checked" <?php } ?> />
						<label for="saveMouseClicks">Save mouse clicks and touch screen taps</label><p class="description">Turn on to start recording mouse click and touch screen tap information on your website.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_drawHotSpotsEnabled ?>" name="drawHotSpotsEnabled" id="drawHotSpotsEnabled" <?php if ($current_drawHotSpotsEnabled) { ?> checked="checked" <?php } ?> />
						<label for="drawHotSpotsEnabled">Enable drawing heat map</label>
						<p class="description">Enable to allow drawing of the heat map overlayed on your website. To manually draw the heat map, add query parameter drawHeatMap=true to the URL (i.e. www.mywebsite.com?drawHeatMap=true or www.mywebsite.com?cat=1&drawHeatMap=true). Your WordPress theme must be HTML5 compliant and your Internet browser must support HTML5 canvas to be able to view the heat map.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_debug ?>" name="debug" id="debug" <?php if ($current_debug) { ?> checked="checked" <?php } ?> />
						<label for="debug">Debug</label>
						<p class="description">Turn on to debug and draw hot spots on every	mouse click and touch screen tap. This option is useful for testing that that the mouse clicks and touch screen taps are being recorded and that the plugin is working as expected.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_applyFilters ?>" name="applyFilters" id="applyFilters" <?php if ($current_applyFilters) { ?> checked="checked" <?php } ?> />
						<label for="applyFilters">Apply URL filters</label>
						<p class="description">Turn on to apply the URL filters. See URL Filters section below.</p>
					</li>
					<p>Each mouse click and touch screen tap is represented as a coloured
						circle or spot. The spots create a heat map with a colour range from green
						(cold), orange (warm) and red (hot). The colour of the spot is
						calculated based on how many other spots it is touching within it's
						radius (i.e if a spot is touching another spot, then it has a heat
						value of 1. If it is touching two spots, then it has a heat value of
						2 and so on).</p>
					<li>
						<label for="hotValue" class="smallWidth">Set a hot value</label>
						<input type="text" value="<?php echo $current_hotValue ?>" name="hotValue" id="hotValue" />&nbsp;(must be greater than 0) 
						<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
					</li>
					<li>
						<label for="spotRadius" class="smallWidth">Set the spot radius</label>
						<input type="text" value="<?php echo $current_spotRadius ?>" name="spotRadius" id="spotRadius" />&nbsp;pixels&nbsp(between 1 and 25)
						<p class="description">Set the radius of each spot. Note: This will
						effect the heat value calculation as spots with a greater radius
						are more likely to touch other spots.</p>
					</li>
					<li>
						<label for="spotOpacity" class="smallWidth">Set the spot opacity</label>
						<input type="text" value="<?php echo $current_spotOpacity ?>" name="spotOpacity" id="spotOpacity" />&nbsp(between 0 and 1)
						<p class="description">Set the opacity value of the spots. This is
						the degree of how much of the background you can see where there
						are spots.</p>
					</li>
					<li>
						<input type="button" name="saveChangesBtn" id="saveChangesBtn" class="button-primary" value="<?php esc_attr_e('Save All Changes'); ?>" />
						<input type='submit' name="refreshBtn" id="refreshBtn" value='<?php esc_attr_e('Clear Database'); ?>' class='button-secondary' />
					</li>
				</ul>
				
				<br />
				<hr />
				<h3>URL Filters</h3>
				<p>URL filters can be useful for performance reasons (i.e. reduce server load) and to target specific pages (i.e. Home page only).</p>
				<ul>
					<li>
						<label for="filterType" class="smallWidth">Filter type</label>
						<input type="radio" name="filterType" value="whitelist" <?php if ($current_filterType == HotSpots::WHITELIST_FILTER_TYPE) { ?> checked="checked" <?php } ?> />
						<label for="filterType">Whitelist</label>
						<input type="radio" name="filterType" value="blacklist"  <?php if ($current_filterType == HotSpots::BLACKLIST_FILTER_TYPE) { ?> checked="checked" <?php } ?>/>
						<label for="filterType">Blacklist</label>
						<p class="description">Set a filter type to either include (whitelist) or exclude (blacklist).</p>
					</li>
					<li>
						<label for="urlFilter" class="smallWidth">URL</label>
						<input type="text" name="urlFilter" id="urlFilter" class="mediumWidth" value="<?php echo site_url(); ?>" />
						<input type="submit" name="addFilterBtn" id="addFilterBtn" class="button" value="<?php esc_attr_e('Add URL Filter'); ?>" />
						<p class="description">Enter URL to filter (i.e. http://www.mywebsite.com and http://www.mywebsite.com?cat=1).</p>
					</li>
					<li>
						<?php 
						$filter = new FilterTable();
						$filter->prepare_items();
						$filter->display();
						?>
					</li>
				</ul>
				
				<br />
				<hr />
				<h3>Heap Maps</h3>
				<p>Different heat maps are drawn when you resize the window width, modify zoom levels and device pixel ratios to cater for responsive design. Ensure the enable drawing heat map option is enabled. You can also manually view the heat maps by adding a query parameter drawHeatMap=true to the URL (i.e. http://www.mywebsite.com?drawHeatMap=true and http://www.mywebsite.com?cat=1&drawHeatMap=true).</p>
				
				<?php 
				$heatMapTable = new HeatMapTable();
				$heatMapTable->prepare_items();
				$heatMapTable->display();
				?>
			</form>
		</div>
		<?php 
	}

	/**
	 * Removes a query string parameter from URL
	 * @param $url
	 * @param $param
	 * @return string
	 * 
	 * @since 1.2
	 */
	public function removeQueryStringParams($url, $params) {
		foreach ($params as $param) {
			$url = preg_replace('/(.*)(\?|&)' . $param . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
			$url = substr($url, 0, -1);
		}
		return $url;
	}	
	
	/**
	 * Function to get the client ip address
	 * 
	 * @since 2.1
	 */
	function getIPAddress() {
		$ipaddress = '';
		if ($_SERVER['HTTP_CLIENT_IP'])
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if($_SERVER['HTTP_X_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if($_SERVER['HTTP_X_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if($_SERVER['HTTP_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if($_SERVER['HTTP_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if($_SERVER['REMOTE_ADDR'])
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
	
		return $ipaddress;
	}
}

$hotSpots = new HotSpots();

?>