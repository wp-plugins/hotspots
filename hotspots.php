<?php 
/*
Plugin Name: HotSpots
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: View a heat map of mouse clicks and touch screen taps overlayed on your webpage allowing you to improve usability by analysing user behaviour.
Version: 2.2.3
Author: Daniel Powney
Auhtor URI: www.danielpowney.com
License: GPL2
*/
?>
<?php


require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tables.php';

/**
 * HotSpots plugin class.
 *
 * @since 2.0
 * @author dpowney
 */
class HotSpots {

	// constants
	const
	VERSION 						= '2.2.1',
	DB_VERSION						= '2.2',
	ID					 			= 'hotspots',

	/* Front end */
	HOTSPOTS_DATA 					= 'hotSpotsData',

	// database
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
	CREATED_DATE_COLUMN				= "createdDate",
	
	// options
	SAVE_MOUSE_CLICKS_OPTION 		= 'savedMouseClicks',
	DRAW_HOTSPOTS_ENABLED_OPTION 	= 'drawHotSpotsEnabled',
	DEBUG_OPTION 					= 'debug',
	HOT_VALUE_OPTION 				= 'hotValue',
	SPOT_OPACITY_OPTION 			= 'spotOpacity',
	SPOT_RADIUS_OPTION 				= 'spotRadius',
	FILTER_TYPE_OPTION				= "filterType",
	APPLY_FILTERS_OPTION			= "applyFilters",
	DB_VERSION_OPTION				= "dbVersion",
	IGNORE_ZOOM_LEVEL_OPTION		= "ignoreZoomLevel",
	IGNORE_DEVICE_PIXEL_RATIO_OPTION = "ignoreDevicePixelRatio",
	MAX_CLICKS_AND_TAPS_PER_URL_OPTION = "maxClicksAndTapsPerURL",
	
	// default values
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
	public static $ignore_query_params = array( 'drawHeatMap', 'KEY', 'XDEBUG_SESSION_START' );

	
	/**
	 * Constructor
	 *
	 * @since 2.0
	 */
	function __construct() {

		// Create settings page, add JavaScript and CSS
		if( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'create_settings_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		}

		// Activation hook
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		
		// Uninstall hook
		register_uninstall_hook( __FILE__, array( $this, 'uninstall_plugin' ) );
		
		// No deactivate hook needed

		// Setup AJAX calls
		$this->add_ajax_actions();
	}
	
	
	/**
	 * Activates the plugin by setting up DB tables and adding options
	 * 
	 * @since 2.0
	 */
	function activate_plugin() {
		global $wpdb;	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		// Create database tables if they doe not exist
		$sql1 = "CREATE TABLE " . $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME . " (
				".HotSpots::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
				".HotSpots::X_COLUMN." int(11) NOT NULL,
				".HotSpots::Y_COLUMN." int(11) NOT NULL,
				".HotSpots::URL_COLUMN." varchar(255),
				".HotSpots::WIDTH_COLUMN." int(11),
				".HotSpots::IS_TOUCH_COLUMN." tinyint(1) DEFAULT 0,
				".HotSpots::IP_ADDRESS_COLUMN." varchar(255),
				".HotSpots::ZOOM_LEVEL_COLUMN." double precision DEFAULT 1,
				".HotSpots::DEVICE_PIXEL_RATIO_COLUMN." double precision DEFAULT 1,
				".HotSpots::CREATED_DATE_COLUMN." DATETIME,
				PRIMARY KEY (".HotSpots::ID_COLUMN.")
				) ENGINE=InnoDB AUTO_INCREMENT=1;";
		dbDelta( $sql1 );
	
		$sql2 = "CREATE TABLE ".$wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME." (
				".URL_Filter_Table::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
				".URL_Filter_Table::URL_COLUMN." varchar(255),
				PRIMARY KEY (id)
				) ENGINE=InnoDB AUTO_INCREMENT=1;";
		dbDelta( $sql2 );
		
		// Add options
		add_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION, HotSpots::DEFAULT_SAVE_MOUSE_CLICKS, '', 'yes' );
		add_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, HotSpots::DEFAULT_DRAW_HOTSPOTS_ENABLED, '', 'yes' );
		add_option( HotSpots::DEBUG_OPTION, HotSpots::DEFAULT_DEBUG, '', 'yes' );
		add_option( HotSpots::HOT_VALUE_OPTION, HotSpots::DEFAULT_HOT_VALUE, '', 'yes' );
		add_option( HotSpots::SPOT_OPACITY_OPTION, HotSpots::DEFAULT_SPOT_OPACITY, '', 'yes' );
		add_option( HotSpots::SPOT_RADIUS_OPTION, HotSpots::DEFAULT_SPOT_RADIUS, '', 'yes' );
		add_option( HotSpots::FILTER_TYPE_OPTION, HotSpots::WHITELIST_FILTER_TYPE, '', 'yes' );
		add_option( HotSpots::APPLY_FILTERS_OPTION, HotSpots::DEFAULT_APPLY_FILTERS, '', 'yes' );
		add_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION, false, '', 'yes' );
		add_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION, false, '', 'yes' );
		add_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION, '', '', 'yes' );
		
		// Check if we need to upgrade the database tables
		if (get_option(HotSpots::DB_VERSION_OPTION) != HotSpots::DB_VERSION) {
			
			// Do update logic here
			
			// Now update the version
			update_option(HotSpots::DB_VERSION_OPTION, HotSpots::DB_VERSION);
		}
		
		add_option( HotSpots::DB_VERSION_OPTION, HotSpots::DB_VERSION, '', 'yes' );
		
		// Do a quick clean of the URLs
		$select_all_query = 'SELECT * FROM ' . $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME;
		$all_rows = $wpdb->get_results($select_all_query);
		foreach ($all_rows as $row) {
			$current_id = $row->id;
			$old_url = $row->url;
			$new_url = $this->normalize_url($old_url);
			if ($old_url != $new_url) {
				$update_query = 'UPDATE ' .  $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME . ' SET url = "' . $new_url . '" WHERE id = ' . $current_id;
				$result = $wpdb->query($update_query);
			}
		}
		
	}
	
	
	/**
	 * Uninstall plugin
	 *
	 * @since 2.1.4
	 */
	function uninstall_plugin() {
		// Delete options
		delete_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION) ;
		delete_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION );
		delete_option( HotSpots::DEBUG_OPTION );
		delete_option( HotSpots::HOT_VALUE_OPTION );
		delete_option( HotSpots::SPOT_OPACITY_OPTION );
		delete_option( HotSpots::SPOT_RADIUS_OPTION );
		delete_option( HotSpots::FILTER_TYPE_OPTION );
		delete_option( HotSpots::APPLY_FILTERS_OPTION );
		delete_option( HotSpots::DB_VERSION_OPTION );
		delete_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION );
		delete_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION );
		delete_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION );
		
		// Drop tables
		global $wpdb;
		$wpdb->query( "DROP TABLE IF_EXISTS " . $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME );
		$wpdb->query( "DROP TABLE IF_EXISTS " . $wpdb->prefix . URL_Filter_Table::FILTER_TBL_NAME );
	}
	
	
	/**
	 * Register AJAX call actions
	 *
	 * @since 1.2
	 */
	public function add_ajax_actions() {
		
		if ( is_admin() ) {
			add_action( 'wp_ajax_save_click_or_tap', array( $this, 'save_click_or_tap' ) );
			add_action( 'wp_ajax_retrieve_clicks_and_taps',  array( $this, 'retrieve_clicks_and_taps' ) );
			add_action( 'wp_ajax_save_changes', array( $this, 'save_changes' ) );
			add_action( 'wp_ajax_set_URL_filter_type', array( $this, 'set_URL_filter_type' ) );
			add_action( 'wp_ajax_nopriv_save_click_or_tap',  array( $this, 'save_click_or_tap' ) );
			add_action( 'wp_ajax_nopriv_retrieve_clicks_and_taps',  array( $this, 'retrieve_clicks_and_taps' ) );
			add_action( 'wp_ajax_nopriv_save_changes', array( $this, 'save_changes' ) );
			add_action( 'wp_ajax_nopriv_set_URL_filter_type', array( $this, 'set_URL_filter_type' ) );
		}
		
	}
	
	/**
	 * Javascript and CSS used by the plugin
	 *
	 * @since 2.0
	 */
	public function assets(){
		wp_enqueue_script( 'jquery' );
	
		// TODO change file names to admin.css and add a admin.php
		wp_enqueue_style( 'hotspots-style' , plugins_url( 'hotspots.css', __FILE__ ) );
		wp_enqueue_script( 'detect-zoom', plugins_url( 'detect-zoom.js', __FILE__ ), array(), false, true );
		wp_enqueue_script( HotSpots::ID, plugins_url( 'hotspots.js', __FILE__ ), array( 'jquery', 'detect-zoom' ), HotSpots::VERSION, true );
	
		$draw_heat_map_enabled = get_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION );
		$save_click_or_tap_enabled = get_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION );
	
		// TODO remove query params in normalize URL function
		$current_URL = $this->remove_query_string_params( addslashes( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ), HotSpots::$ignore_query_params );
		$current_URL = $this->normalize_url( $current_URL );
		// ?XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=13631390018391
		
		// Check options if applying filters
		$apply_URL_filters = get_option( HotSpots::APPLY_FILTERS_OPTION );
		// Also check if at least one of the options is true to improve performance
		if ( $apply_URL_filters == true && ( $draw_heat_map_enabled == true || $save_click_or_tap_enabled == true ) ) {
			// check if enabled
			$filter_type = get_option( HotSpots::FILTER_TYPE_OPTION );
	
			global $wpdb;
			$query = 'SELECT ' .URL_Filter_Table::URL_COLUMN . ' FROM ' . $wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME;
			$rows = $wpdb->get_results( $query );
				
			if ( $filter_type == HotSpots::BLACKLIST_FILTER_TYPE ) { // excludes
				foreach  ($rows as $row ) {
					// TODO Do we need to normalize the URL once it is saved to the DB?
					$url = $this->normalize_url( $row->url );
	
					// If it's in the blacklist, we disable the options
					if ( $url == $current_URL ) {
						$draw_heat_map_enabled = 0;
						$save_click_or_tap_enabled = 0;
						break;
					}
				}
			} else { // whitelist (includes)
				// check if the current url is in the whitelist
				$found = false;
				foreach ( $rows as $row ) {
					$url = $this->normalize_url( $row->url );
						
					// If it's not in the whitelist, we disable the options
					if ( $url == $current_URL ) {
						$found = true;
						break;
					}
				}
				if ( $found == false ) {
					$draw_heat_map_enabled = 0;
					$save_click_or_tap_enabled = 0;
				}
			}
		}
	
		// check max clicks and taps per URL option
		$max_clicks_and_taps_per_URL = get_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION );
		if ( $save_click_or_tap_enabled == true && $max_clicks_and_taps_per_URL != '' ) {
			global $wpdb;
			$query = 'SELECT * FROM '. $wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME . ' WHERE ' . HotSpots::URL_COLUMN . ' = "' . $current_URL . '"';
			$wpdb->query( $query );
			$count = $wpdb->num_rows;
			if ( $count >= $max_clicks_and_taps_per_URL ) {
				$save_click_or_tap_enabled = 0;
			}
		}
	
	
		$config_array = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( HotSpots::ID.'-nonce' ),
				'drawHeatMapEnabled' => $draw_heat_map_enabled,
				'saveClickOrTapEnabled' => $save_click_or_tap_enabled,
				'debug' => get_option( HotSpots::DEBUG_OPTION ),
				'hotValue' => get_option( HotSpots::HOT_VALUE_OPTION ),
				'spotOpacity' => get_option( HotSpots::SPOT_OPACITY_OPTION ),
				'spotRadius' => get_option( HotSpots::SPOT_RADIUS_OPTION ),
				'filterType' => get_option( HotSpots::FILTER_TYPE_OPTION ),
		);
		wp_localize_script( HotSpots::ID, HotSpots::HOTSPOTS_DATA, $config_array );
	}
	
	
	/**
	 * Admin assets
	 *
	 * @since 1.2.8
	 */
	public function admin_assets() {
		$config_array = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( HotSpots::ID.'-nonce' )
		);
		wp_enqueue_script( 'jquery' );
	
		if ( is_admin() ) {
			wp_enqueue_style( 'hotspots-admin-style', plugins_url( 'hotspots-admin.css', __FILE__ ) );
			wp_enqueue_script( HotSpots::ID.'-admin', plugins_url( 'hotspots-admin.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( HotSpots::ID.'-admin', HotSpots::HOTSPOTS_DATA, $config_array );
		}
	
	}
	
	/**
	 * Saves mouse click/touch screen tap information and saves to database
	 *
	 * @since 2.0
	 */
	public function save_click_or_tap() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];

		if ( wp_verify_nonce( $ajaxNonce, HotSpots::ID.'-nonce' ) ) {
			$x = 0;
			if ( isset($_POST['x']) )
				$x = intval( $_POST['x'] );
			
			$y = 0;
			if ( isset($_POST['y'] ) )
				$y =  intval( $_POST['y'] );
			
			$url = '';
			if ( isset($_POST['url']) ) {
				// TODO remove query params in normalize URL function
				$url = $this->remove_query_string_params( addslashes( $_POST['url'] ), HotSpots::$ignore_query_params );
				$url = $this->normalize_url( $url );
			}
			
			$width = 0;
			if ( isset($_POST['width'] ) )
				$width = intval($_POST['width']);
			
			$is_tap = false;
			if ( isset($_POST['isTap'] ) )
				$is_tap = ( $_POST['isTap'] == "true" ) ? true : false;
				
			$zoom_level = 1;
			if ( isset($_POST['zoomLevel'] ) )
				$zoom_level = doubleval( $_POST['zoomLevel'] );
				
			$client_IP_address = $this->get_client_IP_address();
				
			$device_pixel_ratio = 1;
			if ( isset($_POST['devicePixelRatio']) )
				$device_pixel_ratio = doubleval( $_POST['devicePixelRatio'] );
			
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HotSpots::HOTSPOTS_TBL_NAME,
					array(
							HotSpots::X_COLUMN => $x,
							HotSpots::Y_COLUMN => $y,
							HotSpots::URL_COLUMN => $url,
							HotSpots::WIDTH_COLUMN => $width,
							HotSpots::ZOOM_LEVEL_COLUMN => $zoom_level,
							HotSpots::IS_TOUCH_COLUMN => $is_tap,
							HotSpots::IP_ADDRESS_COLUMN => $client_IP_address,
							HotSpots::DEVICE_PIXEL_RATIO_COLUMN => $device_pixel_ratio,
							HotSpots::CREATED_DATE_COLUMN => current_time('mysql')
					)
			);
			$id = $wpdb->insert_id;

			// If debug and draw heat map enabled, return the heat value so we can 
			// draw the heat colour of the circle
			$debug = get_option( HotSpots::DEBUG_OPTION );
			$draw_heat_map_enabled = get_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION );
			if ($debug == true && $draw_heat_map_enabled == true) {
				// retrieve all clicks and taps and calculate heat value
				$query = "SELECT " . HotSpots::ID_COLUMN . ", ".HotSpots::X_COLUMN.", ".HotSpots::Y_COLUMN.", "
						. HotSpots::URL_COLUMN.", ".HotSpots::WIDTH_COLUMN." FROM "
						. $wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE ".HotSpots::URL_COLUMN." = '" . $url . "'";
						// TODO make a constant
				$range = 6; // allow a range of 6 pixels either side to be the same
				
				$diff_left = $width - $range;
				$diff_right = $width + $range;
				
				$query .= ' AND '.HotSpots::WIDTH_COLUMN.' >= ' . $diff_left . ' AND '.HotSpots::WIDTH_COLUMN.' <= '. $diff_right;
				
				$rows = $wpdb->get_results($query);
				$heat_value = $this->calculate_heat_value($x, $y, $id, $rows);
				$response = array('id' => $id, 'heatValue' => $heat_value);
			} else {
				$response = array('id' => $id);
			}
				
			echo json_encode($response);
		}
		
		die();
	}
	

	/**
	 * Retrieves all mouse clicks/touch screen taps
	 *
	 * @since 1.0
	 */
	public function retrieve_clicks_and_taps() {
		global $wpdb;
		$ajax_nonce = $_POST['nonce'];
		
		$response_data = array();
		if ( wp_verify_nonce( $ajax_nonce, HotSpots::ID .'-nonce' ) ) {
			$url = '';
			if ( isset($_POST['url']) ) {
				// TODO remove query params in normalize URL function
				$url = $this->remove_query_string_params( addslashes( $_POST['url'] ), HotSpots::$ignore_query_params );
				$url = $this->normalize_url( $url );
			}
			
			$query = "SELECT " . HotSpots::ID_COLUMN . ", ".HotSpots::X_COLUMN.", ".HotSpots::Y_COLUMN.", "
						. HotSpots::URL_COLUMN.", ".HotSpots::WIDTH_COLUMN." FROM "
						. $wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE ".HotSpots::URL_COLUMN." = '" . $url . "'";

			// TODO make a constant
			$range = 6; // allow a range of 6 pixels either side to be the same
			$width = 0;
			if ( isset($_POST['width'] ) )
				$width = intval($_POST['width']);
			$diff_left = $width - $range;
			$diff_right = $width + $range;
			$query .= ' AND '.HotSpots::WIDTH_COLUMN.' >= ' . $diff_left . ' AND '.HotSpots::WIDTH_COLUMN.' <= '. $diff_right;
			
			$ignore_zoom_level = get_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION );			
			if ( $ignore_zoom_level == false ) {
				$zoom_level = 1;
				if ( isset($_POST['zoomLevel'] ) )
					$zoom_level = doubleval( $_POST['zoomLevel'] );
				
				$query .= ' AND ' . HotSpots::ZOOM_LEVEL_COLUMN . ' = ' . $zoom_level;
			}
			
			$ignore_device_pixel_ratio = get_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION );
			if ( $ignore_device_pixel_ratio == false ) {
				$device_pixel_ratio = 1;
				if ( isset($_POST['devicePixelRatio']) )
					$device_pixel_ratio = doubleval( $_POST['devicePixelRatio'] );
				
				$query .= ' AND ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ' = ' . $device_pixel_ratio;
			}
			
			$rows = $wpdb->get_results($query);
				
			$index = 0;
			foreach ($rows as $row) {
				$id = $row->id;
				$x = $row->x;
				$y = $row->y;
				
				// TODO Do we need to normalize the URL once it is saved to the DB?
				$url = $this->normalize_url( $row->url );
				$width = $row->screenWidth;
				$heat_value = $this->calculate_heat_value($x, $y, $id, $rows);
				$response_data[$index++] = array(
						'id' => $id,
						'x' => $x, 
						'y' => $y, 
						'width' => $width, 
						'url' => $url,
						'heatValue' => $heat_value
					);
			}
		}
		
		echo json_encode($response_data);
		
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
	public function calculate_heat_value($x, $y, $id, $rows) {
		$heat_value = 0;
		$spot_radius = get_option( HotSpots::SPOT_RADIUS_OPTION );

		foreach ($rows as $row) {
			$current_x = $row->x;
			$current_y = $row->y;
			$current_id = $row->id;

			// skip if comparing the same click or tap
			if ($id == $current_id) {
				continue;
			}
				
			// Check if the spot is touching other spots
			$diff_x = $x - $current_x;
			$diff_y = $y - $current_y;
			$hot_x = ($diff_x > - $spot_radius && $diff_x < $spot_radius);
			$hot_y = ($diff_y > - $spot_radius && $diff_y < $spot_radius);
			if ($hot_x && $hot_y) {
				$heat_value++;
			}
		}
		return $heat_value;
	}

	
	/**
	 * Save settings changes
	 *
	 * @since 1.2
	 */
	public function save_changes() {

		$errors = "";
		// draw hotspots enabled option
		if ( isset($_POST['drawHotSpotsEnabled']) ) {
			if ( $_POST['drawHotSpotsEnabled'] == "true" ) {
				update_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, true );
			} else {
				update_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION, false );
			}
		}

		// Save mouse clicks option
		if ( isset( $_POST['saveMouseClicks'] ) ) {
			if ($_POST['saveMouseClicks'] == "true") {
				update_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION, true );
			} else {
				update_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION, false );
			}
		}

		// debug option
		if ( isset( $_POST['debug'] ) ) {
			if ( $_POST['debug'] == "true" ) {
				update_option( HotSpots::DEBUG_OPTION, true );
			} else {
				update_option( HotSpots::DEBUG_OPTION, false );
			}
		}
		
		// apply filters option
		if ( isset ($_POST['applyFilters'] ) ) {
			if ( $_POST['applyFilters'] == "true") {
				update_option( HotSpots::APPLY_FILTERS_OPTION, true );
			} else {
				update_option( HotSpots::APPLY_FILTERS_OPTION, false );
			}
		}
		
		// ignore zoom level option
		if ( isset ($_POST['ignoreZoomLevel'] ) ) {
			if ( $_POST['ignoreZoomLevel'] == "true") {
				update_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION, true );
			} else {
				update_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION, false );
			}
		}
		
		// ignore devixe pixel ratio option
		if ( isset ($_POST['ignoreDevicePixelRatio'] ) ) {
			if ( $_POST['ignoreDevicePixelRatio'] == "true") {
				update_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION, true );
			} else {
				update_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION, false );
			}
		}
		
		// max clicks and taps per URL option
		if ( isset( $_POST['maxClicksAndTapsPerURL'] ) ) {
			if ( is_numeric( $_POST['maxClicksAndTapsPerURL'] ) ) {
				$max_clicks_and_taps_per_URL = intval( $_POST['maxClicksAndTapsPerURL'] );
				if ( $max_clicks_and_taps_per_URL > 0 ) {
					update_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION, $max_clicks_and_taps_per_URL );
				} else {
					$errors .= "<p>Max clicks and taps per URL must be numeric greater than 0 or empty.</p>";
				}
			} else {
				if (strlen( trim($_POST['maxClicksAndTapsPerURL'])) == 0)
					update_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION, '' );
				else
					$errors .= "<p>Max clicks and taps per URL must be numeric greater than 0 or empty.</p>";
			}
			
		}
		
		// hot value option
		if ( isset( $_POST['hotValue'] ) ) {
			if ( is_numeric( $_POST['hotValue'] ) ) {
				$hot_value = intval( $_POST['hotValue'] );
				if ( $hot_value > 0 ) {
					update_option( HotSpots::HOT_VALUE_OPTION, $hot_value );
				} else {
					$errors .= "<p>Hot value must be numeric greater than 0.</p>";
				}
			} else {
				$errors .= "<p>Hot value must be numeric greater than 0.</p>";
			}
		}

		// spot opacity option
		if ( isset( $_POST['spotOpacity'] ) ) {
			if ( is_numeric( $_POST['spotOpacity'] ) ) {
				$spot_opacity = floatval( $_POST['spotOpacity'] );
				if ( $spot_opacity >= 0 && $spot_opacity <= 1 ) {
					update_option( HotSpots::SPOT_OPACITY_OPTION, $spot_opacity );
				} else {
					$errors .= "<p>Spot opacity must be numeric between 0 and 1.</p>";
				}
			} else {
				$errors .= "<p>Spot opacity must be numeric between 0 and 1.</p>";
			}
		}

		// spot radius option
		if ( isset( $_POST['spotRadius'] ) ) {
			if ( is_numeric( $_POST['spotRadius'] ) ) {
				$spot_radius = intval( $_POST['spotRadius'] );
				if ( $spot_radius >= 1 && $spot_radius <= 25 ) {
					update_option( HotSpots::SPOT_RADIUS_OPTION, $spot_radius );
				} else {
					$errors .= "<p>Spot radius must be numeric between 1 and 25.</p>";
				}
			} else {
				$errors .= "<p>Spot radius must be numeric between 1 and 25.</p>";
			}
		}

		if ( strlen( $errors ) > 0 ) {
			echo json_encode( array( 'success' => false, 'errors' => $errors ) );
		} else {
			echo json_encode( array( 'success' => true ) );
		}

		die();
	}
	
	
	/**
	 * Sets the URL filter type (either whitelist or blacklist)
	 *
	 * @since 2.1
	 */
	public function set_URL_filter_type() {
	
		$errors = "";
		// filter type option
		if ( isset( $_POST['filterType'] ) ) {
			if ( $_POST['filterType'] == HotSpots::WHITELIST_FILTER_TYPE ) {
				update_option( HotSpots::FILTER_TYPE_OPTION, HotSpots::WHITELIST_FILTER_TYPE );
			} else {
				update_option( HotSpots::FILTER_TYPE_OPTION, HotSpots::BLACKLIST_FILTER_TYPE );
			}
		} else {
			$errors .'<p>Could not set filter type.</p>';
		}
		
		if ( strlen( $errors ) > 0 ) {
			echo json_encode( array( 'success' => false, 'errors' => $errors ) );
		} else {
			echo json_encode( array( 'success' => true) );
		}
		
		die();
	}

	
	/**
	 * Creates the Settings page
	 *
	 * @since 2.0
	 */
	public function create_settings_page() {
		add_options_page( __( 'HotSpots', HotSpots::ID ), __( 'HotSpots', HotSpots::ID ), 'manage_options', HotSpots::ID, array( $this, 'settingsPage' ) );
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
		if ( isset( $_POST['addFilterSubmit'] ) && $_POST['addFilterSubmit'] === "true" ) {
			// Add URL filter
			$url = isset( $_POST['urlFilter'] ) ? trim( $_POST['urlFilter'] ) : '';
			$url = $this->normalize_url( $url );

			if ( strlen( $url ) == 0 ) {
				$errors .= '<p>URL field is missing or invalid.</p>';
			} else {
				$url = addslashes($url);
				
				try {
					// first make sure URL has not already been added
					$query = 'SELECT * FROM '.$wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME . ' where '. URL_Filter_Table::URL_COLUMN .' = "' .$url . '"';
					$count = $wpdb->query($query);
						
					if ($count > 0) {
						$errors .= '<p>URL filter for ' . $url .' already exists.</p>';
					} else {
						$wpdb->flush();
						$results = $wpdb->insert( $wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME, array( 'url' => $url ) );
						$success .= '<p>URL filter added successfully.</p>';
					}
				} catch ( Exception $e ) {
					$errors .= '<p>An error has occured. ' . $e->getMessage() .' </p>';
				}
			}
		} else if ( isset( $_POST['clearDatabaseSubmit'] ) && $_POST['clearDatabaseSubmit'] === "true" ) {
			// Clear database table
			try {
				$rows = $wpdb->get_results( "DELETE FROM ".$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME." WHERE 1" );
				$success .= '<p>Database cleared successfully.</p>';
			} catch ( Exception $e ) {
				$errors .= '<p>An error has occured. ' . $e->getMessage() . '</p>';
			}
		}
		
		// current option values
		$current_draw_heat_map_enabled = get_option( HotSpots::DRAW_HOTSPOTS_ENABLED_OPTION );
		$current_save_click_or_tap_enabled = get_option( HotSpots::SAVE_MOUSE_CLICKS_OPTION );
		$current_debug = get_option( HotSpots::DEBUG_OPTION );
		$current_hot_value = get_option( HotSpots::HOT_VALUE_OPTION );
		$current_spot_opacity = get_option( HotSpots::SPOT_OPACITY_OPTION );
		$current_spot_radius = get_option( HotSpots::SPOT_RADIUS_OPTION );
		$current_filter_type = get_option( HotSpots::FILTER_TYPE_OPTION );
		$current_apply_URL_filters = get_option( HotSpots::APPLY_FILTERS_OPTION );
		$current_ignore_zoom_level = get_option( HotSpots::IGNORE_ZOOM_LEVEL_OPTION );
		$current_ignore_device_pixel_ratio = get_option( HotSpots::IGNORE_DEVICE_PIXEL_RATIO_OPTION );
		$current_max_clicks_and_taps_per_URL = get_option( HotSpots::MAX_CLICKS_AND_TAPS_PER_URL_OPTION );
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
			if ( strlen( $success ) > 0)
				echo '<div class="updated">' . $success . '</div>';
			else if ( strlen( $errors ) > 0 )
				echo '<div class="error">' . $errors . '</div>';
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
						<input type="checkbox" value="<?php echo $current_save_click_or_tap_enabled ?>" name="saveMouseClicks" id="saveMouseClicks" <?php if ($current_save_click_or_tap_enabled) { ?> checked="checked" <?php } ?> />
						<label for="saveMouseClicks">Save mouse clicks and touch screen taps</label><p class="description">Turn on to start recording mouse click and touch screen tap information on your website.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_draw_heat_map_enabled ?>" name="drawHotSpotsEnabled" id="drawHotSpotsEnabled" <?php if ($current_draw_heat_map_enabled) { ?> checked="checked" <?php } ?> />
						<label for="drawHotSpotsEnabled">Enable drawing heat map</label>
						<p class="description">Enable to allow drawing of the heat map overlayed on your website. To manually draw the heat map, add query parameter drawHeatMap=true to the URL (i.e. www.mywebsite.com?drawHeatMap=true or www.mywebsite.com?cat=1&drawHeatMap=true). Your WordPress theme must be HTML5 compliant and your Internet browser must support HTML5 canvas to be able to view the heat map.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_debug ?>" name="debug" id="debug" <?php if ($current_debug) { ?> checked="checked" <?php } ?> />
						<label for="debug">Debug</label>
						<p class="description">Turn on to debug and draw hot spots on every	mouse click and touch screen tap. This option is useful for testing that that the mouse clicks and touch screen taps are being recorded and that the plugin is working as expected.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_apply_URL_filters ?>" name="applyFilters" id="applyFilters" <?php if ($current_apply_URL_filters) { ?> checked="checked" <?php } ?> />
						<label for="applyFilters">Enable URL filters</label>
						<p class="description" class="smallWidth">Turn on to apply the URL filters. See URL Filters section below.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_ignore_zoom_level ?>" name="ignoreZoomLevel" id="ignoreZoomLevel" <?php if ($current_ignore_zoom_level) { ?> checked="checked" <?php } ?> />
						<label for="ignoreZoomLevel">Ignore zoom level</label>
					
						<input type="checkbox" value="<?php echo $current_ignore_device_pixel_ratio ?>" name="ignoreDevicePixelRatio" id="ignoreDevicePixelRatio" <?php if ($current_ignore_device_pixel_ratio) { ?> checked="checked" <?php } ?> />
						<label for="ignoreDevicePixelRatio">Ignore device pixel ratio</label>
						
						<p class="description">You can ignore the zoom level and device pixel ratio data when drawing the heat map. However, note your website likely appears differenly for different device pixel ratios and browser zoom levels.</p>
					</li>
					
					<li>
						<label for="maxClicksAndTapsPerURL" class="smallWidth">Max clicks and taps saved per URL</label>
						<input type="text" value="<?php echo $current_max_clicks_and_taps_per_URL ?>" name="maxClicksAndTapsPerURL" id="maxClicksAndTapsPerURL" />&nbsp;(leave empty for no maximum) 
						<p class="description">Generally, large amounts of data collected over a longer period of time does not statistically provider better results. Therefore, you can limit the number of clicks and taps saved per URL and this also improves the performance of drawing the heat map. This condition is checked on every page load to determine whether to allow saving clicks and taps (so this means that once the maximum is reached, the page has to be reloaded to stop saving the clicks and taps).</p>
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
						<input type="text" value="<?php echo $current_hot_value ?>" name="hotValue" id="hotValue" />&nbsp;(must be greater than 0) 
						<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
					</li>
					<li>
						<label for="spotRadius" class="smallWidth">Set the spot radius</label>
						<input type="text" value="<?php echo $current_spot_radius ?>" name="spotRadius" id="spotRadius" />&nbsp;pixels&nbsp(between 1 and 25)
						<p class="description">Set the radius of each spot. Note: This will
						effect the heat value calculation as spots with a greater radius
						are more likely to touch other spots.</p>
					</li>
					<li>
						<label for="spotOpacity" class="smallWidth">Set the spot opacity</label>
						<input type="text" value="<?php echo $current_spot_opacity ?>" name="spotOpacity" id="spotOpacity" />&nbsp(between 0 and 1)
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
						<label for="urlFilter" class="smallWidth">URL</label>
						<input type="text" name="urlFilter" id="urlFilter" class="mediumWidth" value="<?php echo $this->normalize_url( site_url() ); ?>" />
						<input type="submit" name="addFilterBtn" id="addFilterBtn" class="button" value="<?php esc_attr_e('Add URL Filter'); ?>" />
						<p class="description">Enter URL to filter (i.e. http://www.mywebsite.com and http://www.mywebsite.com?cat=1).</p>
					</li>
					<li>
						<label for="filterType" class="smallWidth">Filter type</label>
						<input type="radio" name="filterType" value="whitelist" <?php if ($current_filter_type == HotSpots::WHITELIST_FILTER_TYPE) { ?> checked="checked" <?php } ?> />
						<label for="filterType">Whitelist</label>
						<input type="radio" name="filterType" value="blacklist"  <?php if ($current_filter_type == HotSpots::BLACKLIST_FILTER_TYPE) { ?> checked="checked" <?php } ?>/>
						<label for="filterType">Blacklist</label>
						<p class="description">Set a filter type to either include (whitelist) or exclude (blacklist).</p>
					</li>
					<li>
						<br />
						<?php 
						$url_filters_table = new URL_Filter_Table();
						$url_filters_table->prepare_items();
						$url_filters_table->display();
						?>
					</li>
				</ul>
				
				<br />
				<hr />
				<h3>Heap Maps</h3>
				<p>Different heat maps are drawn when you resize the window width, modify zoom levels and device pixel ratios to cater for responsive design. Ensure the enable drawing heat map option is enabled. You can also manually view the heat maps by adding a query parameter drawHeatMap=true to the URL (i.e. http://www.mywebsite.com?drawHeatMap=true and http://www.mywebsite.com?cat=1&drawHeatMap=true).</p>
				
				<?php 
				$heat_map_table = new Heat_Map_Table();
				$heat_map_table->prepare_items();
				$heat_map_table->display();
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
	public function remove_query_string_params( $url, $params ) {
		foreach ( $params as $param ) {
			$url = preg_replace( '/(.*)(\?|&)' . $param . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&' );
			$url = substr( $url, 0, -1 );
		}
		return $url;
	}	
	
	/**
	 * Gets the client ip address
	 * 
	 * @since 2.1
	 */
	function get_client_IP_address() {
		$client_IP_address = '';
		if ( $_SERVER['HTTP_CLIENT_IP'] )
			$client_IP_address = $_SERVER['HTTP_CLIENT_IP'];
		else if ( $_SERVER['HTTP_X_FORWARDED_FOR'] )
			$client_IP_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if ( $_SERVER['HTTP_X_FORWARDED'] )
			$client_IP_address = $_SERVER['HTTP_X_FORWARDED'];
		else if ( $_SERVER['HTTP_FORWARDED_FOR'] )
			$client_IP_address = $_SERVER['HTTP_FORWARDED_FOR'];
		else if ( $_SERVER['HTTP_FORWARDED'] )
			$client_IP_address = $_SERVER['HTTP_FORWARDED'];
		else if ( $_SERVER['REMOTE_ADDR'] )
			$client_IP_address = $_SERVER['REMOTE_ADDR'];
	
		return $client_IP_address;
	}
	
	/**
	 * Normalizes the URL (some of the best parts of RFC 3986)
	 *
	 * @param unknown_type $url
	 * @return string
	 */
	public function normalize_url($url) {
		// Process from RFC 3986 http://en.wikipedia.org/wiki/URL_normalization

		// Limiting protocols.
		if ( !parse_url( $url, PHP_URL_SCHEME ) ) {
			$url = 'http://' . $url;
		}
		
		$parsed_url = parse_url( $url );
		if ($parsed_url === false)
			return '';
		
		// user and pass components are ignored
		
		// TODO Removing or adding “www” as the first domain label.
		$host = preg_replace('/^www\./', '', $parsed_url['host']);
	
		// Converting the scheme and host to lower case
		$scheme = strtolower($parsed_url['scheme']);
		$host = strtolower($host);
		
		$path = $parsed_url['path'];
		// TODO Capitalizing letters in escape sequences
		// TODO Decoding percent-encoded octets of unreserved characters
		
		// Removing the default port
		$port = $parsed_url['port'];
		if ($port == 80)
			$port = '';
		
		// Removing the fragment # (do not get fragment component
		
		// Removing directory index (i.e. index.html, index.php)
		$path = str_replace('index.html', '', $path);
		$path = str_replace('index.php', '', $path);
		
		// Adding trailing /
		$path_last_char = $path[strlen($path)-1];
		if ( $path_last_char != '/' )
			$path = $path . '/';
		
		// TODO Removing dot-segments.
		
		// TODO Replacing IP with domain name.
		
		// TODO Removing duplicate slashes
		$path = preg_replace("~\\\\+([\"\'\\x00\\\\])~", "$1", $path);
		
		// construct URL
		$url =  $scheme . '://' . $host . $path;
		
		// Add query params if they exist
		// Sorting the query parameters.
		// Removing unused query variables
		// Removing default query parameters.
		// Removing the "?" when the query is empty.
		$query = $parsed_url['query'];
		if ($query) {
			$query_parts = explode('&', $query);
			$params = array();
			foreach ($query_parts as $param) {
				$items = explode('=', $param, 2);
				$name = $items[0];
				$value = '';
				if (count($items) == 2)
					$value = $items[1];
				$params[$name] = $value;
			}
			ksort($params);
			$count_params = count($params);
			if ($count_params > 0) {
				$url .= '?';
				$index = 0;
				foreach ($params as $name => $value) {
					$url .= $name;
					if (strlen($value) != 0)
						$url .= '=' . $value;
					if ($index++ < ($count_params - 1))
						$url .= '&';
				}
			}
		}
		return $url;
	}
}

$hotSpots = new HotSpots();

?>