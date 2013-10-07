<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'uaparser' . DIRECTORY_SEPARATOR . 'uaparser.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-admin-tables.php';

/**
 * Admin class
 * 
 * @author dpowney
 *
 */
class HUT_Admin {
	
	public $settings_tabs = array();
	
	public $general_settings = array();
	public $heat_map_settings = array();
	public $user_tracking_tabs = array();
	public $schedule_settings = array();
	public $database_settings = array();
	private $url_filter_settings = array();
	
	/**
	 * Constructor
	 *
	 * @since 2.4
	 */
	function __construct() {
		// Settings
		add_action('init', array( &$this, 'load_settings' ) );
		add_action('init', array( &$this, 'start_session'), 1);
		add_action('wp_logout', array( &$this, 'end_session') );
		add_action('wp_login', array( &$this, 'end_session') );
		
		add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_url_filter_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_heat_map_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_database_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_schedule_settings' ) );
		
		$this->settings_tabs[HUT_Common::GENERAL_SETTINGS_TAB] = 'General';
		$this->settings_tabs[HUT_Common::HEAT_MAP_SETTINGS_TAB] = 'Heat Map';
		$this->settings_tabs[HUT_Common::SCHEDULE_SETTINGS_TAB] = 'Schedule';
		$this->settings_tabs[HUT_Common::URL_FILTER_SETTINGS_TAB] = 'URL Filters';
		$this->settings_tabs[HUT_Common::DATABASE_SETTINGS_TAB] = 'Database';
		
		$this->user_tracking_tabs[HUT_Common::HEAT_MAPS_TAB] = 'Heat Maps';
		
		// Create settings page, add JavaScript and CSS
		if( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		}
		
		$this->user_tracking_tabs[HUT_Common::USERS_TAB] = 'Users';
		$this->user_tracking_tabs[HUT_Common::USER_ACTIVITY_TAB] = 'User Activity';
		$this->settings_tabs[HUT_Common::ELEMENT_SETTINGS_TAB] = 'Elements';
		$this->user_tracking_tabs[HUT_Common::REPORTS_TAB] = 'Reports';
		// TODO $this->plugin_settings_tabs[HUT_Common::STATISTICS_TAB] = 'Stats';
		
	
		// Setup AJAX calls
		$this->add_ajax_actions();
	}
	
	/**
	 * Start session
	 */
	function start_session() {
		if(!session_id()) {
			session_start();
		}
	}
	
	/**
	 * End session
	 */
	function end_session() {
		session_destroy ();
	}

	/**
	 * Activates the plugin by setting up DB tables and adding options
	 *
	 */
	public static function activate_plugin() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		// Create database tables
		$create_click_tap_tbl_query = 'CREATE TABLE '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' (
		'.HUT_Common::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_Common::X_COLUMN.' int(11) NOT NULL,
		'.HUT_Common::Y_COLUMN.' int(11) NOT NULL,
		'.HUT_Common::URL_COLUMN.' varchar(255),
		'.HUT_Common::WIDTH_COLUMN.' int(11),
		'.HUT_Common::IS_TAP_COLUMN.' tinyint(1) DEFAULT 0,
		'.HUT_Common::IP_ADDRESS_COLUMN.' varchar(255),
		'.HUT_Common::ZOOM_LEVEL_COLUMN.' double precision DEFAULT 1,
		'.HUT_Common::DEVICE_PIXEL_RATIO_COLUMN.' double precision DEFAULT 1,
		'.HUT_Common::CREATED_DATE_COLUMN.' DATETIME,
		'.HUT_Common::SESSION_ID_COLUMN.' varchar(255),
		'.HUT_Common::ROLE_COLUMN.' varchar(255) DEFAULT "",
		'.HUT_Common::USER_LOGIN.' varchar(255) DEFAULT "",
		browser_family varchar(255) DEFAULT "",
		browser_version varchar(255) DEFAULT "",
		device varchar(255) DEFAULT "",
		os_family varchar(255) DEFAULT "",
		os_version varchar(255) DEFAULT "",
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_click_tap_tbl_query );
	
		$create_url_filters_tbl_query = 'CREATE TABLE '.$wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME.' (
		'.HUT_URL_Filter_Table::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_URL_Filter_Table::URL_COLUMN.' varchar(255),
		PRIMARY KEY  (id)
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_url_filters_tbl_query );
		
		// URL ping table
		$create_url_ping_tbl = 'CREATE TABLE '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME.' (
		'.HUT_Common::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_Common::URL_COLUMN.' varchar(255),
		'.HUT_Common::IP_ADDRESS_COLUMN.' varchar(255),
		'.HUT_Common::RECORD_DATE_COLUMN.' DATETIME,
		'.HUT_Common::SESSION_ID_COLUMN.' varchar(255),
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_url_ping_tbl );
		
		// AJAX ping table
		$create_ajax_ping_tbl = 'CREATE TABLE '.$wpdb->prefix.HUT_Common::AJAX_PING_TBL_NAME.' (
		'.HUT_Common::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_Common::URL_COLUMN.' varchar(255),
		'.HUT_Common::IP_ADDRESS_COLUMN.' varchar(255),
		'.HUT_Common::RECORD_DATE_COLUMN.' DATETIME,
		'.HUT_Common::AJAX_ACTION_COLUMN.' varchar(100),
		'.HUT_Common::STATUS_TEXT_COLUMN.' varchar(100),
		'.HUT_Common::SESSION_ID_COLUMN.' varchar(255),
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_ajax_ping_tbl );
		
		// Element table
		$element_selector_tbl = 'CREATE TABLE '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME.' (
		'.HUT_Common::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_Common::ELEMENT_SELECTOR_COLUMN.' varchar(255),
		'.HUT_Common::URL_COLUMN.' varchar(255),
		'.HUT_Common::NAME_COLUMN.' varchar(255),
		'.HUT_Common::IS_FORM_SUBMIT_COLUMN. ' tinyint(1) DEFAULT 0,
		'.HUT_Common::IS_TAP_COLUMN.' tinyint(1) DEFAULT 0,
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $element_selector_tbl );
		
		// Element ping table
		$element_selector_ping_tbl = 'CREATE TABLE '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME.' (
		'.HUT_Common::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_Common::ELEMENT_SELECTOR_COLUMN.' varchar(255),
		'.HUT_Common::URL_COLUMN.' varchar(255),
		'.HUT_Common::IP_ADDRESS_COLUMN.' varchar(255),
		'.HUT_Common::RECORD_DATE_COLUMN.' DATETIME,
		'.HUT_Common::SESSION_ID_COLUMN.' varchar(255),
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $element_selector_ping_tbl );		
		
		add_option( HUT_Common::PLUGIN_VERSION_OPTION, HUT_Common::PLUGIN_VERSION, '', 'yes' );
	}
	
	/**
	 * Uninstall plugin
	 *
	 */
	public static function uninstall_plugin() {
		// Delete options
		delete_option( HUT_Common::GENERAL_SETTINGS_KEY ) ;
		delete_option( HUT_Common::URL_FILTERS_SETTINGS_KEY );
		delete_option( HUT_Common::HEAT_MAP_SETTINGS_KEY );
		delete_option( HUT_Common::SCHEDULE_SETTINGS_KEY );
		delete_option( HUT_Common::DATABASE_SETTINGS_KEY );
		
		// Plugin version
		delete_option( HUT_Common::PLUGIN_VERSION_OPTION );
	
		// Drop tables
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::CLICK_TAP_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_URL_Filter_Table::URL_FILTER_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::URL_PING_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::AJAX_PING_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::ELEMENT_SELECTOR_TBL_NAME );
	}
	
	/**
	 * Retrieve settings from DB and sets default options if not set
	 */
	function load_settings() {
		$this->general_settings = (array) get_option( HUT_Common::GENERAL_SETTINGS_KEY );
		$this->url_filter_settings = (array) get_option( HUT_Common::URL_FILTERS_SETTINGS_KEY );
		$this->heat_map_settings = (array) get_option( HUT_Common::HEAT_MAP_SETTINGS_KEY );
		$this->database_settings = (array) get_option( HUT_Common::DATABASE_SETTINGS_KEY );
		$this->schedule_settings = (array) get_option( HUT_Common::SCHEDULE_SETTINGS_KEY );
		
		// Merge with defaults
		$this->general_settings = array_merge( array(
				HUT_Common::SAVE_CLICK_TAP_OPTION => true,
				HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION => true,
				HUT_Common::DEBUG_OPTION => false,
				HUT_Common::SAVE_AJAX_ACTIONS_OPTION => true,
				HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION => true,
				HUT_Common::SAVE_PAGE_LOADS_OPTION => true
		), $this->general_settings );
		
		$this->schedule_settings = array_merge( array(
				HUT_Common::SCHEDULED_START_DATE_OPTION => '',
				HUT_Common::SCHEDULED_END_DATE_OPTION => '',
		), $this->schedule_settings );
		
		$this->database_settings = array_merge( array(
				HUT_Common::URL_DB_LIMIT_OPTION => ''
		), $this->database_settings );
		
		$this->heat_map_settings = array_merge( array(
				HUT_Common::USE_HEATMAPJS_OPTION => false,
				HUT_Common::HOT_VALUE_OPTION => 20,
				HUT_Common::SPOT_OPACITY_OPTION => 0.2,
				HUT_Common::SPOT_RADIUS_OPTION => 8,
				HUT_Common::IGNORE_ZOOM_LEVEL_OPTION => false,
				HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION => false,
				HUT_Common::IGNORE_WIDTH_OPTION => false,
				HUT_Common::IGNORE_DEVICE_OPTION => false,
				HUT_Common::IGNORE_OS_FAMILY_OPTION => false,
				HUT_Common::IGNORE_BROWSER_FAMILY_OPTION => false,
				HUT_Common::WIDTH_ALLOWANCE_OPTION => 6,
				HUT_Common::HIDE_ROLES_OPTION => null
		), $this->heat_map_settings );
		
		$this->url_filter_settings = array_merge( array(
				HUT_Common::APPLY_URL_FILTERS_OPTION => false,
				HUT_Common::FILTER_TYPE_OPTION => 'whitelist'
		), $this->url_filter_settings );
		
		update_option(HUT_Common::GENERAL_SETTINGS_KEY, $this->general_settings);
		update_option(HUT_Common::SCHEDULE_SETTINGS_KEY, $this->schedule_settings);
		update_option(HUT_Common::DATABASE_SETTINGS_KEY, $this->database_settings);
		update_option(HUT_Common::HEAT_MAP_SETTINGS_KEY, $this->heat_map_settings);
		update_option(HUT_Common::URL_FILTERS_SETTINGS_KEY, $this->url_filter_settings);
	}
	
	/**
	 * Register the General settings
	 */
	function register_general_settings() {	
		register_setting( HUT_Common::GENERAL_SETTINGS_KEY, HUT_Common::GENERAL_SETTINGS_KEY, array( &$this, 'sanitize_general_settings' ) );
		
		add_settings_section( 'section_general', 'General Settings', array( &$this, 'section_general_desc' ), HUT_Common::GENERAL_SETTINGS_KEY );
		
		add_settings_field( HUT_Common::SAVE_CLICK_TAP_OPTION, 'Save mouse clicks and touch screen taps', array( &$this, 'field_save_click_tap' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION, 'Enable drawing heat map', array( &$this, 'field_draw_heat_map_enabled' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::DEBUG_OPTION, 'Debug', array( &$this, 'field_debug' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );

		add_settings_field( HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION, 'Save element selectors', array( &$this, 'field_save_element_selectors' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::SAVE_AJAX_ACTIONS_OPTION, 'Save AJAX actions', array( &$this, 'field_save_ajax_actions' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::SAVE_PAGE_LOADS_OPTION, 'Save page loads', array( &$this, 'field_save_page_loads' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		
	}
	
	/**
	 * General settings description
	 */
	function section_general_desc() {
		echo "";
	}
	
	/**
	 * General settings fields
	 */
	function field_save_click_tap() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::SAVE_CLICK_TAP_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::SAVE_CLICK_TAP_OPTION], true); ?>/>
		<p class="description">Turn on to start recording mouse click and touch screen tap information on your website.</p>
		<?php
	}
	function field_draw_heat_map_enabled() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION], true ); ?>/>
		<p class="description">Enable to allow drawing of the heat map overlayed on your website. To manually draw the heat map, add query parameter drawHeatMap=true to the URL (i.e. www.mywebsite.com?drawHeatMap=true or www.mywebsite.com?cat=1&drawHeatMap=true). Your WordPress theme must be HTML5 compliant and your Internet browser must support HTML5 canvas to be able to view the heat map.</p>
		<?php 
	}
	function field_debug() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::DEBUG_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::DEBUG_OPTION], true); ?>/>
		<p class="description">Turn on to debug and draw hot spots on every	mouse click and touch screen tap. This option is useful for testing that that the mouse clicks and touch screen taps are being recorded and that the plugin is working as expected.</p>
		<?php 
	}	
		
	function field_save_ajax_actions() {
	?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::SAVE_AJAX_ACTIONS_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::SAVE_AJAX_ACTIONS_OPTION], true); ?>/>
		<p class="description">Turn on to start recording AJAX calls.</p>
		<?php
	}
	function field_save_element_selectors() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION], true); ?>/>
		<p class="description">Turn on to start recording element selectors.</p>
		<?php
	}
	function field_save_page_loads() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::GENERAL_SETTINGS_KEY; ?>[<?php echo HUT_Common::SAVE_PAGE_LOADS_OPTION; ?>]" value="true" <?php checked(true, $this->general_settings[HUT_Common::SAVE_PAGE_LOADS_OPTION], true); ?>/>
		<p class="description">Turn on to start recording page loads.</p>
		<?php
	}
		
	/**
	 * Sanitize and validate General settings
	 * 
	 * @param unknown_type $input
	 * @return boolean
	 */
	function sanitize_general_settings($input) {
	
		// Save click tap option
		if ( isset( $input[HUT_Common::SAVE_CLICK_TAP_OPTION] ) && $input[HUT_Common::SAVE_CLICK_TAP_OPTION] == "true")
			$input[HUT_Common::SAVE_CLICK_TAP_OPTION] = true;
		else
			$input[HUT_Common::SAVE_CLICK_TAP_OPTION] = false;
		
		// draw heat map enabled option
		if ( isset( $input[HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION] ) && $input[HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION] == true)
			$input[HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION] = true;
		else
			$input[HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION] = false;
		
		// debug option
		if ( isset( $input[HUT_Common::DEBUG_OPTION] ) && $input[HUT_Common::DEBUG_OPTION] == "true")
			$input[HUT_Common::DEBUG_OPTION] = true;
		else 
			$input[HUT_Common::DEBUG_OPTION] = false;
		
		// Save AJAX actions option
		if ( isset( $input[HUT_Common::SAVE_AJAX_ACTIONS_OPTION] ) && $input[HUT_Common::SAVE_AJAX_ACTIONS_OPTION] == "true")
			$input[HUT_Common::SAVE_AJAX_ACTIONS_OPTION] = true;
		else
			$input[HUT_Common::SAVE_AJAX_ACTIONS_OPTION] = false;
		
		// Save element selectors option
		if ( isset( $input[HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION] ) && $input[HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION] == "true")
			$input[HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION] = true;
		else
			$input[HUT_Common::SAVE_ELEMENT_SELECTORS_OPTION] = false;
		
		// Save page loads option
		if ( isset( $input[HUT_Common::SAVE_PAGE_LOADS_OPTION] ) && $input[HUT_Common::SAVE_PAGE_LOADS_OPTION] == "true")
			$input[HUT_Common::SAVE_PAGE_LOADS_OPTION] = true;
		else
			$input[HUT_Common::SAVE_PAGE_LOADS_OPTION] = false;
		
		return $input;
	}

	/**
	 * Register the Schedule settings
	 */
	function register_schedule_settings() {
		register_setting( HUT_Common::SCHEDULE_SETTINGS_KEY, HUT_Common::SCHEDULE_SETTINGS_KEY, array( &$this, 'sanitize_schedule_settings' ) );
	
		add_settings_section( 'section_schedule', 'Schedule Settings', array( &$this, 'section_schedule_desc' ), HUT_Common::SCHEDULE_SETTINGS_KEY );
	
		add_settings_field( HUT_Common::SCHEDULED_START_DATE_OPTION, 'Scheduled start date & time', array( &$this, 'field_scheduled_start_date' ), HUT_Common::SCHEDULE_SETTINGS_KEY, 'section_schedule' );
		add_settings_field( HUT_Common::SCHEDULED_END_DATE_OPTION, 'Scheduled end date & time', array( &$this, 'field_scheduled_end_date' ), HUT_Common::SCHEDULE_SETTINGS_KEY, 'section_schedule' );
	}
	
	/**
	 * Schedule settings description
	 */
	function section_schedule_desc() {
	}
	
	/**
	 * Schedule settings fields
	 */
	function field_scheduled_start_date() {
		// from server or to user - get_date_from_gmt
		// from user or to server  	get_gmt_from_date
		$scheduled_start_date = $this->schedule_settings[HUT_Common::SCHEDULED_START_DATE_OPTION];
		$scheduled_start_time_part = '00:00';
		$scheduled_start_date_part = '';
		if (isset($scheduled_start_date) && ! empty ($scheduled_start_date)) {
			$date_parts = preg_split("/\s/", get_date_from_gmt($scheduled_start_date));
			if (count($date_parts) == 2) {
				$scheduled_start_date_part = $date_parts[0];
				$time_parts = preg_split("/:/", $date_parts[1]);
				if (count($time_parts) >= 2)
					$scheduled_start_time_part = $time_parts[0] . ':' . $time_parts[1];
			}
		}
		?>
		<input type="text" class="date-field" name="<?php echo HUT_Common::SCHEDULE_SETTINGS_KEY; ?>[<?php echo HUT_Common::SCHEDULED_START_DATE_OPTION; ?>]" value="<?php echo $scheduled_start_date_part; ?>" />&nbsp;(yyyy-MM-dd)<br />
		<input type="text" class="time-field" name="scheduled_start_time_part" value="<?php echo $scheduled_start_time_part; ?>" />&nbsp;(HH:mm - 24 hour format)
		<p class="description">Schedule a start date and time to save mouse clicks and touch screen taps. Leave date input empty to turn off. If turned on, the save mouse clicks and touch screen taps option is ignored until the scheduled start date passes. This option must be enabled for the scheduling to work. The timezone can be configured from the WordPress Settings -> General.</p>
		<?php
	}
	function field_scheduled_end_date() {
		// from server or to user - get_date_from_gmt
		// from user or to server  	get_gmt_from_date
		$scheduled_end_date = $this->schedule_settings[HUT_Common::SCHEDULED_END_DATE_OPTION];
		$scheduled_end_time_part = '23:59';
		$scheduled_end_date_part = '';
		if (isset($scheduled_end_date) && ! empty ($scheduled_end_date)) {
			$date_parts = preg_split("/\s/", get_date_from_gmt($scheduled_end_date));
			if (count($date_parts) == 2) {
				$scheduled_end_date_part = $date_parts[0];
				$time_parts = preg_split("/:/", $date_parts[1]);
				if (count($time_parts) >= 2)
					$scheduled_end_time_part = $time_parts[0] . ':' . $time_parts[1];
			}
		}
		?>
		<input type="text" class="date-field" name="<?php echo HUT_Common::SCHEDULE_SETTINGS_KEY; ?>[<?php echo HUT_Common::SCHEDULED_END_DATE_OPTION; ?>]" value="<?php echo $scheduled_end_date_part ?>" />&nbsp;(yyyy-MM-dd)<br />
		<input type="text" class="time-field" name="scheduled_end_time_part" value="<?php echo $scheduled_end_time_part; ?>" />&nbsp;(HH:mm - 24 hour format)
		<p class="description">Schedule an end date and time to save mouse clicks and touch screen taps. Leave date input empty to turn off. If turned on, the save mouse clicks and touch screen taps option is ignored once the scheduled end date passes. This option must be enabled for the scheduling to work. The timezone can be configured from the WordPress Settings -> General.</p>
		<?php
	}	
	/**
	 * Sanitize and validate Schedule settings
	 *
	 * @param unknown_type $input
	 * @return boolean
	 */
	function sanitize_schedule_settings($input) {
		// from server or to user - get_date_from_gmt
		// from user or to server  	get_gmt_from_date
		$schedule_start_date = null;
		if (isset( $input[HUT_Common::SCHEDULED_START_DATE_OPTION]) && strlen($input[HUT_Common::SCHEDULED_START_DATE_OPTION]) > 0) {
			if (HUT_Common::check_date_format($input[HUT_Common::SCHEDULED_START_DATE_OPTION]) == false) {
				add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'schedule_start_date_error', 'Scheduled start date invalid format', 'error');
				$input[HUT_Common::SCHEDULED_START_DATE_OPTION] = '';
			} else {
				list($year, $month, $day) = explode('-', $input[HUT_Common::SCHEDULED_START_DATE_OPTION]);// default yyyy-mm-dd format
	
				// add time part
				$scheduled_start_time_part = $_POST['scheduled_start_time_part'];
				$hour = 0;
				$minute = 0;
				if ( ! preg_match("/([01]?[0-9]|2[0-3]):([0-5][0-9])/", $scheduled_start_time_part)) {
					add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'scheduled_start_time_part_invalid_format_error', 'Invalid scheduled start time format. Time must be in 24 hour format HH:mm (i.e. 12:30).' , 'error');
					// Default to 0, 0, 0
				} else {
					// set time parts
					list($hour, $minute) = explode(':', $scheduled_start_time_part);
				}
	
				$schedule_start_date = get_gmt_from_date( date("Y-m-d H:i:s", gmmktime($hour, $minute, 0, $month, $day, $year) ) );
				$today = get_gmt_from_date( get_date_from_gmt( date("Y-m-d H:i:s") ) );
	
				if (strtotime($schedule_start_date) <= strtotime($today)) {
					add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'schedule_start_date_past_error', 'Scheduled start date must be in the future', 'error');
					$input[HUT_Common::SCHEDULED_START_DATE_OPTION] = '';
				}
	
				$input[HUT_Common::SCHEDULED_START_DATE_OPTION] = $schedule_start_date;
			}
		} else {
			$input[HUT_Common::SCHEDULED_START_DATE_OPTION] = "";
		}
	
		if (isset( $input[HUT_Common::SCHEDULED_END_DATE_OPTION]) && strlen($input[HUT_Common::SCHEDULED_END_DATE_OPTION]) > 0) {
			if (HUT_Common::check_date_format($input[HUT_Common::SCHEDULED_END_DATE_OPTION]) == false) {
				add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'schedule_end_date_error', 'Scheduled end date invalid format', 'error');
				$input[HUT_Common::SCHEDULED_START_DATE_OPTION] = '';
			} else {
				list($year, $month, $day) = explode('-', $input[HUT_Common::SCHEDULED_END_DATE_OPTION]);// default yyyy-mm-dd format
	
				// add time part
				$scheduled_end_time_part = $_POST['scheduled_end_time_part'];
				$hour = 23;
				$minute = 59;
				if ( ! preg_match("/([01]?[0-9]|2[0-3]):([0-5][0-9])/", $scheduled_end_time_part)) {
					add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'scheduled_end_time_part_invalid_format_error', 'Invalid scheduled end time format. Time must be in 24 hour format HH:mm (i.e. 12:30).' , 'error');
					// Default to 0, 0, 0
				} else {
					// set time parts
					list($hour, $minute) = explode(':', $scheduled_end_time_part);
				}
	
	
				$schedule_end_date = get_gmt_from_date(date("Y-m-d H:i:s", gmmktime($hour, $minute, 0, $month, $day, $year) ) );
				$today = get_gmt_from_date( get_date_from_gmt( date("Y-m-d H:i:s") ) );
	
				if (strtotime($schedule_end_date) <= strtotime($today)) {
					add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'schedule_end_date_past_error', 'Scheduled end date must be in the future', 'error');
					$input[HUT_Common::SCHEDULED_END_DATE_OPTION] = '';
				} else if ($schedule_start_date != null && strtotime($schedule_end_date) <= strtotime($schedule_start_date)) {
					add_settings_error( HUT_Common::SCHEDULE_SETTINGS_KEY, 'schedule_end_date_after_start_date_error', 'Scheduled end date must be after the scheduled start date', 'error');
					$input[HUT_Common::SCHEDULED_END_DATE_OPTION] = '';
				}
	
				$input[HUT_Common::SCHEDULED_END_DATE_OPTION] = $schedule_end_date;
			}
		} else {
			$input[HUT_Common::SCHEDULED_END_DATE_OPTION] = "";
		}
	
		return $input;
	}
	
	/** 
	 * Register the Heat Map settings
	 */
	function register_heat_map_settings() {
	
		register_setting( HUT_Common::HEAT_MAP_SETTINGS_KEY, HUT_Common::HEAT_MAP_SETTINGS_KEY, array( &$this, 'sanitize_heat_map_settings' ) );
		
		add_settings_section( 'section_heat_map', 'Heat Map Settings', array( &$this, 'section_heat_map_desc' ), HUT_Common::HEAT_MAP_SETTINGS_KEY );
		add_settings_field( HUT_Common::USE_HEATMAPJS_OPTION, 'Use heatmap.js', array( &$this, 'field_heatmapjs' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::HOT_VALUE_OPTION, 'Hot value', array( &$this, 'field_hot_value' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::SPOT_RADIUS_OPTION, 'Spot radius', array( &$this, 'field_spot_radius' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::SPOT_OPACITY_OPTION, 'Spot opacity', array( &$this, 'field_spot_opacity' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_WIDTH_OPTION, 'Ignore width', array( &$this, 'field_ignore_width' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_ZOOM_LEVEL_OPTION, 'Ignore zoom level', array( &$this, 'field_ignore_zoom_level' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION, 'Ignore device pixel ratio', array( &$this, 'field_ignore_device_pixel_ratio' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_DEVICE_OPTION, 'Ignore device', array( &$this, 'field_ignore_device' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_BROWSER_FAMILY_OPTION, 'Ignore browser', array( &$this, 'field_ignore_browser_family' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::IGNORE_OS_FAMILY_OPTION, 'Ignore operating system', array( &$this, 'field_ignore_os_family' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::WIDTH_ALLOWANCE_OPTION, 'Width allowance', array( &$this, 'field_width_allowance' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );
		add_settings_field( HUT_Common::HIDE_ROLES_OPTION, 'Hide roles', array( &$this, 'field_hide_roles' ), HUT_Common::HEAT_MAP_SETTINGS_KEY, 'section_heat_map' );

	}
	/**
	 * Heat map settings description
	 */
	function section_heat_map_desc() {
		?>
		<?php 
	}
	/** 
	 * Heat map settings fields
	 */
	function field_heatmapjs() {
		?>
			<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::USE_HEATMAPJS_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::USE_HEATMAPJS_OPTION], true); ?> />
			<p class="description">Use <a href="http://www.patrick-wied.at/static/heatmapjs/">heatmap.js</a> library to draw the heat map. Otherwise, plot the mouse clicks and touch screen taps with coloured heat values.</p>
			<?php 
		}
	function field_hot_value() {
		?>
		<input type="text" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::HOT_VALUE_OPTION; ?>]" value="<?php echo esc_attr( $this->heat_map_settings[HUT_Common::HOT_VALUE_OPTION] ); ?>" />&nbsp;(must be greater than 0)
		<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
		<?php
	}
	function field_spot_radius() {
		?>
		<input type="text" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::SPOT_RADIUS_OPTION; ?>]" value="<?php echo esc_attr( $this->heat_map_settings[HUT_Common::SPOT_RADIUS_OPTION] ); ?>" />&nbsp;(between 1 and 25)
		<p class="description">Set the radius of each spot. Note: This will effect the heat value calculation as spots with a greater radius are more likely to touch other spots.</p>
		<?php
	}
	function field_spot_opacity() {
		?>
		<input type="text" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::SPOT_OPACITY_OPTION; ?>]" value="<?php echo esc_attr( $this->heat_map_settings[HUT_Common::SPOT_OPACITY_OPTION] ); ?>" />&nbsp;(between 0.0 and 1.0)
		<p class="description">Set the opacity value of the spots. This is the degree of how much of the background you can see where there are spots.</p>
		<?php
	}
	function field_ignore_zoom_level() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_ZOOM_LEVEL_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION], true); ?> />
		<p class="description">You can ignore the zoom level data when drawing the heat map. However, note your website likely appears differently for browser zoom levels.</p>
		<?php 
	}
	function field_ignore_width() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_WIDTH_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_WIDTH_OPTION], true); ?> />
		<p class="description">You can ignore the width data when drawing the heat map. However, note your website likely appears differently for widths and responsive design.</p>
		<?php 
	}
	function field_ignore_device_pixel_ratio() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION], true ); ?>/>
		<p class="description">You can ignore the device pixel ratio data when drawing the heat map. However, note your website likely appears differently for different device pixel ratios.</p>
		<?php 
	}
	function field_ignore_device() {
	?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_DEVICE_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_DEVICE_OPTION], true); ?> />
		<p class="description">You can ignore the device when drawing the heat map.</p>
		<?php 
	}
	function field_ignore_os_family() {
	?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_OS_FAMILY_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_OS_FAMILY_OPTION], true); ?> />
		<p class="description">You can ignore the operating system when drawing the heat map.</p>
		<?php 
	}
	function field_ignore_browser_family() {
	?>
		<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_BROWSER_FAMILY_OPTION; ?>]" value="true" <?php checked(true, $this->heat_map_settings[HUT_Common::IGNORE_BROWSER_FAMILY_OPTION], true); ?> />
		<p class="description">You can ignore the browser when drawing the heat map.</p>
		<?php 
	}
	function field_width_allowance() {
	?>
		<input type="text" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::WIDTH_ALLOWANCE_OPTION; ?>]" value="<?php echo esc_attr( $this->heat_map_settings[HUT_Common::WIDTH_ALLOWANCE_OPTION] ); ?>" />&nbsp;pixels (between 0 and 20)
		<p class="description">An allowance to the width when drawing the heat map. This saves time when adjusting the width to draw a heat map as the width does not need to be exact (i.e if the width allowance is 6 pixels and the heat map width is 1600 pixels, then all clicks and taps within width of 1594 pixels to 1606 pixels will also be drawn on the heat map). Note: the larger the width allowance, the less accurate the placement of the clicks and taps on the heat map will likely be.</p>
		<?php
	}
	function field_hide_roles() {
		global $wp_roles;
		if ( ! isset( $wp_roles) )
			$wp_roles = new WP_Roles();
	
		$roles = $wp_roles->get_names();
		// add None to the array of role non logged in users or visitors who do not have a role
		$roles[HUT_Common::NO_ROLE_VALUE] = "None";
		
		$hide_roles = $this->heat_map_settings[HUT_Common::HIDE_ROLES_OPTION];
		
		echo '<p>';
		foreach ($roles as $role_value => $role_name) { ?>
			<input type="checkbox" name="<?php echo HUT_Common::HEAT_MAP_SETTINGS_KEY; ?>[<?php echo HUT_Common::HIDE_ROLES_OPTION; ?>][]" value="<?php echo $role_value ?>" <?php
			if (is_array($hide_roles)) {
					if (in_array($role_value, $hide_roles)) {
						echo 'checked="checked"';
					}
			} else {
				checked(true, $this->heat_map_settings[HUT_Common::HIDE_ROLES_OPTION], true );
			}
			echo ' />&nbsp;<label class="hut_role">' . $role_name . '</label>'; 
		}
		
		echo '</p>';
		echo '<p class="description">You can hide mouse clicks and touch screen taps of users from specific roles from being displayed on the heat maps. None is for all non logged in users or visitors who do not have a role.</p>';
	}
	/**
	 * Sanitize and validate heat map settings
	 * 
	 * @param unknown_type $input
	 */
	function sanitize_heat_map_settings($input) {
		
		// use heatmap.js option
		if ( isset( $input[HUT_Common::USE_HEATMAPJS_OPTION] ) && $input[HUT_Common::USE_HEATMAPJS_OPTION] == "true")
			$input[HUT_Common::USE_HEATMAPJS_OPTION] = true;
		else
			$input[HUT_Common::USE_HEATMAPJS_OPTION] = false;
		
		// Width allowance option
		if ( isset( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] ) ) {
				$width_allowance = intval( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] );
				if ( $width_allowance < 0 || $width_allowance > 20) {
					add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'width_allowance_range_error', 'Width allowance must be numeric between 0 and 20.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'width_allowance_format_error', 'Width allowance must be numeric between 0 and 20.', 'error');
			}
		
		}
		
		// hot value option
		if ( isset( $input[HUT_Common::HOT_VALUE_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::HOT_VALUE_OPTION] ) ) {
				$hot_value = intval( $input[HUT_Common::HOT_VALUE_OPTION] );
				if ( $hot_value <= 0 ) {
					add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'hot_value_range_error', 'Hot value must be numeric greater than 0.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'hot_value_non_numeric_error', 'Hot value must be numeric greater than 0.', 'error');
			}
		}
		
		// spot opacity option
		if ( isset( $input[HUT_Common::SPOT_OPACITY_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::SPOT_OPACITY_OPTION] ) ) {
				$spot_opacity = floatval( $input[HUT_Common::SPOT_OPACITY_OPTION] );
				if ( $spot_opacity < 0 || $spot_opacity > 1 ) {
					add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'spot_opacity_range_error', 'Spot opacity must be numeric between 0 and 1.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'spot_opacity_non_numeric_error', 'Spot opacity must be numeric between 0 and 1.', 'error');
			}
		}
		
		// spot radius option
		if ( isset( $input[HUT_Common::SPOT_RADIUS_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::SPOT_RADIUS_OPTION] ) ) {
				$spot_radius = intval( $input[HUT_Common::SPOT_RADIUS_OPTION] );
				if ( $spot_radius < 1 && $spot_radius > 25 ) {
					add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'spot_radius_range_error', 'Spot radius must be numeric between 1 and 25.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::HEAT_MAP_SETTINGS_KEY, 'spot_radius_non_numeric_error', 'Spot radius must be numeric between 1 and 25.', 'error');
			}
		}
		
		// Ignore width option
		if ( isset( $input[HUT_Common::IGNORE_WIDTH_OPTION] ) && $input[HUT_Common::IGNORE_WIDTH_OPTION] == "true")
			$input[HUT_Common::IGNORE_WIDTH_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_WIDTH_OPTION] = false;
		
		// Ignore zoom level option
		if ( isset( $input[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION] ) && $input[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION] == "true")
			$input[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION] = false;
		
		// Ignore device pixel ratio option
		if ( isset( $input[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION] ) && $input[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION] == "true")
			$input[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION] = false;
		
		// Ignore device option
		if ( isset( $input[HUT_Common::IGNORE_DEVICE_OPTION] ) && $input[HUT_Common::IGNORE_DEVICE_OPTION] == "true")
			$input[HUT_Common::IGNORE_DEVICE_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_DEVICE_OPTION] = false;
		
		// Ignore os family option
		if ( isset( $input[HUT_Common::IGNORE_OS_FAMILY_OPTION] ) && $input[HUT_Common::IGNORE_OS_FAMILY_OPTION] == "true")
			$input[HUT_Common::IGNORE_OS_FAMILY_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_OS_FAMILY_OPTION] = false;
		
		// Ignore browser family option
		if ( isset( $input[HUT_Common::IGNORE_BROWSER_FAMILY_OPTION] ) && $input[HUT_Common::IGNORE_BROWSER_FAMILY_OPTION] == "true")
			$input[HUT_Common::IGNORE_BROWSER_FAMILY_OPTION] = true;
		else
			$input[HUT_Common::IGNORE_BROWSER_FAMILY_OPTION] = false;
		
		return $input;
	}
	
	/**
	 * Register the URL Filter settings
	 */
	function register_url_filter_settings() {
		register_setting( HUT_Common::URL_FILTERS_SETTINGS_KEY, HUT_Common::URL_FILTERS_SETTINGS_KEY, array( &$this, 'sanitize_url_filters_settings' ) );
	
		add_settings_section( 'section_url_filter', 'URL Filter Settings', array( &$this, 'section_url_filter_desc' ), HUT_Common::URL_FILTERS_SETTINGS_KEY );
	
		add_settings_field( HUT_Common::APPLY_URL_FILTERS_OPTION, 'Apply URL filters', array( &$this, 'field_apply_url_filter' ), HUT_Common::URL_FILTERS_SETTINGS_KEY, 'section_url_filter' );
		add_settings_field( HUT_Common::FILTER_TYPE_OPTION, 'Filter type', array( &$this, 'field_filter_type' ), HUT_Common::URL_FILTERS_SETTINGS_KEY, 'section_url_filter' );
	}
	
	/**
	 * URL Filter settings description
	 */
	function section_url_filter_desc() {
		echo "<p>URL filters can be useful for performance reasons (i.e. reduce server load) and to target specific pages (i.e. Home page only).</p>";
	}
	/**
	 * URl Filter settings fields
	 */
	function field_apply_url_filter() {
		?><input type="checkbox" name="<?php echo HUT_Common::URL_FILTERS_SETTINGS_KEY; ?>[<?php echo HUT_Common::APPLY_URL_FILTERS_OPTION; ?>]" value="true" <?php checked(true, $this->url_filter_settings[HUT_Common::APPLY_URL_FILTERS_OPTION], true); ?> />
		<p class="description">Turn on to apply the URL filters.</p>
		<?php 
	}
	function field_filter_type() {
		?>
		<input type="radio" name="<?php echo HUT_Common::URL_FILTERS_SETTINGS_KEY; ?>[<?php echo HUT_Common::FILTER_TYPE_OPTION; ?>]" value="whitelist" <?php checked('whitelist', $this->url_filter_settings[HUT_Common::FILTER_TYPE_OPTION], true); ?> />
		<label for="filterType">Whitelist</label><br />
		<input type="radio" name="<?php echo HUT_Common::URL_FILTERS_SETTINGS_KEY; ?>[<?php echo HUT_Common::FILTER_TYPE_OPTION; ?>]" value="blacklist"  <?php checked('blacklist', $this->url_filter_settings[HUT_Common::FILTER_TYPE_OPTION], true); ?>/>
		<label for="filterType">Blacklist</label>
		<p class="description">Set a filter type to either include (whitelist) or exclude (blacklist).</p>
		<?php
	}	
	function sanitize_url_filters_settings($input) {
	
		// Apply URL filters option
		if ( isset( $input[HUT_Common::APPLY_URL_FILTERS_OPTION] ) && $input[HUT_Common::APPLY_URL_FILTERS_OPTION] == "true")
			$input[HUT_Common::APPLY_URL_FILTERS_OPTION] = true;
		else
			$input[HUT_Common::APPLY_URL_FILTERS_OPTION] = false;
		
		return $input;
	}
	
	/**
	 * Register the Database settings
	 */
	function register_database_settings() {
		
		register_setting( HUT_Common::DATABASE_SETTINGS_KEY, HUT_Common::DATABASE_SETTINGS_KEY, array( &$this, 'sanitize_database_settings' ) );
	
		add_settings_section( 'section_database', 'Database Settings', array( &$this, 'section_database_desc' ), HUT_Common::DATABASE_SETTINGS_KEY );
	
		add_settings_field( HUT_Common::URL_DB_LIMIT_OPTION, 'URL database limit', array( &$this, 'field_url_db_limit' ), HUT_Common::DATABASE_SETTINGS_KEY, 'section_database' );
	}
	
	/**
	 * Database settings description
	 */
	function section_database_desc() {

	}

	function field_url_db_limit() {
		?>
		<input type="text" name="<?php echo HUT_Common::DATABASE_SETTINGS_KEY; ?>[<?php echo HUT_Common::URL_DB_LIMIT_OPTION; ?>]" value="<?php echo esc_attr( $this->database_settings[HUT_Common::URL_DB_LIMIT_OPTION] ); ?>" />&nbsp;(leave empty for no limit)
		<p class="description">Generally, large amounts of data collected over a longer period of time does not statistically provider better results. Therefore, you can limit the number of clicks and taps saved per URL. This also improves the performance when drawing the heat map. This condition is checked on every page load to determine whether to allow saving clicks and taps (so this means that once the limit is reached, the page has to be reloaded to stop saving the clicks and taps).</p>
		<?php
	}
		
	function sanitize_database_settings($input) {
		
		// URL database limit option
		if ( isset( $input[HUT_Common::URL_DB_LIMIT_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::URL_DB_LIMIT_OPTION] ) ) {
				$url_db_limit = intval( $input[HUT_Common::URL_DB_LIMIT_OPTION] );
				if ( $url_db_limit <= 0 ) {
					add_settings_error( HUT_Common::DATABASE_SETTINGS_KEY, 'url_db_limit_range_error', 'URL database limit must be numeric greater than 0 or empty.', 'error');
				}
			} else {
				if (strlen( trim($input[HUT_Common::URL_DB_LIMIT_OPTION])) != 0)
					add_settings_error( HUT_Common::DATABASE_SETTINGS_KEY, 'url_db_limit_trim_error', 'URL database limit must be numeric greater than 0 or empty.', 'error');
			}
		}
			
		return $input;
	}
	
	
	/**
	 * Creates the Settings page with the following tabs: General, Heat Maps, URl Filters and Advanced
	 *
	 * @since 2.0	
	 */
	public function add_admin_menus() {
		add_menu_page( __( 'Hotspots User Tracker', HUT_Common::PLUGIN_ID ), __( 'Hotspots User Tracker', HUT_Common::PLUGIN_ID ), 'manage_options', 'user_tracking_page', array( &$this, 'user_tracking_page' ), plugins_url( 'hotspots16.ico', __FILE__ ), null );

		add_submenu_page('user_tracking_page','','','manage_options','user_tracking_page', array( &$this, 'user_tracking_page' ));
		add_submenu_page('user_tracking_page','User Tracking','User Tracking','manage_options','hut_user_tracking_page', array( &$this, 'user_tracking_page' ));
		add_submenu_page('user_tracking_page','Settings','Settings','manage_options', 'hut_settings_page', array( &$this, 'settings_page' ));

	}

	/**
	 * Displays the plugin tab headers
	 */
	function settings_page() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : HUT_Common::GENERAL_SETTINGS_TAB;
	
		?>
		<div class="wrap">
			<div id="hut-icon" class="icon32" style="background: url('<?php echo plugins_url( 'hotspots32.ico', __FILE__ ); ?>') no-repeat left top;"></div>
			<h2>Hotspots User Tracker: Settings
				<form id="paypal-form" style="display: inline-block;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="display: inline-block;">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5BDJEA4KANDZW">
					<input type="image" src="https://www.paypalobjects.com/en_AU/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_AU/i/scr/pixel.gif" width="1" height="1">
				</form>
			</h2>
			
			<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $this->settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="?page=hut_settings_page&tab=' . $tab_key . '">' . $tab_caption . '</a>';
			}
			echo '</h2>';
		
			if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
				add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
			}
			settings_errors();
			
			$this->do_settings_page_tabs($current_tab);
			
		?>
		</div>
		<?php
	}

	/**
	 * Display settings page tabs
	 * @param unknown_type $tab
	 */
	function do_settings_page_tabs($tab) {
		if ($tab == HUT_Common::URL_FILTER_SETTINGS_TAB)
			$this->do_url_filter_settings_tab();
		else if ($tab == HUT_Common::GENERAL_SETTINGS_TAB)
			$this->do_general_settings_tab();
		else if ($tab == HUT_Common::SCHEDULE_SETTINGS_TAB)
			$this->do_schedule_settings_tab();
		else if ($tab == HUT_Common::DATABASE_SETTINGS_TAB)
			$this->do_database_settings_tab();
		else if ($tab == HUT_Common::HEAT_MAP_SETTINGS_TAB)
			$this->do_heat_map_settings_tab();
		else if ($tab == HUT_Common::ELEMENT_SETTINGS_TAB)
			$this->do_element_settings_tab();
	}
	
	/** 
	 * General settings tab
	 */
	function do_general_settings_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::GENERAL_SETTINGS_KEY );
			do_settings_sections( HUT_Common::GENERAL_SETTINGS_KEY );
			submit_button();
			?>
		</form>
		<?php
	}
	function do_database_settings_tab() {
		if ( isset( $_POST['clear-db-flag'] ) && $_POST['clear-db-flag'] === "true" )
			$this->clear_database();
		?>
		<form method="post" action="options.php">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::DATABASE_SETTINGS_KEY );
			do_settings_sections( HUT_Common::DATABASE_SETTINGS_KEY );
			submit_button( );
			?>
		</form>
		
		<form method="post">
			<input type="hidden" name="clear-db-flag" id="clear-db-flag" value="false" />
			<?php 
			submit_button( $text = 'Clear database', $type = 'delete', $name = 'clear-database', $wrap = false, $other_attributes = null );
			?>
			<p class="description">Clear all data saved in the database.</p>
		</form>
		<?php 
	}
	function do_schedule_settings_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::SCHEDULE_SETTINGS_KEY );
			do_settings_sections( HUT_Common::SCHEDULE_SETTINGS_KEY );
			submit_button();
			?>
		</form>
		<?php 
	}
	function do_heat_map_settings_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::HEAT_MAP_SETTINGS_KEY );
			do_settings_sections( HUT_Common::HEAT_MAP_SETTINGS_KEY );
			submit_button();
			?>
		</form>
		<?php 
	}
	
	/**
	 * Clears the database. Can be overriden to clear from multiple tables.
	 */
	function clear_database() {
		global $wpdb;
		$error_message = "";
		$success_message = "";
		try {
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE 1' );
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::AJAX_PING_TBL_NAME.' WHERE 1' );
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME.' WHERE 1' );
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME.' WHERE 1' );
			$success_message .= 'Database cleared successfully.';
		} catch ( Exception $e ) {
			$error_message .= 'An error has occured. ' . $e->getMessage();
		}
		if ( strlen( $error_message ) > 0)
			echo '<div class="error"><p>' . $error_message . '</p></div>';
		if ( strlen( $success_message ) > 0)
			echo '<div class="updated"><p>' . $success_message . '</p></div>';
	}
	
	/**
	 * URL filters tab
	 */
	function do_url_filter_settings_tab() {
		// Check whether adding a URL filters
		if (isset( $_POST['add-URL-filter-flag'] ) && $_POST['add-URL-filter-flag'] === "true") {
			global $wpdb;
			$error_message = "";
			$success_message = "";
				
			$url = isset( $_POST['url-filter'] ) ? trim( $_POST['url-filter'] ) : '';
	
			if ( strlen( $url ) == 0 ) {
				$error_message .= 'URL field is missing or invalid.';
			} else {
				$url = HUT_Common::normalize_url( $url );
				$url = addslashes($url);
				global $wpdb;
					
				try {
					// first make sure the URL has not already been added
					$query = 'SELECT * FROM '.$wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME . ' where '. HUT_URL_Filter_Table::URL_COLUMN .' = "' .$url . '"';
					$count = $wpdb->query($query);
	
					if ($count > 0) {
						$error_message .= 'URL filter for ' . $url .' already exists.';
					} else {
						$wpdb->flush();
						$results = $wpdb->insert( $wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME, array( 'url' => $url ) );
						$success_message .= 'URL filter added successfully.';
					}
				} catch ( Exception $e ) {
					$error_message .= 'An error has occured. ' . $e->getMessage();
				}
			}
			if ( strlen( $error_message ) > 0)
				echo '<div class="error"><p>' . $error_message . '</p></div>';
			if ( strlen( $success_message ) > 0)
				echo '<div class="updated"><p>' . $success_message . '</p></div>';
		} ?>
		
		<form method="post" action="options.php">
			<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::URL_FILTERS_SETTINGS_KEY );
			do_settings_sections( HUT_Common::URL_FILTERS_SETTINGS_KEY );
			submit_button();
			?>
		</form>
				
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">URL</th>
						<td>
							<input type="text" class="regular-text" name="url-filter" id="url-filter" value="<?php echo HUT_Common::normalize_url( site_url() ); ?>" />
							<p class="description">Enter URL to filter (i.e. http://www.mywebsite.com and http://www.mywebsite.com?cat=1).</p>
						</td>
					</tr>
				</tbody>
			</table>
		    <input type="hidden" name="add-URL-filter-flag" id="add-URL-filter-flag" value="false" />
			<?php 
			submit_button( $text = 'Add URL filter', $type = 'button-secondary', $name = 'add-URL-filter', $wrap = false, $other_attributes = null );
		 	?>
		</form>
		
		<form method="post">
	    <?php 	
	   		$url_filters_table = new HUT_URL_Filter_Table();
		    $url_filters_table->prepare_items();
		    $url_filters_table->display();
		?>
		</form>
	<?php
	}
		

	/**
	 * Heat maps tab
	 */
	function do_heat_maps_tab() {
		?>
		<h3>Heat Maps</h3>
		<p>Different heat maps are drawn when you resize the window width, modify zoom levels and device pixel ratios to cater 
		for responsive design. Ensure the enable drawing heat map option is enabled. You can also manually view the heat maps 
		by adding a query parameter drawHeatMap=true to the URL (i.e. http://www.mywebsite.com?drawHeatMap=true and 
		http://www.mywebsite.com?cat=1&drawHeatMap=true). Note: You can target specific heat maps (width, device pixel ratio 
		and zoom levels only) using the View Heat Map button in the table below.</p>
		<form id="heat-maps-form" name="heat-maps-form" method="post">
			<?php		        
			$hut_heat_maps_table = new HUT_Heat_Maps_Table();
			$hut_heat_maps_table->prepare_items();
			$hut_heat_maps_table->display();
			?>
		</form>
		<?php
	}
	
	/**
	 * Users page
	 */
	function user_tracking_page() {
		?>
		<div class="wrap">
			<div id="hut-icon" class="icon32" style="background: url('<?php echo plugins_url( 'hotspots32.ico', __FILE__ ); ?>') no-repeat left top;"></div>
			<h2>Hotspots User Tracker: Settings
				<form id="paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="display: inline-block;">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="5BDJEA4KANDZW">
					<input type="image" src="https://www.paypalobjects.com/en_AU/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_AU/i/scr/pixel.gif" width="1" height="1">
				</form>
			</h2>
			
			<h2 class="nav-tab-wrapper">
			<?php 
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : HUT_Common::HEAT_MAPS_TAB;
			foreach ( $this->user_tracking_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="?page=hut_user_tracking_page&tab=' . $tab_key . '">' . $tab_caption . '</a>';
			}
			echo '</h2>';
		
			$this->do_user_tracking_tabs($current_tab);
		
		?>
		</div>
		<?php
	}
		
	/**
	 * Display users page tabs
	 * @param unknown_type $tab
	 */
	function do_user_tracking_tabs($tab) {
		if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
			add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
		}
		settings_errors();
	
		if ($tab == HUT_Common::HEAT_MAPS_TAB)
			$this->do_heat_maps_tab();
		else if ($tab == HUT_Common::USERS_TAB)
			$this->do_users_tab();
		else if ($tab == HUT_Common::USER_ACTIVITY_TAB)
			$this->do_user_activity_tab();
		else if ($tab == HUT_Common::REPORTS_TAB)
			$this->do_reports_tab();
	}
	
	/**
	 * Statistics tab
	 */
	function do_statistics_tab() {
		
	}
	
	/**
	 * Element selectors
	 */
	function do_element_settings_tab() {
		echo '<h3>Element Settings</h3>';
		if ( isset( $_POST['name']) && isset( $_POST['element_selector'] ) ) {
			$name = $_POST['name'];
			$element_selector = $_POST['element_selector'];
	
			$url = isset( $_POST['url'] ) ? trim( $_POST['url'] ) : '';
			$url = HUT_Common::normalize_url( $url );
			$url = addslashes($url);
				
			$is_form_submit = isset( $_POST['isFormSubmit'] ) ? true : false;
				
			$valid_input = true;
			if ( strlen( trim( $element_selector ) ) == 0 ) {
				echo '<div class="error"><p>A selector must be entered</p></div>';
				$valid_input = false;
			}
			if ( strlen( trim( $name ) ) == 0) {
				echo '<div class="error"><p>A name must be entered</p></div>';
				$valid_input = false;
			}
				
			if ($valid_input == true) {
				global $wpdb;
				try {
					// first make sure the URL has not already been added
					$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME . ' WHERE '. HUT_Common::ELEMENT_SELECTOR_INPUT .' = "' .$element_selector . '" AND url = "' . $url . '"';
						
					$count = $wpdb->query($query);
	
					if ($count > 0) {
						echo '<div class="error"><p>Element selector already exists.</p></div>';
					} else {
						$wpdb->flush();
						$results = $wpdb->insert( $wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME, array( 'element_selector' => $element_selector, 'name' => $name, 'url' => $url, 'is_form_submit' => $is_form_submit ) );
						echo '<div class="success"><p>Element selector added successfully.</p></div>';
					}
				} catch ( Exception $e ) {
					echo '<div class="error"><p>An error occurred. ' . $e->getMessage() . '</p></div>';
				}
			}
	
		}
	
		?>
		<p>Element selectors can be used to track the areas of impressions (a mouse click or touch screen tap) and form submits. For example, a website may have a div container with id "my-sidebar" and you'd like to track how often a user makes an impression anywhere within this container. An element selector "div#my-sidebar" could be used to track this information. Element selectors can also be nested.</p>
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">Selector</th>
						<td>
							<input type="text" name="element_selector" id="element_selector" value="" />
							<p class="description">Enter a jQuery element selector.</p>
							<p class="description">Some useful examples for WordPress websites (if you are to use these check the HTML and modify the element selectors accordinly):<br />
								ul.menu li - All menu items<br />
								header - Header<br />
								footer - Footer<br />
								.widget - Widgets<br />
								form.search-form - a search form<br />
								input#name - Name input field<br />
								form - All forms (form submit)</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Name</th>
						<td>
							<input type="text" name="name" id="name" value="" />
							<p class="description">Enter a name for the element selector. The name should describe the element's purpose such as "Contact form" or "Menu link".</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Is form submit?</th>
						<td>
							<input type="checkbox" name="isFormSubmit" id="isFormSubmit" value="" />
							<p class="description">Does the element trigger a form submit javaScript event? If you want to track an element click or tap instead of a submit, then leave this unchecked.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">URL</th>
						<td>
							<input class="regular-text" type="text" name="url" id="url" value="" />&nbsp(Optional, leave empty to target all URLs)
							<p class="description">You can enter a URL to target a specific page.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="submit" class="button button-secondary" value="Add Element Selector" />
		</form>
		
		<br />
			
		<form method="post">
			<?php 
			$element_selector_table = new HUT_Element_Selector_Table();
			$element_selector_table->prepare_items();
			$element_selector_table->display();
			?>
		</form>
		<?php 
		
	}
		
	/**
	 * Reports tab
	 */
	function do_reports_tab() {
		global $wpdb;
		$start_date = isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::START_DATE_SEARCH_INPUT] : '';
		$end_date = isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::END_DATE_SEARCH_INPUT] : '';
		?>
		<h3>Reports</h3>
		<p><a href="#">Element Selector Report</a></p>
		
		<h4>Element Selector Report</h4>
		
		<form method="post">
			<?php 
			$element_impressions_report_table = new HUT_Element_Impressions_Report_Table();
			$element_impressions_report_table->prepare_items();
			$element_impressions_report_table->display();
			?>
		</form>
		
		<p class="description">Note: Reports may not be 100% accurate. Nested element selectors can be used.</p>
		
		<p>Please suggest report ideas in the plugin <a href="http://wordpress.org/support/plugin/hotspots">support forum</a>. Thank you.</p>
		<?php
	}
	
	/**
	 * Displays a tab for user activities
	 */
	function do_users_tab() {
		if (isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) > 0) {
			if (!HUT_Common::check_date_format($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) > 0) {
				echo '<div class="error"><p>Invalid start date.</p></div>';
			}
		}
		if (isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) > 0) {
			if (!HUT_Common::check_date_format($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) > 0) {
				echo '<div class="error"><p>Invalid end date</p></div>';
			}
		}
		?>
			
		<h3>Users</h3>
		<p>Search for users to view their activities.</p>
		<form method="post">
		
			<?php 
			$users_table = new HUT_Users_Table();
			$users_table->prepare_items();
			$users_table->display();
		
		?></form><?php
	}
	
	/**
	 * Displays a tab for user activities
	 */
	function do_user_activity_tab() {
		
		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : '';
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : '';
		
		?>
		<h3>User Activity</h3>
		<form method="post">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">IP Address</th>
						<td>
							<input type="text" name="ip_address" id="ip_address" value="<?php echo $ip_address; ?>" />
							<p class="description">Enter the IP address of the user.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Session ID</th>
						<td>
							<input type="text" name="session_id" id="session_id" value="<?php echo $session_id; ?>" />
							<p class="description">Enter the Session ID of the user.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="submit" class="button button-primary" value="View User Activity" />
		</form>
		<p class="description">Navigate from the Users tab using the "View User Activity" action button to prefill the IP address and session ID inputs for a specific user.</p>
		
		<?php 
		$this->display_user_activies($ip_address, $session_id);
	}
	

	
	/**
	 * Displays user activities
	 * 
	 * @param unknown_type $ip_address
	 */
	function display_user_activies($ip_address, $session_id) {		
		
		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : null;
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : null;
		$start_date = null;
		$end_date = null;
		$url = null;
		if ($ip_address == null || $session_id == null) {
			if (isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) > 0) {
				if (HUT_Common::check_date_format($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) {
					$start_date = date("Y-m-d H:i:s", strtotime($_POST[HUT_Common::START_DATE_SEARCH_INPUT])); // default yyyy-mm-dd format
				}
			}
				
			if (isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) > 0) {
				if (HUT_Common::check_date_format($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) {
					list($yyyy, $mm, $dd) = explode('-', $_POST[HUT_Common::END_DATE_SEARCH_INPUT]);// default yyyy-mm-dd format
					$end_date = date("Y-m-d H:i:s", mktime(23, 59, 59, $mm, $dd, $yyyy) );
				}
			}
				
			if (isset($_POST[HUT_Common::URL_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::URL_SEARCH_INPUT])) > 0) {
				$url = $_POST[HUT_Common::URL_SEARCH_INPUT];
			}
		}
		$params = array('ip_address' => $ip_address, 'session_id' => $session_id, 'start_date' => $start_date, 'end_date' => $end_date, 'url' => $url);
		
		?>
		
		<div id="poststuff" class="">
	        <div id="post-body" class="metabox-holder hut-columns-2">
				<form  method="get" action="">
                <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
                <?php add_meta_box("summary-metabox", "Summary", array($this, "user_activity_summary_metabox"), "hut_user_tracking_page", "normal");?>
                <?php add_meta_box("environment-metabox", "Environment", array($this, "user_activity_environment_metabox"), "hut_user_tracking_page", "normal");?>
                <?php do_meta_boxes('hut_user_tracking_page','normal', $params);?>
            </form>
			</div>
		</div>
		
		<?php 

		$users_table = new HUT_User_Activity_Sequence_Table();
		$users_table->prepare_items();
		$users_table->display();
	}
	
	function user_activity_summary_metabox($params) {
		global $wpdb;
		$start_date = $params['start_date'];
		$end_date = $params['end_date'];
		$ip_address = $params['ip_address'];
		$session_id = $params['session_id'];
		$url = $params['url'];
		
		$summary_query = 'SELECT ip_address, session_id, record_date as r, record_date FROM ( SELECT * FROM ' . $wpdb->prefix . HUT_Common::URL_PING_TBL_NAME . ' ';
		$summary_query .= 'WHERE ';		
		if ($session_id && $ip_address)
			$summary_query .= 'session_id = "' . $session_id . '" AND ip_address = "' . $ip_address . '"';
		else if ($session_id)
			$summary_query .= 'session_id = "' . $session_id . '"';
		else
			$summary_query .= 'ip_address = "' . $ip_address . '"';
		
		$summary_query .= 'ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC ) AS a GROUP BY ' . HUT_Common::IP_ADDRESS_COLUMN . ', session_id ORDER BY r DESC';
		$rows = $wpdb->get_results( $summary_query );
		
		// user login and role
		$user_query = 'SELECT user_login, role FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$user_login = $wpdb->get_col($user_query, 0);
		$user_role =  $wpdb->get_col($user_query, 1);
		if (isset($user_login) && is_array($user_login) && count($user_login) > 0)
			$user_login = $user_login[0];
		else
			$user_login = '';
		global $wp_roles;
		if ( ! isset( $wp_roles) )
			$wp_roles = new WP_Roles();
		$roles = $wp_roles->get_names();
		if (count($user_role) > 0 && $user_role[0] != null)
			$user_role = $roles[$user_role[0]];
		else
			$user_role = '';
		
		// page view count
		$page_hits_count_query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$page_hits_count = $wpdb->query($page_hits_count_query);
		
		// ajax action count
		$ajax_action_count_query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::AJAX_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$ajax_action_count = $wpdb->query($ajax_action_count_query);
		
		// click tap count
		$click_tap_count_query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$click_tap_count = $wpdb->query($click_tap_count_query);
		
		// elements
		$element_count_query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$element_count = $wpdb->query($element_count_query);
		
		if (count($rows) > 0) {
			?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">IP Address</th>
						<td><?php echo $rows[0]->ip_address; ?></td>
						<th scope="row">Session ID</th>
						<td><?php echo $rows[0]->session_id; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">User</th>
						<td><?php echo $user_login; ?></td>
						<th scope="row">Last Page Hit Date</th>
						<td><?php echo date("F j, Y, g:i a", strtotime($rows[0]->record_date)); ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Page Hits</th>
						<td><?php echo $page_hits_count; ?></td>
						<th scope="row">AJAX Actions</th>
						<td><?php echo $ajax_action_count; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Clicks/Taps</th>
						<td><?php echo $click_tap_count; ?></td>
						<th scope="row">Element</th>
						<td><?php echo $element_count; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Role</th>
						<td><?php echo $user_role; ?></td>
						<th scope="row"></th>
						<td></td>
					</tr>
				</tbody>
			</table>
			<?php
		} else {
			echo '<p>No data found.</p>';
		}
	}
	
	
	function user_activity_environment_metabox($params) {
		global $wpdb;
		$start_date = $params['start_date'];
		$end_date = $params['end_date'];
		$ip_address = $params['ip_address'];
		$session_id = $params['session_id'];
		$url = $params['url'];
		$query = 'SELECT zoom_level, width, device_pixel_ratio, browser_family, browser_version, device, os_family, os_version FROM  ' . $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '" GROUP BY zoom_level, width, device_pixel_ratio, browser_family, browser_version, device, os_family, os_version';
		$rows = $wpdb->get_results( $query );

			
		if (count($rows) > 0) {
			?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">Zoom Level</th>
						<td><?php echo (100 * $rows[0]->zoom_level); ?>%</td>
						<th scope="row">Width</th>
						<td><?php echo $rows[0]->width; ?>px</td>
					</tr>
					<tr valign="top">
						<th scope="row">Device Pixel Ratio</th>
						<td><?php echo HUT_Common::convert_decimalto_ratio($rows[0]->device_pixel_ratio); ?></td>
						<th scope="row">Browser</th>
						<td><?php echo $rows[0]->browser_family . ' ' . $rows[0]->browser_version; ?></td>
					</tr>
					<tr valign="top">
						<th scope="row">Device</th>
						<td><?php echo $rows[0]->device; ?></td>
						<th scope="row">Operatig System</th>
						<td><?php echo $rows[0]->os_family . ' ' . $rows[0]->os_version; ?></td>
					</tr>
				</tbody>
			</table>
			<?php
		} else {
			echo '<p>No data found.</p>';
		}
	}

	/**
	 * Admin assets
	 *
	 * @since 1.2.8
	 */
	public function assets() {
		$config_array = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( HUT_Common::PLUGIN_ID.'-nonce' )
		);
		wp_enqueue_script( 'jquery' );
	
		if ( is_admin() ) {
			wp_enqueue_style( HUT_Common::PLUGIN_ID.'-admin-style', plugins_url( 'css/admin.css', __FILE__ ) );
			wp_enqueue_script( HUT_Common::PLUGIN_ID.'-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( HUT_Common::PLUGIN_ID.'-admin-script', HUT_Common::CONFIG_DATA, $config_array );
			
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-timepicker');
			wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		}
		
		wp_enqueue_script('common');
	    wp_enqueue_script('wp-lists');
	    wp_enqueue_script('postbox');
	}
	
	/**
	 * Register AJAX call actions
	 *
	 * @since 2.4
	 */
	public function add_ajax_actions() {
		
		if (is_admin()) {
			add_action( 'wp_ajax_save_click_or_tap', array( $this, 'save_click_or_tap' ) );
			add_action( 'wp_ajax_retrieve_clicks_and_taps',  array( $this, 'retrieve_clicks_and_taps' ) );
			add_action( 'wp_ajax_nopriv_save_click_or_tap',  array( $this, 'save_click_or_tap' ) );
			add_action( 'wp_ajax_nopriv_retrieve_clicks_and_taps',  array( $this, 'retrieve_clicks_and_taps' ) );
		
			add_action( 'wp_ajax_url_ping', array( $this, 'url_ping' ) );
			add_action( 'wp_ajax_nopriv_url_ping',  array( $this, 'url_ping' ) );
			add_action( 'wp_ajax_ajax_ping', array( $this, 'ajax_ping' ) );
			add_action( 'wp_ajax_nopriv_ajax_ping',  array( $this, 'ajax_ping' ) );
			add_action( 'wp_ajax_form_submit_ping', array( $this, 'form_submit_ping' ) );
			add_action( 'wp_ajax_nopriv_form_submit_ping',  array( $this, 'form_submit_ping' ) );
			add_action( 'wp_ajax_element_selector_ping', array( $this, 'element_selector_ping' ) );
			add_action( 'wp_ajax_nopriv_element_selector_ping',  array( $this, 'element_selector_ping' ) );
		}
	}

	/**
	 * Calculates the heat value given closeness of existing mouse clicks
	 * or touch screen taps
	 *
	 * @param x
	 * @param y
	 * @param id
	 * @param rows
	 */
	public function calculate_heat_value($x, $y, $id, $rows) {
		$heat_value = 0;
		$spot_radius = $this->heat_map_settings[HUT_Common::SPOT_RADIUS_OPTION];
	
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
	 * Retrieves all mouse clicks/touch screen taps
	 *
	 * @since 1.0
	 */
	public function retrieve_clicks_and_taps() {
		global $wpdb;
		$ajax_nonce = $_POST['nonce'];
	
		$response_data = array();
		if ( wp_verify_nonce( $ajax_nonce, HUT_Common::PLUGIN_ID .'-nonce' ) ) {
			$url = '';
			if ( isset($_POST['url']) ) {
				$url = HUT_Common::normalize_url( $_POST['url'] );
			}
	
			$query = "SELECT " . HUT_Common::ID_COLUMN . ", ".HUT_Common::X_COLUMN.", ".HUT_Common::Y_COLUMN.", "
			. HUT_Common::URL_COLUMN.", ".HUT_Common::WIDTH_COLUMN." FROM "
			. $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME." WHERE ".HUT_Common::URL_COLUMN." = '" . $url . "'";
	
			$ignore_width = $this->heat_map_settings[ HUT_Common::IGNORE_WIDTH_OPTION];

			if ( $ignore_width == false ) {
				// allow a range either side to be the same
				$width_allowance = $this->heat_map_settings[ HUT_Common::WIDTH_ALLOWANCE_OPTION ];
				$width = 0;
				if ( isset($_POST['width'] ) )
					$width = intval($_POST['width']);
				$diff_left = $width - $width_allowance;
				$diff_right = $width + $width_allowance;
				$query .= ' AND '.HUT_Common::WIDTH_COLUMN.' >= ' . $diff_left . ' AND '.HUT_Common::WIDTH_COLUMN.' <= '. $diff_right;
			}
			
			$ignore_zoom_level = $this->heat_map_settings[ HUT_Common::IGNORE_ZOOM_LEVEL_OPTION];
			if ( $ignore_zoom_level == false ) {
				$zoom_level = 1;
				if ( isset($_POST['zoomLevel'] ) )
					$zoom_level = doubleval( $_POST['zoomLevel'] );
	
				$query .= ' AND ' . HUT_Common::ZOOM_LEVEL_COLUMN . ' = ' . $zoom_level;
			}
	
			$ignore_device_pixel_ratio = $this->heat_map_settings[ HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION];
			if ( $ignore_device_pixel_ratio == false ) {
				$device_pixel_ratio = 1;
				if ( isset($_POST['devicePixelRatio']) )
					$device_pixel_ratio = doubleval( $_POST['devicePixelRatio'] );
	
				$query .= ' AND ' . HUT_Common::DEVICE_PIXEL_RATIO_COLUMN . ' = ' . $device_pixel_ratio;
			}
	
			if ( isset($_POST['clickTapId']) && $_POST['clickTapId'] !== null && $_POST['clickTapId'] !== "" && $_POST['clickTapId'] !== "null") {
				$click_tap_id = intval( $_POST['clickTapId'] );
				$query .= ' AND ' . HUT_Common::ID_COLUMN . ' = ' . $click_tap_id;
			}
			
			$ignore_device = $this->heat_map_settings[ HUT_Common::IGNORE_DEVICE_OPTION];
			if ($ignore_device == false) {
				if ( isset($_POST['device']) && $_POST['device'] !== null && $_POST['device'] !== "" && $_POST['device'] !== "null") {
					$device =  $_POST['device'];
					$query .= ' AND device = "' . $device . '"';
				}
			}
			$ignore_os_family = $this->heat_map_settings[ HUT_Common::IGNORE_OS_FAMILY_OPTION];
			if ($ignore_os_family == false) {
				if ( isset($_POST['osFamily']) && $_POST['osFamily'] !== null && $_POST['osFamily'] !== "" && $_POST['osFamily'] !== "null") {
					$osFamily =  $_POST['osFamily'];
					$query .= ' AND os_family = "' . $osFamily . '"';
				}
			}
			$ignore_browser_family = $this->heat_map_settings[ HUT_Common::IGNORE_BROWSER_FAMILY_OPTION];
			if ($ignore_browser_family == false) {
				if ( isset($_POST['browserFamily']) && $_POST['browserFamily'] !== null && $_POST['browserFamily'] !== "" && $_POST['browserFamily'] !== "null") {
					$browserFamily =  $_POST['browserFamily'];
					$query .= ' AND browser_family = "' . $browserFamily . '"';
				}
			}
			$hide_roles = $this->heat_map_settings[ HUT_Common::HIDE_ROLES_OPTION];
			if (count($hide_roles) > 0) {
				foreach ($hide_roles as $role) {
					if ($role == HUT_Common::NO_ROLE_VALUE)
						$query .= ' AND ' . HUT_Common::ROLE_COLUMN . ' != ""';
					else
						$query .= ' AND ' . HUT_Common::ROLE_COLUMN . ' != "' . $role . '"';
				}
			}
			
			$rows = $wpdb->get_results($query);
	
			$index = 0;
			foreach ($rows as $row) {
				$id = $row->id;
				$x = $row->x;
				$y = $row->y;
	
				// TODO Do we need to normalize the URL once it is saved to the DB?
				$url = HUT_Common::normalize_url( $row->url );
				$width = $row->width;
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
	 * Saves mouse click or touch screen tap information database
	 *
	 * @since 2.0
	 */
	public function save_click_or_tap() {
		global $wpdb;
	
		$ajaxNonce = $_POST['nonce'];
	
		if ( wp_verify_nonce( $ajaxNonce, HUT_Common::PLUGIN_ID.'-nonce' ) ) {
			$x = 0;
			if ( isset($_POST['x']) )
				$x = intval( $_POST['x'] );
	
			$y = 0;
			if ( isset($_POST['y'] ) )
				$y =  intval( $_POST['y'] );
	
			$url = '';
			if ( isset($_POST['url']) ) {
				$url = HUT_Common::normalize_url( $_POST['url'] );
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
	
			$ip_address = HUT_Common::get_IP_address();
	
			$device_pixel_ratio = 1;
			if ( isset($_POST['devicePixelRatio']) )
				$device_pixel_ratio = doubleval( $_POST['devicePixelRatio'] );
			
			// Lets get the role again, we don't really need to passed from the frontend
			global $wp_roles;
			$current_user = wp_get_current_user();
			$roles = $current_user->roles;
			$role = array_shift($roles);
			$user_login = $current_user->user_login;
			
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$parser = new UAParser();
			$result = $parser->parse($ua);
			
			$browser_family =  $result->ua->family;
			$browser_version = $result->ua->toVersionString; 
			$device = $result->device->family;
			$os_family = $result->os->family;
			$os_version = $result->os->toVersionString;
			
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::CLICK_TAP_TBL_NAME,
					array(
							HUT_Common::X_COLUMN => $x,
							HUT_Common::Y_COLUMN => $y,
							HUT_Common::URL_COLUMN => $url,
							HUT_Common::WIDTH_COLUMN => $width,
							HUT_Common::ZOOM_LEVEL_COLUMN => $zoom_level,
							HUT_Common::IS_TAP_COLUMN => $is_tap,
							HUT_Common::IP_ADDRESS_COLUMN => $ip_address,
							HUT_Common::DEVICE_PIXEL_RATIO_COLUMN => $device_pixel_ratio,
							HUT_Common::CREATED_DATE_COLUMN => current_time('mysql'),
							HUT_Common::SESSION_ID_COLUMN => session_id(),
							HUT_Common::ROLE_COLUMN => $role,
							HUT_Common::USER_LOGIN => $user_login,
							'browser_family' => $browser_family,
							'browser_version' => $browser_version,
							'device' => $device,
							'os_family' => $os_family,
							'os_version' => $os_version
					)
			);
			$id = $wpdb->insert_id;
	
			// If debug and draw heat map enabled, return the heat value so we can
			// draw the heat colour of the circle
			$debug = $this->general_settings[ HUT_Common::DEBUG_OPTION ];
			$draw_heat_map_enabled = $this->general_settings[ HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION ];
			if ($debug == true && $draw_heat_map_enabled == true) {
				// retrieve all clicks and taps and calculate heat value
				$query = "SELECT " . HUT_Common::ID_COLUMN . ", ".HUT_Common::X_COLUMN.", ".HUT_Common::Y_COLUMN.", "
				. HUT_Common::URL_COLUMN.", ".HUT_Common::WIDTH_COLUMN." FROM "
				. $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME." WHERE ".HUT_Common::URL_COLUMN." = '" . $url . "'";
				
				// allow a range either side to be the same
				$width_allowance = $this->heat_map_settings[ HUT_Common::WIDTH_ALLOWANCE_OPTION ];
				$diff_left = $width - $width_allowance;
				$diff_right = $width + $width_allowance;
	
				$query .= ' AND '.HUT_Common::WIDTH_COLUMN.' >= ' . $diff_left . ' AND '.HUT_Common::WIDTH_COLUMN.' <= '. $diff_right;
	
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
	 * Record URL ping
	 */
	function url_ping() {
		$ajaxNonce = $_POST['nonce'];
	
		if ( wp_verify_nonce( $ajaxNonce, HUT_Common::PLUGIN_ID.'-nonce' ) ) {
			global $wpdb;
				
			$url = '';
			if ( isset($_POST['url']) ) {
				$url = HUT_Common::normalize_url( $_POST['url'] );
			}
			$ip_address = HUT_Common::get_IP_address();
				
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::URL_PING_TBL_NAME,
					array(
							HUT_Common::URL_COLUMN => $url,
							HUT_Common::IP_ADDRESS_COLUMN => $ip_address,
							HUT_Common::RECORD_DATE_COLUMN => current_time('mysql'),
							HUT_Common::SESSION_ID_COLUMN => session_id()
					)
			);
			$id = $wpdb->insert_id;
			$response = array('id' => $id);
				
			echo json_encode($response);
		}
		die();
	}
	
	/**
	 * Record AJAX ping
	 */
	function ajax_ping() {
		$ajaxNonce = $_POST['nonce'];
	
		if ( wp_verify_nonce( $ajaxNonce, HUT_Common::PLUGIN_ID.'-nonce' ) ) {
			global $wpdb;
	
			$url = '';
			if ( isset($_POST['url']) ) {
				$url = HUT_Common::normalize_url( $_POST['url'] );
			}
			$ip_address = HUT_Common::get_IP_address();
			$ajax_action = '';
			if ( isset($_POST['ajaxAction']) ) {
				$ajax_action = $_POST['ajaxAction'];
			}
			$status_text = '';
			if ( isset($_POST['statusText']) ) {
				$status_text = $_POST['statusText'];
			}
	
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::AJAX_PING_TBL_NAME,
					array(
							HUT_Common::URL_COLUMN => $url,
							HUT_Common::IP_ADDRESS_COLUMN => $ip_address,
							HUT_Common::RECORD_DATE_COLUMN => current_time('mysql'),
							HUT_Common::AJAX_ACTION_COLUMN => $ajax_action,
							HUT_Common::STATUS_TEXT_COLUMN => $status_text,
							HUT_Common::SESSION_ID_COLUMN => session_id()
					)
			);
			$id = $wpdb->insert_id;
			$response = array('id' => $id);
	
			echo json_encode($response);
		}
		die();
	}
	
	/**
	 * Record Element selector ping
	 */
	function element_selector_ping() {
		$ajaxNonce = $_POST['nonce'];
	
		if ( wp_verify_nonce( $ajaxNonce, HUT_Common::PLUGIN_ID.'-nonce' ) ) {
			global $wpdb;
	
			$url = '';
			if ( isset($_POST['url']) ) {
				$url = HUT_Common::normalize_url( $_POST['url'] );
			}
			$ip_address = HUT_Common::get_IP_address();
			$element_selector = '';
			if ( isset($_POST['elementSelector']) ) {
				$element_selector = $_POST['elementSelector'];
			}
			
			// TODO Do we want to pass isFormSubmit in AJAX call? Currently it is not needed
			/* $is_form_submit = false;
			if ( isset($_POST['is_form_submit']) ) {
			$is_form_submit = $_POST['is_form_submit'];
			}*/
	
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME,
					array(
							HUT_Common::URL_COLUMN => $url,
							HUT_Common::IP_ADDRESS_COLUMN => $ip_address,
							HUT_Common::RECORD_DATE_COLUMN => current_time('mysql'),
							HUT_Common::ELEMENT_SELECTOR_COLUMN => $element_selector,
							HUT_Common::SESSION_ID_COLUMN => session_id(),
							//HUT_Common::IS_FORM_SUBMIT_COLUMN => $is_form_submit
					)
			);
			$id = $wpdb->insert_id;
			$response = array('id' => $id);
	
			echo json_encode($response);
		}
		die();
	}
}

?>