<?php

// Check if we need to do an upgrade from a previous version
$previous_plugin_version = get_option( HUT_Common::PLUGIN_VERSION_OPTION );
if ( $previous_plugin_version != HUT_Common::PLUGIN_VERSION ) {

	// from version 2.x to 3.x, we also need to check the presence of a DB table which has been renamed
	global $wpdb;
	$old_hotspots_tbl_name =  $wpdb->prefix . 'hotspot';
	$renamed_hotspots_tbl = ( $wpdb->get_var('SHOW TABLES LIKE "' . $old_hotspots_tbl_name . '"') == $old_hotspots_tbl_name );

	// This stuff is temporary until most users have upgraded
	if ( empty($previous_plugin_version) && ($renamed_hotspots_tbl) ) {

		// Delete old options
		delete_option( 'savedMouseClicks') ;
		delete_option( 'drawHotSpotsEnabled' );
		delete_option( 'debug' );
		delete_option( 'hotValue' );
		delete_option( 'spotOpacity' );
		delete_option( 'spotRadius' );
		delete_option( 'filterType' );
		delete_option( 'applyFilters' );
		delete_option( 'dbVersion' );
		delete_option( 'ignoreZoomLevel' );
		delete_option( 'ignoreDevicePixelRatio');
		delete_option( 'maxClicksAndTapsPerURL' );

		try {
			// Delete old files that are no longer used and have been renamed and or moved
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'detect-zoom.js'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'detect-zoom.js');
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots.css'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots.css');
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots.js'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots.js');
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots-admin.css'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots-admin.css');
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots-admin.js'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hotspots-admin.js');
			if (file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tables.php'))
				unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tables.php');
		} catch (Exception $e) {
			die('An error occured updating the plugin file structure! Try manually deleting the plugin files to fix the problem.');
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		try {
			// Database table modifications
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
				
		} catch (Exception $e) {
			die('An error occured updating the plugin database tables!');
		}

		// Data migration of old tables
		try {
			if ($renamed_hotspots_tbl) {
				$wpdb->query('INSERT INTO ' . $wpdb->prefix . HUT_Common::CLICK_TAP_TBL_NAME . ' SELECT "", x, y, url, screenWidth, isTouch, ipAddress, zoomLevel, devicePixelRatio, createdDate, ""  FROM ' . $old_hotspots_tbl_name);
				// we wont drop in case something goes wrong
				// $wpdb->query('DROP TABLE IF EXISTS ' . $old_hotspots_tbl_name);
			}

			$old_url_filter_tbl_name = $wpdb->prefix . 'filter';
			if ($wpdb->get_var('SHOW TABLES LIKE "' . $old_url_filter_tbl_name . '"') == $old_url_filter_tbl_name) {
				$wpdb->query('INSERT INTO ' .$wpdb->prefix . HUT_URL_Filter_Table::URL_FILTER_TBL_NAME . ' SELECT "", url FROM ' . $old_url_filter_tbl_name);
				// we wont drop in case something goes wrong
				// $wpdb->query('DROP TABLE IF EXISTS ' . $old_url_filter_tbl_name);
			}
		} catch (Exception $e) {
			die('An error occured during data migrating of the plugin database tables! Try dropping the old database tables to skip the data migration.');
		}

		// note 1.0 was actually 3.0.x
		$previous_plugin_version = '1.0';
	}

	// Upgrade from 3.0.x to 3.1.x
	// note 1.0 was actually 3.0.x
	if ($previous_plugin_version == '1.0') {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		try {
			// Database table modifications
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
			PRIMARY KEY  ('.HUT_Common::ID_COLUMN.')
			) ENGINE=InnoDB AUTO_INCREMENT=1;';
			dbDelta( $create_click_tap_tbl_query );
		} catch (Exception $e) {
			die('An error occured updating the plugin database tables!');
		}
	}


	if ($previous_plugin_version == '3.1.0') {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		try {
			// Database table modifications
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
		} catch (Exception $e) {
			die('An error occured updating the plugin database tables!');
		}
	}
	
	// from 3.2.0 to 3.4.0
	if ($previous_plugin_version == '3.2.0' && HUT_Common::PLUGIN_VERSION == '3.4.0') {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		try {
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
			
		} catch (Exception $e) {
			die('An error occured updating the plugin database tables!');
		}
		
		// Now update the version if you get this far
		update_option( HUT_Common::PLUGIN_VERSION_OPTION, HUT_Common::PLUGIN_VERSION );
	}
}