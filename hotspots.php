<?php 
/*
Plugin Name: Hotspots Analytics
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: The most advanced analytics plugin for WordPress websites including heatmaps, user activity and custom event tracking.
Version: 4.0
Author: Daniel Powney
Auhtor URI: danielpowney.com
License: GPL2
*/

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'admin-controller.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'frontend-controller.php';

$hut_admin_controller = null;
$hut_frontend_controller = null;
if (is_admin()) {
	$hut_admin_controller = new HUT_Admin_Controller();
} else {
	$hut_frontend_controller = new HUT_Frontend_Controller();
}

// Activation and deactivation
register_activation_hook( __FILE__, 'hut_activate_plugin');
register_uninstall_hook( __FILE__, 'hut_uninstall_plugin' );
register_deactivation_hook( __FILE__, 'hut_uninstall_plugin' );
function hut_activate_plugin() {
	if (is_admin()) {
		HUT_Admin_Controller::activate_plugin();
	}

}
function hut_uninstall_plugin() {
	if (is_admin()) {
		HUT_Admin_Controller::uninstall_plugin();
	}
}

// ensure a session has been started
session_start();

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'update-check.php';



?>