<?php 
/*
Plugin Name: Hotspots User Tracker
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: The most advanced free analytics plugin for user tracking and heat maps overlayed on your website.
Version: 3.4.5
Author: Daniel Powney
Auhtor URI: www.danielpowney.com
License: GPL2
*/
?>
<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-admin.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-frontend.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class-common.php';

// instantiate admin and front end
global $admin;
global $frontend;
if (is_admin()) {
	$admin = new HUT_Admin();
} else {
	$frontend = new HUT_Frontend();
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'updates.php';

// Activation and deactivation
register_activation_hook( __FILE__, 'hut_activate_plugin');
register_uninstall_hook( __FILE__, 'hut_uninstall_plugin' );
//register_deactivation_hook( __FILE__, 'hut_uninstall_plugin' );
function hut_activate_plugin() {
	if (is_admin()) {
		HUT_Admin::activate_plugin();
	}

}
function hut_uninstall_plugin() {
	if (is_admin()) {
		HUT_Admin::uninstall_plugin();
	} 
}

// ensure a session has been started
session_start();

?>