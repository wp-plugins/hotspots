<?php 
/*
Plugin Name: HotSpots
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: HotSpots is a plugin which draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing user behaviour.
Version: 1.2.1
Author: Daniel Powney
Auhtor URI: www.danielpowney.com
License: GPL2
*/
?>
<?php


/**
 * Enqueue hotspots scripts and setup local variables
 * 
 * @since 1.0
 */
function assets() {

	wp_enqueue_script('jquery');
	wp_enqueue_script('hotspots', plugins_url('hotspot.js', __FILE__), array('jquery'));
	
	$config_array = array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'ajaxNonce' => wp_create_nonce('hotspot-nonce'),
			'enabled' => get_option('enabled'),
			'showOnClick' => get_option('showOnClick'),
			'warmValue' => get_option('warmValue'),
			'hotValue' => get_option('hotValue'),
			'spotOpacity' => get_option('spotOpacity'),
			'spotRadius' => get_option('spotRadius')
	);
	wp_localize_script('hotspots', 'hotSpotData', $config_array);
	
	if (is_admin()) {
		wp_enqueue_style('hotspots-admin-style', plugins_url('hotspots-admin.css', __FILE__));
		wp_enqueue_script('hotspots-admin', plugins_url('hotspot-admin.js', __FILE__), array('jquery'));
		wp_localize_script('hotspot-admin', 'hotSpotData', $config_array);
		
	}
}
add_action( 'wp_enqueue_scripts', 'assets' );
add_action( 'admin_enqueue_scripts', 'assets' );


/**
 * Create the hotspots DB table
 * 
 * @since 1.0
 */
function createHotSpotDBTable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->query("DROP TABLE IF EXISTS hsp_hotspot");
	$sql1 = "CREATE TABLE hsp_hotspot (
	id int(11) NOT NULL AUTO_INCREMENT,
	x int(11) NOT NULL,
	y int(11) NOT NULL,
	url varchar(255),
	PRIMARY KEY (id)
	) ENGINE=InnoDB AUTO_INCREMENT=1;";
	dbDelta($sql1);
}
register_activation_hook(__FILE__,'createHotSpotDBTable');

/**
 * Add a mouse click to the DB
 * 
 * @since 1.0
 */
function addMouseClick() {
	global $wpdb;
	
	$ajaxNonce = $_POST['nonce'];

	if (wp_verify_nonce($ajaxNonce, 'hotspot-nonce')) {
		$x = isset($_POST['x']) ? $_POST['x'] : '';
		$y = isset($_POST['y']) ? $_POST['y'] : '';
		$url = isset($_POST['url']) ? $_POST['url'] : '';
		$rows_affected = $wpdb->insert( "hsp_hotspot", array( 'x' => $x, 'y' => $y, 'url' => $url ) );
		echo $wpdb->insert_id;
	}
    die(); 
	
}
add_action('wp_ajax_add_mouse_click', 'addMouseClick');
add_action('wp_ajax_nopriv_add_mouse_click', 'addMouseClick');


/**
 * Gets all mouse clicks
 * 
 * @since 1.0
 */
function getMouseClicks() {
	global $wpdb;

	$ajaxNonce = $_POST['nonce'];
	$rows = null;
	if (wp_verify_nonce($ajaxNonce, 'hotspot-nonce')) {
		
		$rows = $wpdb->get_results( "SELECT x, y, url, id FROM hsp_hotspot" );
	}
	echo json_encode($rows);
	die();
}
add_action('wp_ajax_get_mouse_clicks', 'getMouseClicks');
add_action('wp_ajax_nopriv_get_mouse_clicks', 'getMouseClicks');


/**
 * Add hotspots options
 * 
 * @since 1.0
 */
function setHotSpotOptions() {
	add_option('enabled', 'off', '', 'yes');
	add_option('showOnClick', 'off', '', 'yes');
	add_option('hotValue', '20', '', 'yes');
	add_option('spotOpacity', '0.2', '', 'yes');
	add_option('spotRadius', '8', '', 'yes');
}
register_activation_hook(__FILE__,'setHotSpotOptions');

/**
 * Unset hotspot options
 * 
 * @since 1.0
 */
function unsetHotSpotOptions() {
	delete_option('enabled');
	delete_option('showOnClick');
	delete_option('hotValue');
	delete_option('spotOpacity');
	delete_option('spotRadius');
}
register_deactivation_hook(__FILE__,'unsetHotSpotOptions');


/**
 * hotspots options admin form handling
 * 
 * @since 1.0
 */
function hotSpotOptions() {
	// defaults
	$default_enabled = get_option('enabled');
	$default_showOnClick = get_option('showOnClick');
	$default_hotValue = get_option('hotValue');
	$default_spotOpacity = get_option('spotOpacity');
	$default_spotRadius = get_option('spotRadius');
	?>
	
	<div id="hotSpotOptions" class="wrap">
		<div class="clear"></div>
		<div class="icon32" id="icon-tools"><br /></div>
		<h1>HotSpots</h1>
		<p>HotSpots is a plugin which draws a heat map of mouse clicks on a
			webpage allowing you to improve usability by analysing which buttons or
			links are popular and easy to use.</p>
		<p>
			To show the heat map on the web page, add <i>?drawHotSpots=true</i> to
			the URL (i.e. www.mywebsite.com?drawHotSpots=true). Make sure the
			enable option is checked. The hot spots are shown as a heat map with a
			colour range from green (cold), to orange (warm) and red (hot). Each
			mouse click is represented as a coloured spot or circle. The colour of
			the spot is calculated based on how many other spots it is touching
			within it's radius (i.e if a spot is touching another spot, then it
			has a heat value of 1).
		</p>
	
	
		<form method="post" action="#" id="hotSpotsOptionsForm">
			<div id="hotSpotsMessages"></div>
		
			<ul>
				<li>
					<input type="checkbox" value="<?php echo $default_enabled ?>" name="enabled" id="enabled" <?php if ($default_enabled == "on") { ?> checked="checked" <?php } ?> />
					<label for="enabled">Enable</label><p class="description">Save mouse clicks and allow displaying heat map using URL query parameter ?drawHotSpots=true</p>
				</li>
			
				<li>
					<input type="checkbox" value="<?php echo $default_showOnClick ?>" name="showOnClick" id="showOnClick" <?php if ($default_showOnClick == "on") { ?> checked="checked" <?php } ?> />
					<label for="showOnClick">Show on click</label>
					<p class="description">Draw spots on every mouse click</p>
				</li>
			
				<h2>Heat Map</h2>
			
				<li><label for="hotValue" class="smallWidth">Set a hot value</label>
					<input type="text" value="<?php echo $default_hotValue ?>" name="hotValue" id="hotValue" />
				</li>
			
				<li>
					<label for="spotRadius" class="smallWidth">Set the spot radius</label>
					<input type="text" value="<?php echo $default_spotRadius ?>" name="spotRadius" id="spotRadius" />
				</li>
			
				<li>
					<label for="spotOpacity" class="smallWidth">Set the spot opacity</label>
					<input type="text" value="<?php echo $default_spotOpacity ?>" name="spotOpacity" id="spotOpacity" />
				</li>
			
				<li>
					<input  type="button" name="saveChangesBtn" id="saveChangesBtn" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
					<input type='button' name="refreshBtn" id="refreshBtn" value='<?php esc_attr_e('Refresh database'); ?>' class='button-secondary' />
				</li>
			</ul>
		</form>
	</div>
<?php 
}


/**
 * Add a hotspot options page to the admin menu
 * 
 * @since 1.0
 */
function addHotSpotMenu() {
	add_options_page(
			'HotSpots', // page title
			'HotSpots', // sub-menu title
			'manage_options', // access/capability
			__FILE__, // file
			'hotSpotOptions' // function
		);
}
add_action('admin_menu', 'addHotSpotMenu');


/**
 * Deletes all rows in the hotspots DB table
 * 
 * @since 1.0
 */
function refreshDatabase() {
	global $wpdb;

	$ajaxNonce = $_POST['nonce'];
	$rows = null;
	if (wp_verify_nonce($ajaxNonce, 'hotspot-nonce')) {
		$rows = $wpdb->get_results( "DELETE FROM hsp_hotspot WHERE 1" );
		echo json_encode(array('success' => true));
	} else {
		echo json_encode(array('success' => false));
	}

	die();
}
add_action('wp_ajax_refreshDatabase', 'refreshDatabase');
add_action('wp_ajax_nopriv_refreshDatabase', 'refreshDatabase');


/**
 * Save changes
 *
 * @since 1.2
 */
function saveChanges() {

	$errors = "";
	// enabled option
	if (isset($_POST['enabled'])) {
		update_option('enabled', 'on');
	} else { // make sure it's off anyway
		update_option('enabled', 'off');
	}
	
	// showOnClick option
	if (isset($_POST['showOnClick'])) {
		update_option('showOnClick', 'on');
	} else { // make sure it's off anyway
		update_option('showOnClick', 'off');
	}
	
	// hot value option
	if (isset($_POST['hotValue'])) {
		if (is_numeric($_POST['hotValue'])) {
			$hotValue = intval($_POST['hotValue']);
			if ($hotValue > 0) {
				update_option('hotValue', $hotValue);
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
				update_option('spotOpacity', $spotOpacity);
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
				update_option('spotRadius', $spotRadius);
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
add_action('wp_ajax_saveChanges', 'saveChanges');
add_action('wp_ajax_nopriv_saveChanges', 'saveChanges');


?>