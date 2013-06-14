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
	PLUGIN_VERSION						= '3.1.0',
	CLICK_TAP_TBL_NAME 					= 'hut_click_tap',
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
	IGNORE_DEVICE_PIXEL_RATIO_OPTION 	= 'ignore_device_pixel_ratio',
	IGNORE_WIDTH_OPTION					= 'ignore_width',
	URL_DB_LIMIT_OPTION					= 'url_db_limit',
	WIDTH_ALLOWANCE_OPTION				= 'width_allowance',
	SCHEDULED_START_DATE_OPTION			= 'schedule_start_date',
	SCHEDULED_END_DATE_OPTION			= 'schedule_end_date',
	SCHEDULED_SAVE_CLICK_TAP			= 'schedule_save_click_tap',
	HIDE_ROLES_OPTION					= 'hide_roles',
	
	// Settings
	GENERAL_SETTINGS_KEY 				= 'hut_general_settings',
	ADVANCED_SETTINGS_KEY				= 'hut_advanced_settings',
	URL_FILTERS_SETTINGS_KEY 			= 'hut_url_filters_settings',
	HEAT_MAPS_SETTINGS_KEY 				= 'hut_heat_maps_settings_key',
	
	GENERAL_SETTINGS_TAB 				= 'hut_general_settings_tab',
	ADVANCED_SETTINGS_TAB				= 'hut_advanced_settings_tab',
	URL_FILTERS_TAB 					= 'hut_url_filters_tab',
	HEAT_MAPS_TAB 						= 'hut_heat_maps_tab',
	
	MENU_PAGE_SLUG						= 'hut_menu_page',
	
	NO_ROLE_VALUE						= "none";
	
	// URL query params which are ignored by the plugin
	public static $ignore_query_params = array( 'drawHeatMap', 'KEY', 'XDEBUG_SESSION_START', 'clickTapId', 'width', 'devicePixelRatio', 'zoomLevel' );
	
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
	
			$numerator = $left_decimal_part + $right_decimal_part; // 175
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