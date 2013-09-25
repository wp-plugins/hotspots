<?php 


/**
 * Common class
 * 
 * @author dpowney
 *
 */
class HUT_Common {
	
	const
	PLUGIN_ID 							= 'hotspots',
	CONFIG_DATA 						= 'configData',
	
	// database
	PLUGIN_VERSION						= '3.4.0',
	
	CLICK_TAP_TBL_NAME 					= 'hut_click_tap',
	URL_PING_TBL_NAME					= 'hut_url_ping',
	AJAX_PING_TBL_NAME					= 'hut_ajax_ping',
	ELEMENT_SELECTOR_TBL_NAME			= 'hut_element_selector',
	ELEMENT_SELECTOR_PING_TBL_NAME 		= 'hut_element_selector_ping',
	
	ID_COLUMN							= 'id',
	X_COLUMN							= 'x',
	Y_COLUMN							= 'y',
	URL_COLUMN							= 'url',
	WIDTH_COLUMN						= 'width',
	ZOOM_LEVEL_COLUMN					= 'zoom_level',
	IS_TAP_COLUMN						= 'is_tap',
	IP_ADDRESS_COLUMN					= 'ip_address',
	DEVICE_PIXEL_RATIO_COLUMN			= 'device_pixel_ratio',
	CREATED_DATE_COLUMN					= 'created_date',
	SESSION_ID_COLUMN					= 'session_id',
	ROLE_COLUMN							= 'role',
	USER_LOGIN							= 'user_login',
	RECORD_DATE_COLUMN					= 'record_date',
	STATUS_TEXT_COLUMN					= 'status_text',
	AJAX_ACTION_COLUMN					= 'ajax_action',
	FORM_ID_COLUMN						= 'form_id',
	ELEMENT_SELECTOR_COLUMN				= 'element_selector',
	NAME_COLUMN							= 'name',
	IS_FORM_SUBMIT_COLUMN				= 'is_form_submit',
	USER_ID_COLUMN						= 'user_id',
	
	// Options
	SAVE_CLICK_TAP_OPTION 				= 'save_click_tap',
	DRAW_HEAT_MAP_ENABLED_OPTION 		= 'draw_heat_map_enabled',
	DEBUG_OPTION 						= 'debug_option',
	HOT_VALUE_OPTION 					= 'hot_value',
	SPOT_OPACITY_OPTION 				= 'spot_opacity',
	SPOT_RADIUS_OPTION 					= 'spot_radius',
	FILTER_TYPE_OPTION					= 'filter_type',
	APPLY_URL_FILTERS_OPTION			= 'apply_url_filters',
	PLUGIN_VERSION_OPTION				= 'hut_plugin_version',
	IGNORE_ZOOM_LEVEL_OPTION			= 'ignore_zoom_level',
	USE_HEATMAPJS_OPTION				= 'use_heatmapjs',
	IGNORE_DEVICE_PIXEL_RATIO_OPTION 	= 'ignore_device_pixel_ratio',
	IGNORE_WIDTH_OPTION					= 'ignore_width',
	IGNORE_DEVICE_OPTION				= 'ignore_device',
	IGNORE_BROWSER_FAMILY_OPTION		= 'ignore_browser_family',
	IGNORE_OS_FAMILY_OPTION				= 'ignore_os_family',
	
	URL_DB_LIMIT_OPTION					= 'url_db_limit',
	WIDTH_ALLOWANCE_OPTION				= 'width_allowance',
	SCHEDULED_START_DATE_OPTION			= 'schedule_start_date',
	SCHEDULED_END_DATE_OPTION			= 'schedule_end_date',
	SCHEDULED_SAVE_CLICK_TAP			= 'schedule_save_click_tap',
	HIDE_ROLES_OPTION					= 'hide_roles',
	START_DATE_SEARCH_INPUT				= 'start_date',
	END_DATE_SEARCH_INPUT				= 'end_date',
	URL_SEARCH_INPUT					= 'url',
	IP_ADDRESS_OPTION					= 'ip_address',
	HAS_VISITED_URL_OPTION				= 'has_visited_url',
	ELEMENT_SELECTOR_INPUT				= 'element_selector',
	NAME_INPUT							= 'name',
	NO_ROLE_VALUE						= "none",
	
	// Settings
	GENERAL_SETTINGS_KEY 				= 'hut_general_settings',
	ADVANCED_SETTINGS_KEY				= 'hut_advanced_settings',
	URL_FILTERS_SETTINGS_KEY 			= 'hut_url_filters_settings',
	HEAT_MAP_SETTINGS_KEY 				= 'hut_heat_map_settings_key',
	SCHEDULE_SETTINGS_KEY				= 'hut_schedule_settings_key',
	DATABASE_SETTINGS_KEY				= 'hut_database_settings_key',
	
	// Tabs
	GENERAL_SETTINGS_TAB 				= 'hut_general_settings_tab',
	HEAT_MAP_SETTINGS_TAB				= 'hut_heat_map_settings_tab',
	SCHEDULE_SETTINGS_TAB				= 'hut_schedule_settings_tab',
	DATABASE_SETTINGS_TAB				= 'hut_database_settings_tab',
	URL_FILTER_SETTINGS_TAB 			= 'hut_url_filter_settings_tab',
	HEAT_MAPS_TAB 						= 'hut_heat_maps_tab',
	USERS_TAB							= 'users_tab',
	ELEMENT_SETTINGS_TAB				= 'elements_tab',
	REPORTS_TAB							= 'reports_tab',
	USER_ACTIVITY_TAB 					= 'user_activity_tab',
	STATISTICS_TAB 						= 'statistics_tab',
	
	MENU_PAGE_SLUG						= 'hut_menu_page',
	
	SAVE_AJAX_ACTIONS_OPTION		= 'save_ajax_actions',
	SAVE_ELEMENT_SELECTORS_OPTION	= 'save_element_selectors',
	SAVE_PAGE_LOADS_OPTION			= 'save_page_loads';
	
	// URL query params which are ignored by the plugin
	public static $ignore_query_params = array( 'drawHeatMap', 'KEY', 'XDEBUG_SESSION_START', 'clickTapId', 'width', 'devicePixelRatio', 'zoomLevel', 'device', 'browserFamily', 'osFamily' );
	
	/**
	 * Gets the client IP address
	 */
	public static function get_ip_address() {
		$ip_address = '';
		if ( isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'])
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		else if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'])
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if ( isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] )
			$ip_address = $_SERVER['HTTP_X_FORWARDED'];
		else if ( isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] )
			$ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
		else if ( isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] )
			$ip_address = $_SERVER['HTTP_FORWARDED'];
		else if ( ISSET($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] )
			$ip_address = $_SERVER['REMOTE_ADDR'];
		
		return $ip_address;
	}
	
	public static function update_session() {
		
	}
	
	/**
	 * Gets the current URL
	 * 
	 * @return current URL
	 */
	public static function get_current_url() {
		$url = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$url .= "s";
		}
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		return HUT_Common::normalize_url($url);
	}
	
	/**
	 * Normalizes the URL (some of the best parts of RFC 3986)
	 *
	 * @param unknown_type $url
	 * @return string
	 */
	public static function normalize_url($url) {
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
		$port = '';
		if (isset($parsed_url['port']))
			$port = $parsed_url['port'];
		if ($port == 80)
			$port = '';
		
		// Removing the fragment # (do not get fragment component)
		
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
		$query = '';
		if (isset($parsed_url['query']))
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
		
		// Remove some query params which we do not want
		$url = HUT_Common::remove_query_string_params($url, HUT_Common::$ignore_query_params);
		
		return $url;
	}
	
	/**
	 * Removes query string parameters from URL
	 * @param $url
	 * @param $param
	 * @return string
	 *
	 * @since 1.2
	 */
	public static function remove_query_string_params( $url, $params ) {
		foreach ( $params as $param ) {
			$url = preg_replace( '/(.*)(\?|&)' . $param . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&' );
			$url = substr( $url, 0, -1 );
		}
		return $url;
	}
	
	/**
	 * Helper function to get the highest common factor. Can be used recursively.
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @return unknown
	 */
	public static function highest_common_factor($a, $b) {
		if ($b==0)
			return $a;
		return HUT_Common::highest_common_factor($b, $a % $b);
	}
	
	/**
	 * Converts a decimal to a fraction that can be returned as a ratio
	 *
	 * @param decimal i.e. 1.75
	 */
	public static function convert_decimalto_ratio($decimal) {
		$decimal = strval($decimal);

		$decimal_array = explode('.', $decimal);
	
		// if a whole number
		if (count($decimal_array) !== 2) {
			return $decimal . ':1';
		} else {
			$left_decimal_part = $decimal_array[0]; // 1
			$right_decimal_part = $decimal_array[1]; // 75

			$numerator = $left_decimal_part . $right_decimal_part; // 175
			$denominator = pow(10,strlen($right_decimal_part)); // 100
			$factor = HUT_Common::highest_common_factor($numerator, $denominator); // 25
			$denominator /= $factor;
			$numerator /= $factor;

			return $numerator . ':' . $denominator;
		}
	}
	
	/**
	 * Checks if date format is valid yyyy-mm-dd
	 * @param unknown_type $date
	 * @return boolean
	 */
	function check_date_format($date) {
		list($yyyy, $mm, $dd) = explode('-',$date);
		return checkdate($mm,$dd,$yyyy);
	}
}

?>