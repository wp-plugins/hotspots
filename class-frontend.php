<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-common.php';

/**
 * Frontend class
 *
 * @author dpowney
 *
 */
class HUT_Frontend {

	/**
	 * Constructor
	 *
	 * @since 2.4
	 */
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Javascript and CSS used by the 
	 *
	 * @since 2.0
	 */
	public function assets(){
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( HUT_Common::PLUGIN_ID . '-frontend-style' , plugins_url( 'css/frontend.css', __FILE__ ) );
		wp_enqueue_script( 'detect-zoom', plugins_url( 'js/detect-zoom.js', __FILE__ ), array(), false, true );
		wp_enqueue_script( HUT_Common::PLUGIN_ID . '-frontend-script', plugins_url( 'js/frontend.js', __FILE__ ), array( 'jquery', 'detect-zoom' ), false, true );

		$advanced_settings = get_option(HUT_Common::ADVANCED_SETTINGS_KEY);
		$general_settings = get_option(HUT_Common::GENERAL_SETTINGS_KEY);
		$url_filter_settings = get_option(HUT_Common::URL_FILTERS_SETTINGS_KEY);
		
		$draw_heat_map_enabled = $general_settings[ HUT_Common::DRAW_HEAT_MAP_ENABLED_OPTION ];
		$save_click_or_tap_enabled = $general_settings[ HUT_Common::SAVE_CLICK_TAP_OPTION ];

		$current_URL = addslashes( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] );
		$current_URL = HUT_Common::normalize_url( $current_URL );

		// Check options if applying filters
		$apply_URL_filters = $url_filter_settings[ HUT_Common::APPLY_URL_FILTERS_OPTION ];
		// Also check if at least one of the options is true to improve performance
		if ( $apply_URL_filters == true && ( $draw_heat_map_enabled == true || $save_click_or_tap_enabled == true ) ) {
			// check if enabled
			$filter_type = $url_filter_settings[ HUT_Common::FILTER_TYPE_OPTION ];

			global $wpdb;
			$query = 'SELECT ' .HUT_URL_Filter_Table::URL_COLUMN . ' FROM ' . $wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME;
			$rows = $wpdb->get_results( $query );

			if ( $filter_type == 'blacklist' ) { // excludes
				foreach  ($rows as $row ) {
					$url = HUT_Common::normalize_url( $row->url );
					
					// If it's in the blacklist, we disable the options
					if ( $url == $current_URL ) {
						$save_click_or_tap_enabled = 0;
						break;
					}
				}
			} else { // whitelist (includes)
				// check if the current url is in the whitelist
				$found = false;
				foreach ( $rows as $row ) {
					$url = HUT_Common::normalize_url( $row->url );

					// If it's not in the whitelist, we disable the options
					if ( $url == $current_URL ) {
						$found = true;
						break;
					}
				}
				
				if ( $found == false ) {
					$save_click_or_tap_enabled = 0;
				}
			}
		}

		// check URL db limit option
		$url_db_limit = $advanced_settings[ HUT_Common::URL_DB_LIMIT_OPTION ];
		if ( $save_click_or_tap_enabled == true && $url_db_limit != '' ) {
			global $wpdb;
			$query = 'SELECT * FROM '. $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' WHERE ' . HUT_Common::URL_COLUMN . ' = "' . $current_URL . '"';
			$wpdb->query( $query );
			$count = $wpdb->num_rows;
			if ( $count >= $url_db_limit ) {
				$save_click_or_tap_enabled = 0;
			}
		}
		

		$config_array = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxNonce' => wp_create_nonce( HUT_Common::PLUGIN_ID.'-nonce' ),
				'drawHeatMapEnabled' => $draw_heat_map_enabled,
				'saveClickOrTapEnabled' => $save_click_or_tap_enabled,
				'debug' => $general_settings[ HUT_Common::DEBUG_OPTION ],
				'hotValue' => $advanced_settings[HUT_Common::HOT_VALUE_OPTION],
				'spotOpacity' =>  $advanced_settings[ HUT_Common::SPOT_OPACITY_OPTION ],
				'spotRadius' =>  $advanced_settings[ HUT_Common::SPOT_RADIUS_OPTION ],
				'filterType' => $url_filter_settings[ HUT_Common::FILTER_TYPE_OPTION ],
		);
		wp_localize_script( HUT_Common::PLUGIN_ID . '-frontend-script', HUT_Common::CONFIG_DATA, $config_array );
	}
}
