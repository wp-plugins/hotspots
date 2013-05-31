<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-common.php';


/**
 * Admin class
 * 
 * @author dpowney
 *
 */
class HUT_Admin {
	
	public $plugin_settings_tabs = array();
	private $general_settings = array();
	private $advanced_settings = array();
	private $url_filter_settings = array();

	
	/**
	 * Constructor
	 *
	 * @since 2.4
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_url_filter_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );
		
		$this->plugin_settings_tabs[HUT_Common::GENERAL_SETTINGS_TAB] = 'General';
		$this->plugin_settings_tabs[HUT_Common::URL_FILTERS_TAB] = 'URL Filters';
		$this->plugin_settings_tabs[HUT_Common::HEAT_MAPS_TAB] = 'Heat Maps';
		$this->plugin_settings_tabs[HUT_Common::ADVANCED_SETTINGS_TAB] = 'Advanced';
		
		// Create settings page, add JavaScript and CSS
		if( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		}
	
		// Setup AJAX calls
		$this->add_ajax_actions();
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
		PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_click_tap_tbl_query );
	
		$create_url_filters_tbl_query = 'CREATE TABLE '.$wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME.' (
		'.HUT_URL_Filter_Table::ID_COLUMN.' int(11) NOT NULL AUTO_INCREMENT,
		'.HUT_URL_Filter_Table::URL_COLUMN.' varchar(255),
		PRIMARY KEY  (id)
		) ENGINE=InnoDB AUTO_INCREMENT=1;';
		dbDelta( $create_url_filters_tbl_query );
		
		add_option( HUT_Common::PLUGIN_VERSION_OPTION, HUT_Common::PLUGIN_VERSION, '', 'yes' );
	}
	
	/**
	 * Uninstall plugin
	 *
	 */
	public static function uninstall_plugin() {
		// Delete options
		delete_option( HUT_Common::GENERAL_SETTINGS_KEY ) ;
		delete_option( HUT_Common::ADVANCED_SETTINGS_KEY );
		delete_option( HUT_Common::URL_FILTERS_SETTINGS_KEY );
	
		delete_option( HUT_Common::PLUGIN_VERSION_OPTION );
	
		// Drop tables
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_Common::CLICK_TAP_TBL_NAME );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . HUT_URL_Filter_Table::URL_FILTER_TBL_NAME );
	}
	
	/**
	 * Retrieve settings from DB and sets default options if not set
	 */
	function load_settings() {
		$this->general_settings = (array) get_option( HUT_Common::GENERAL_SETTINGS_KEY );
		$this->advanced_settings = (array) get_option( HUT_Common::ADVANCED_SETTINGS_KEY );
		$this->url_filter_settings = (array) get_option( HUT_Common::URL_FILTERS_SETTINGS_KEY );
		
		// Merge with defaults
		$this->general_settings = array_merge( array(
				HUT_Common::SAVE_CLICK_TAP_OPTION => true,
				HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION => true,
				HUT_Common::DEBUG_OPTION => false
		), $this->general_settings );
	
		$this->advanced_settings = array_merge( array(
				HUT_Common::HOT_VALUE_OPTION => 20,
				HUT_Common::SPOT_OPACITY_OPTION => 0.2,
				HUT_Common::SPOT_RADIUS_OPTION => 8,
				HUT_Common::IGNORE_ZOOM_LEVEL_OPTION => false,
				HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION => false,
				HUT_Common::IGNORE_WIDTH_OPTION => false,
				HUT_Common::URL_DB_LIMIT_OPTION => '',
				HUT_Common::WIDTH_ALLOWANCE_OPTION => 6
		), $this->advanced_settings );
		
		$this->url_filter_settings = array_merge( array(
				HUT_Common::APPLY_URL_FILTERS_OPTION => false,
				HUT_Common::FILTER_TYPE_OPTION => 'whitelist'
		), $this->url_filter_settings );
		
		update_option(HUT_Common::GENERAL_SETTINGS_KEY, $this->general_settings);
		update_option(HUT_Common::ADVANCED_SETTINGS_KEY, $this->advanced_settings);
		update_option(HUT_Common::URL_FILTERS_SETTINGS_KEY, $this->url_filter_settings);
	}
	
	/**
	 * Register the General settings
	 */
	function register_general_settings() {	
		register_setting( HUT_Common::GENERAL_SETTINGS_KEY, HUT_Common::GENERAL_SETTINGS_KEY, array( &$this, 'sanitize_general_settings' ) );
		
		add_settings_section( 'section_general', 'General', array( &$this, 'section_general_desc' ), HUT_Common::GENERAL_SETTINGS_KEY );
		
		add_settings_field( HUT_Common::SAVE_CLICK_TAP_OPTION, 'Save mouse clicks and touch screen taps', array( &$this, 'field_save_click_tap' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION, 'Enable drawing heat map', array( &$this, 'field_draw_heat_map_enabled' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
		add_settings_field( HUT_Common::DEBUG_OPTION, 'Debug', array( &$this, 'field_debug' ), HUT_Common::GENERAL_SETTINGS_KEY, 'section_general' );
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
		
		return $input;
	}

	
	/** 
	 * Register the Advanced settings
	 */
	function register_advanced_settings() {
	
		register_setting( HUT_Common::ADVANCED_SETTINGS_KEY, HUT_Common::ADVANCED_SETTINGS_KEY, array( &$this, 'sanitize_advanced_settings' ) );
		
		add_settings_section( 'section_advanced', 'Advanced', array( &$this, 'section_advanced_desc' ), HUT_Common::ADVANCED_SETTINGS_KEY );
		
		add_settings_field( HUT_Common::HOT_VALUE_OPTION, 'Hot value', array( &$this, 'field_hot_value' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::SPOT_RADIUS_OPTION, 'Spot radius', array( &$this, 'field_spot_radius' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::SPOT_OPACITY_OPTION, 'Spot opacity', array( &$this, 'field_spot_opacity' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::IGNORE_WIDTH_OPTION, 'Ignore width', array( &$this, 'field_ignore_width' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::IGNORE_ZOOM_LEVEL_OPTION, 'Ignore zoom level', array( &$this, 'field_ignore_zoom_level' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION, 'Ignore device pixel ratio', array( &$this, 'field_ignore_device_pixel_ratio' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::URL_DB_LIMIT_OPTION, 'URL database limit', array( &$this, 'field_url_db_limit' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
		add_settings_field( HUT_Common::WIDTH_ALLOWANCE_OPTION, 'Width allowance', array( &$this, 'field_width_allowance' ), HUT_Common::ADVANCED_SETTINGS_KEY, 'section_advanced' );
	}
	/**
	 * Advanced settings description
	 */
	function section_advanced_desc() {
		?>
		<p>Each mouse click and touch screen tap is represented as a coloured circle or spot. The spots create a heat map with a colour range from green 
		(cold), orange (warm) and red (hot). The colour of the spot is calculated based on how many other spots it is touching within it's radius (i.e 
		if a spot is touching another spot, then it has a heat  value of 1. If it is touching two spots, then it has a heat value of 2 and so on).</p>
		<?php 
	}
	/** 
	 * Advanced settings fields
	 */
	function field_hot_value() {
		?>
		<input type="text" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::HOT_VALUE_OPTION; ?>]" value="<?php echo esc_attr( $this->advanced_settings[HUT_Common::HOT_VALUE_OPTION] ); ?>" />&nbsp;(must be greater than 0)
		<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
		<?php
	}
	function field_spot_radius() {
		?>
		<input type="text" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::SPOT_RADIUS_OPTION; ?>]" value="<?php echo esc_attr( $this->advanced_settings[HUT_Common::SPOT_RADIUS_OPTION] ); ?>" />&nbsp;(between 1 and 25)
		<p class="description">Set the radius of each spot. Note: This will effect the heat value calculation as spots with a greater radius are more likely to touch other spots.</p>
		<?php
	}
	function field_spot_opacity() {
		?>
		<input type="text" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::SPOT_OPACITY_OPTION; ?>]" value="<?php echo esc_attr( $this->advanced_settings[HUT_Common::SPOT_OPACITY_OPTION] ); ?>" />&nbsp;(between 0.0 and 1.0)
		<p class="description">Set the opacity value of the spots. This is the degree of how much of the background you can see where there are spots.</p>
		<?php
	}
	function field_ignore_zoom_level() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_ZOOM_LEVEL_OPTION; ?>]" value="true" <?php checked(true, $this->advanced_settings[HUT_Common::IGNORE_ZOOM_LEVEL_OPTION], true); ?> />
		<p class="description">You can ignore the zoom level data when drawing the heat map. However, note your website likely appears differently for browser zoom levels.</p>
		<?php 
	}
	function field_ignore_width() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_WIDTH_OPTION; ?>]" value="true" <?php checked(true, $this->advanced_settings[HUT_Common::IGNORE_WIDTH_OPTION], true); ?> />
		<p class="description">You can ignore the width data when drawing the heat map. However, note your website likely appears differently for widths and responsive design.</p>
		<?php 
	}
	function field_ignore_device_pixel_ratio() {
		?>
		<input type="checkbox" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION; ?>]" value="true" <?php checked(true, $this->advanced_settings[HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION], true ); ?>/>
		<p class="description">You can ignore the device pixel ratio data when drawing the heat map. However, note your website likely appears differently for different device pixel ratios.</p>
		<?php 
	}
	function field_url_db_limit() {
		?>
		<input type="text" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::URL_DB_LIMIT_OPTION; ?>]" value="<?php echo esc_attr( $this->advanced_settings[HUT_Common::URL_DB_LIMIT_OPTION] ); ?>" />&nbsp;(leave empty for no limit)
		<p class="description">Generally, large amounts of data collected over a longer period of time does not statistically provider better results. Therefore, you can limit the number of clicks and taps saved per URL. This also improves the performance when drawing the heat map. This condition is checked on every page load to determine whether to allow saving clicks and taps (so this means that once the limit is reached, the page has to be reloaded to stop saving the clicks and taps).</p>
		<?php
	}
	function field_width_allowance() {
		?>
			<input type="text" name="<?php echo HUT_Common::ADVANCED_SETTINGS_KEY; ?>[<?php echo HUT_Common::WIDTH_ALLOWANCE_OPTION; ?>]" value="<?php echo esc_attr( $this->advanced_settings[HUT_Common::WIDTH_ALLOWANCE_OPTION] ); ?>" />&nbsp;pixels (between 0 and 20)
			<p class="description">An allowance to the width when drawing the heat map. This saves time when adjusting the width to draw a heat map as the width does not need to be exact (i.e if the width allowance is 6 pixels and the heat map width is 1600 pixels, then all clicks and taps within width of 1594 pixels to 1606 pixels will also be drawn on the heat map). Note: the larger the width allowance, the less accurate the placement of the clicks and taps on the heat map will likely be.</p>
			<?php
		}
	/**
	 * Sanitize and validate Advanced settings
	 * 
	 * @param unknown_type $input
	 */
	function sanitize_advanced_settings($input) {
		
		// URL database limit option
		if ( isset( $input[HUT_Common::URL_DB_LIMIT_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::URL_DB_LIMIT_OPTION] ) ) {
				$url_db_limit = intval( $input[HUT_Common::URL_DB_LIMIT_OPTION] );
				if ( $url_db_limit <= 0 ) {
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'url_db_limit_range_error', 'URL database limit must be numeric greater than 0 or empty.', 'error');
				}
			} else {
				if (strlen( trim($input[HUT_Common::URL_DB_LIMIT_OPTION])) != 0)
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'url_db_limit_trim_error', 'URL database limit must be numeric greater than 0 or empty.', 'error');
			}
		}
		
		// Width allowance option
		if ( isset( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] ) ) {
				$width_allowance = intval( $input[HUT_Common::WIDTH_ALLOWANCE_OPTION] );
				if ( $width_allowance < 0 || $width_allowance > 20) {
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'width_allowance_range_error', 'Width allowance must be numeric between 0 and 20.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'width_allowance_format_error', 'Width allowance must be numeric between 0 and 20.', 'error');
			}
		
		}
		
		// hot value option
		if ( isset( $input[HUT_Common::HOT_VALUE_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::HOT_VALUE_OPTION] ) ) {
				$hot_value = intval( $input[HUT_Common::HOT_VALUE_OPTION] );
				if ( $hot_value <= 0 ) {
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'hot_value_range_error', 'Hot value must be numeric greater than 0.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'hot_value_non_numeric_error', 'Hot value must be numeric greater than 0.', 'error');
			}
		}
		
		// spot opacity option
		if ( isset( $input[HUT_Common::SPOT_OPACITY_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::SPOT_OPACITY_OPTION] ) ) {
				$spot_opacity = floatval( $input[HUT_Common::SPOT_OPACITY_OPTION] );
				if ( $spot_opacity < 0 || $spot_opacity > 1 ) {
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'spot_opacity_range_error', 'Spot opacity must be numeric between 0 and 1.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'spot_opacity_non_numeric_error', 'Spot opacity must be numeric between 0 and 1.', 'error');
			}
		}
		
		// spot radius option
		if ( isset( $input[HUT_Common::SPOT_RADIUS_OPTION] ) ) {
			if ( is_numeric( $input[HUT_Common::SPOT_RADIUS_OPTION] ) ) {
				$spot_radius = intval( $input[HUT_Common::SPOT_RADIUS_OPTION] );
				if ( $spot_radius < 1 && $spot_radius > 25 ) {
					add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'spot_radius_range_error', 'Spot radius must be numeric between 1 and 25.', 'error');
				}
			} else {
				add_settings_error( HUT_Common::ADVANCED_SETTINGS_KEY, 'spot_radius_non_numeric_error', 'Spot radius must be numeric between 1 and 25.', 'error');
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
		
		return $input;
	}
	
	/**
	 * Register the URl Filter settings
	 */
	function register_url_filter_settings() {
		register_setting( HUT_Common::URL_FILTERS_SETTINGS_KEY, HUT_Common::URL_FILTERS_SETTINGS_KEY, array( &$this, 'sanitize_url_filters_settings' ) );
	
		add_settings_section( 'section_url_filter', 'URL Filters', array( &$this, 'section_url_filter_desc' ), HUT_Common::URL_FILTERS_SETTINGS_KEY );
	
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
	 * Creates the Settings page with the following tabs: General, Heat Maps, URl Filters and Advanced
	 *
	 * @since 2.0	
	 */
	public function add_admin_menus() {
		add_menu_page( __( 'Hotspots User Tracker', HUT_Common::PLUGIN_ID ), __( 'Hotspots User Tracker', HUT_Common::PLUGIN_ID ), 'manage_options', HUT_Common::MENU_PAGE_SLUG, array( &$this, 'plugin_options_page' ), null, null );
	}
	
	function plugin_options_page() {
		?>
	    <div class="wrap">
			<div class="icon32" id="icon-tools"></div><h2>Hotspots User Tracker</h2>
			
			<?php 
	        //simple check to find out if activate/deactivate is required
	        //if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots.php'))
	        //	echo '<div class="error"><p>You need to deactivate and then re-activate the plugin to complete the plugin uprade.</p></div>';
			?>
			<p>View a heat map of mouse clicks and touch screen taps overlayed on your webpage allowing you to improve usability by analysing user behaviour.</p>
	    
	    	
	        <?php
	        $this->plugin_options_tabs(); 
	        ?>
	    </div>
	    <?php
	}
	
	/**
	 * Displays an options tab
	 * 
	 * @param unknown_type $tab
	 */
	function do_plugin_option_tab($tab) {
		if ( isset( $_GET['updated'] ) && isset( $_GET['page'] ) ) {
			add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
		}
		settings_errors();
	
		if ($tab == HUT_Common::HEAT_MAPS_TAB)
			$this->do_heat_maps_tab();
		else if ($tab == HUT_Common::URL_FILTERS_TAB)
			$this->do_url_filters_tab();
		else if ($tab == HUT_Common::GENERAL_SETTINGS_TAB)
			$this->do_general_settings_tab();
		else if ($tab == HUT_Common::ADVANCED_SETTINGS_TAB)
			$this->do_advanced_settings_tab();
	}
	
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
	/**
	 * Clears the database. Can be overriden to clear from multiple tables.
	 */
	function clear_database() {
		global $wpdb;
		$error_message = "";
		$success_message = "";
		try {
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE 1' );
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
	 * Dispalys the Advanced Settings tab
	 */
	function do_advanced_settings_tab() {
		if ( isset( $_POST['clear-db-flag'] ) && $_POST['clear-db-flag'] === "true" )
			$this->clear_database();
		
		?>
		<form method="post" action="options.php">
		<?php
			wp_nonce_field( 'update-options' );
			settings_fields( HUT_Common::ADVANCED_SETTINGS_KEY );
			do_settings_sections( HUT_Common::ADVANCED_SETTINGS_KEY );
			submit_button();
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
	
	/**
	 * Displays the URL Filters tab
	 */
	function do_url_filters_tab() {
		
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
		}
		
		?>
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

		<br />
		
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
	 * Displays the heat maps tab
	 */
	function do_heat_maps_tab() {
		?>
		<h3>Heat Maps</h3>
		<p>Different heat maps are drawn when you resize the window width, modify zoom levels and device pixel ratios to cater for responsive 
		design. Ensure the enable drawing heat map option is enabled. You can also manually view the heat maps by adding a query parameter 
		drawHeatMap=true to the URL (i.e. http://www.mywebsite.com?drawHeatMap=true and http://www.mywebsite.com?cat=1&drawHeatMap=true).</p>
				        		        
		<?php		        
		$hut_heat_map_table = new HUT_Heat_Map_Table();
		$hut_heat_map_table->prepare_items();
		$hut_heat_map_table->display();		        
	}
	
	/**
	 * Displays the plugin tab headers
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : HUT_Common::GENERAL_SETTINGS_TAB;
	
		screen_icon();
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . HUT_Common::MENU_PAGE_SLUG . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
		
		$this->do_plugin_option_tab($current_tab);
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
		}
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
		$spot_radius = $this->advanced_settings[HUT_Common::SPOT_RADIUS_OPTION];
	
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
	
			$ignore_width = $this->advanced_settings[ HUT_Common::IGNORE_WIDTH_OPTION];

			if ( $ignore_width == false ) {
				// allow a range either side to be the same
				$width_allowance = $this->advanced_settings[ HUT_Common::WIDTH_ALLOWANCE_OPTION ];
				$width = 0;
				if ( isset($_POST['width'] ) )
					$width = intval($_POST['width']);
				$diff_left = $width - $width_allowance;
				$diff_right = $width + $width_allowance;
				$query .= ' AND '.HUT_Common::WIDTH_COLUMN.' >= ' . $diff_left . ' AND '.HUT_Common::WIDTH_COLUMN.' <= '. $diff_right;
			}
			
			$ignore_zoom_level = $this->advanced_settings[ HUT_Common::IGNORE_ZOOM_LEVEL_OPTION];
			if ( $ignore_zoom_level == false ) {
				$zoom_level = 1;
				if ( isset($_POST['zoomLevel'] ) )
					$zoom_level = doubleval( $_POST['zoomLevel'] );
	
				$query .= ' AND ' . HUT_Common::ZOOM_LEVEL_COLUMN . ' = ' . $zoom_level;
			}
	
			$ignore_device_pixel_ratio = $this->advanced_settings[ HUT_Common::IGNORE_DEVICE_PIXEL_RATIO_OPTION];
			if ( $ignore_device_pixel_ratio == false ) {
				$device_pixel_ratio = 1;
				if ( isset($_POST['devicePixelRatio']) )
					$device_pixel_ratio = doubleval( $_POST['devicePixelRatio'] );
	
				$query .= ' AND ' . HUT_Common::DEVICE_PIXEL_RATIO_COLUMN . ' = ' . $device_pixel_ratio;
			}
	
			if ( isset($_POST['clickTapId']) && $_POST['clickTapId'] !== null && $_POST['clickTapId'] !== "") {
				$click_tap_id = intval( $_POST['clickTapId'] );
				$query .= ' AND ' . HUT_Common::ID_COLUMN . ' = ' . $click_tap_id;
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
							HUT_Common::SESSION_ID_COLUMN => session_id()
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
				$width_allowance = $this->advanced_settings[ HUT_Common::WIDTH_ALLOWANCE_OPTION ];
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
}




if (!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * A table for viewing heat map data for URLs including width,
 * zoom level and device pixel ratio.
 *
 * @author dpowney
 *
 */
class HUT_Heat_Map_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'Heat Map Data',
				'plural' => 'Heat Map Data',
				'ajax'	=> false
		) );
	}

	/** (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "bottom" ){
			echo '';
		}
		if ( $which == "bottom" ){
			?>
			<p>Width: Inner width of the browser window, excluding any vertical scrollbar if present and also including any remaining 
			horizontal scroll. Zoom Level: Percentage of browser zoom. Device pixel ratio: Ratio of device pixels compared with CSS pixels.</p>
			<?php 
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				'id' => __(''),
				HUT_Common::URL_COLUMN =>__('URL'),
				'count' => __('Count'),
				'heatMaps' => __('Heat Maps')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array('id');
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT '. HUT_Common::URL_COLUMN . ', COUNT(*) AS count, uuid() AS id FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE 1 GROUP BY '.HUT_Common::URL_COLUMN . ' ORDER BY count DESC';

		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		$items_per_page = 5;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if (empty($page_num) || !is_numeric($page_num) || $page_num<=0 ) {
			$page_num = 1;
		}
		$total_pages = ceil($item_count/$items_per_page);
		// adjust the query to take pagination into account
		if (!empty($page_num) && !empty($items_per_page)) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );

		$this->items =  $wpdb->get_results($query, ARRAY_A);
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case HUT_Common::URL_COLUMN :
			case 'count':
			case 'heatMaps':
			case 'id':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ;
		}
	}

	/**
	 * url column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_url($item){
		$url = $item[HUT_Common::URL_COLUMN];
		echo stripslashes($url);
	}

	/**
	 *
	 * @param unknown_type $item
	 */
	function column_count($item) {
		// get widths for url, and create a select
		$totalCount = $item['count'] . ' ';
		$url = $item['url'];
		global $wpdb;
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE ' . HUT_Common::URL_COLUMN . ' = "' . $url . '" AND ' . HUT_Common::IS_TAP_COLUMN . ' = 1';
		$tapCount = $wpdb->query($query); //return the total number of affected rows
		echo $totalCount . ' (' . ($totalCount - $tapCount) . ' clicks & ' . $tapCount . ' taps)';
	}
	
	/**
	 *
	 * @param unknown_type $item
	 */
	function column_heatMaps( $item ) {
		global $wpdb;
		$width_query = 'SELECT '. HUT_Common::WIDTH_COLUMN . ', COUNT(*) AS count FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE url = "'. $item[HUT_Common::URL_COLUMN] . '" GROUP BY '.HUT_Common::WIDTH_COLUMN . ' ORDER BY count DESC';
		$width_rows = $wpdb->get_results( $width_query );
		$url = $item[HUT_Common::URL_COLUMN];
		$id = $item['id'];

		echo '<table class="widefat" cellspacing="0">';
		echo '<thead><tr><th class="manage-column">Width</th><th class="manage-column">Zoom Level</th><th class="manage-column" >Device Pixel Ratio</th><th class="manage-column">Count</th><th class="manage-column">Action</th></tr></thead>';

		echo '<tbody>';
		$row_count = 0;
		foreach ($width_rows as $width_row) {
			// get width column data
			$width = $width_row->width;
			$width_count = $width_row->count;
				
			// get zoom level and device pixel ratio column data
			$browser_device_query = 'SELECT '. HUT_Common::ZOOM_LEVEL_COLUMN . ', ' . HUT_Common::DEVICE_PIXEL_RATIO_COLUMN . ', COUNT(*) AS count FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE url = "'. $item[HUT_Common::URL_COLUMN] . '" AND width = "' . $width . '" GROUP BY '.HUT_Common::ZOOM_LEVEL_COLUMN . ', ' . HUT_Common::DEVICE_PIXEL_RATIO_COLUMN . ' ORDER BY count DESC';
			$browser_device_rows = $wpdb->get_results($browser_device_query);
			
			$row_class = '';
			if ( ( $row_count++ % 2 ) == 0 )
				$row_class = 'alternate';
			$row_span = count($browser_device_rows);
			
			echo '<tr class="' . $row_class . '">';
			echo '<td class="column-width" rowspan="' . $row_span . '">'. $width . 'px</td>';
			
			$index = 0;
			foreach ($browser_device_rows as $browser_device_row) {
				$zoom_level = $browser_device_row->zoom_level;
				$count = $browser_device_row->count;
				$device_pixel_ratio = $browser_device_row->device_pixel_ratio;
				
				if ($index > 0)
					echo '<tr class="' . $row_class . '">';
				
				$td_style = '';
				if ($row_span > 1)
					$td_style = 'border-left: 1px solid #dfdfdf;';
				
				echo '<td style="' . $td_style . '">' . ($zoom_level * 100). '%</td><td>' . HUT_Common::convert_decimalto_ratio($device_pixel_ratio) . '</td>';
				echo '<td style="' . $td_style . '">' . $count . '</td>';
				echo '<td style="' . $td_style . '">';
				echo '<input id="' . $id . $index .'" type="button" class="button view-heat-map-button" value="View Heat Map" />';
				echo '<input type="hidden" id="url_' . $id . $index . '" name="url_' . $id . $index . '" value="' . $url  .'"></input>';
				echo '<input type="hidden" id="data_' . $id . $index . '" name="data_' . $id . $index . '" value="_' . $width . '_' . $device_pixel_ratio . '_' . $zoom_level . '"></input>';
				echo '</td>';
				
				echo '</tr>';
				$index++;
			}
		}
		echo '</tbody></table>';
	}
}

/**
 * HUT_URL_Filter_Table class used for whitelist or blacklist filtering of URL's
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_URL_Filter_Table extends WP_List_Table {

	const
	URL_COLUMN 						= 'url',
	ID_COLUMN 						= 'id',
	CHECKBOX_COLUMN 				= 'cb',
	URL_LABEL 						= 'URL',
	ID_LABEL 						= "ID",
	DELETE_CHECKBOX 				= 'delete[]',
	URL_FILTER_TBL_NAME 			= 'hut_url_filter',
	DELETE_BULK_ACTION_NAME			= 'delete',
	DELETE_BULK_ACTION_LABEL		= 'Delete';


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'URL Filter Table',
				'plural' => 'URL Filter Table',
				'ajax'	=> false
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//echo '<br />';
		}
		if ( $which == "bottom" ){
			echo '<br />';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				HUT_URL_Filter_Table::CHECKBOX_COLUMN => '<input type="checkbox" />',
				HUT_URL_Filter_Table::URL_COLUMN =>__(HUT_URL_Filter_Table::URL_LABEL),
				HUT_URL_Filter_Table::ID_COLUMN => __('')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Process any bulk actions first
		$this->process_bulk_action();

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array(HUT_URL_Filter_Table::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME;

		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$items_per_page = 10;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if ( empty( $page_num ) || !is_numeric( $page_num ) || $page_num <= 0 ) {
			$page_num = 1;
		}
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( !empty( $page_num ) && !empty( $items_per_page ) ) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int) $offset. ',' .(int) $items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );



		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case HUT_URL_Filter_Table::CHECKBOX_COLUMN :
			case HUT_URL_Filter_Table::ID_COLUMN :
			case HUT_URL_Filter_Table::URL_COLUMN :
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ;
		}
	}

	/**
	 * description column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_url( $item ){
		echo stripslashes( $item[HUT_URL_Filter_Table::URL_COLUMN] );
	}

	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="'.HUT_URL_Filter_Table::DELETE_CHECKBOX.'" value="%s" />', $item[HUT_URL_Filter_Table::ID_COLUMN]
		);
	}
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
				HUT_URL_Filter_Table::DELETE_BULK_ACTION_NAME => HUT_URL_Filter_Table::DELETE_BULK_ACTION_LABEL
		);
		return $actions;
	}

	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		if ($this->current_action() === HUT_URL_Filter_Table::DELETE_BULK_ACTION_NAME) {
			global $wpdb;

			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );
				
			foreach($checked as $id) {
				$query = "DELETE FROM ". $wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME . " WHERE " .  HUT_URL_Filter_Table::ID_COLUMN . " = " . $id;
				$results = $wpdb->query( $query );
			}
		}
	}
}

?>