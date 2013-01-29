<?php 
/*
Plugin Name: HotSpots
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: HotSpots is a plugin which draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing user behaviour.
Version: 1.3
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
	
	$enabled = get_option('enabled');
	$homePageOnly = get_option('homePageOnly');
	if ($homePageOnly == 'on' && is_home() == false && $enabled == 'on') {
		$enabled = 'off';
	}
	$config_array = array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'ajaxNonce' => wp_create_nonce('hotspot-nonce'),
			'enabled' => $enabled,
			'showOnClick' => get_option('showOnClick'),
			'isResponsive' => get_option('isResponsive'),
			'hotValue' => get_option('hotValue'),
			'spotOpacity' => get_option('spotOpacity'),
			'spotRadius' => get_option('spotRadius')
	);
	wp_localize_script('hotspots', 'hotSpotData', $config_array);
}
add_action( 'wp_enqueue_scripts', 'assets' );


/**
 * admin backend assets
 */
function adminAssets() {
	$config_array = array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'ajaxNonce' => wp_create_nonce('hotspot-nonce')
	);
	wp_enqueue_script('jquery');
	
	if (is_admin()) {
		wp_enqueue_style('hotspots-admin-style', plugins_url('hotspots-admin.css', __FILE__));
		wp_enqueue_script('hotspots-admin', plugins_url('hotspot-admin.js', __FILE__), array('jquery'));
		wp_localize_script('hotspots-admin', 'hotSpotData', $config_array);
	
	}
	
}
add_action( 'admin_enqueue_scripts', 'adminAssets' );


/**
 * Create the hotspots DB table
 * 
 * @since 1.0
 */
function createHotSpotDBTable() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	// Do not delete table, keep old records
	$sql1 = "CREATE TABLE hsp_hotspot (
	id int(11) NOT NULL AUTO_INCREMENT,
	x int(11) NOT NULL,
	y int(11) NOT NULL,
	url varchar(255),
	screenWidth int(11),
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
		$url = isset($_POST['url']) ? removeQueryStringParam(addslashes($_POST['url']), "drawHotSpots") : '';
		$screenWidth = isset($_POST['screenWidth']) ? intval($_POST['screenWidth']) : '';
		$rows_affected = $wpdb->insert( "hsp_hotspot", array( 'x' => $x, 'y' => $y, 'url' => $url, 'screenWidth' => $screenWidth ) );
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
		$url = isset($_POST['url']) ? removeQueryStringParam(addslashes($_POST['url']), "drawHotSpots") : '';
		$query = "SELECT id, x, y, url, screenWidth FROM hsp_hotspot WHERE url = '" . $url . "'";
		
		$isResponsive = get_option('isResponsive') === 'on' ? true : false;
		if ($isResponsive === true && isset($_POST['screenWidth'])){
			$range = 6; // allow a range of 6 pixels either side to be the same
			$screenWidth = intval($_POST['screenWidth']);
			$diffLeft = $screenWidth - $range;
			$diffRight = $screenWidth + $range;
			$query .= ' AND screenWidth >= ' . $diffLeft . ' AND screenWidth <= '. $diffRight;			
		}
		
		$rows = $wpdb->get_results($query);
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
	add_option('isResponsive', 'on', '', 'yes');
	add_option('homePageOnly', 'off', '', 'yes');
	
}
register_activation_hook(__FILE__,'setHotSpotOptions');

/**
 * Unset hotspot options
 * 
 * @since 1.0
 */
function unsetHotSpotOptions() {
	// Commented out delete options to keep options in case of version updates
	//delete_option('enabled');
	//delete_option('showOnClick');
	//delete_option('hotValue');
	//delete_option('spotOpacity');
	//delete_option('spotRadius');
	//delete_option('isResponsive');
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
	$default_isResponsive = get_option('isResponsive');
	
	$default_homePageOnly = get_option('homePageOnly');
	
	?>
	
	<div id="hotSpotOptions" class="wrap">
		<div class="icon32" id="icon-tools"><br /></div>
		<h1>HotSpots</h1>
		<p>HotSpots is a plugin which draws a heat map of mouse clicks
			overlayed on your webpage allowing you to improve usability by
			analysing user behaviour. This can give insight into which buttons or
			links are popular and easy to use including the effecfiveness of
			advertising placement. Each page on your website has it's own heat
			map. Different heat maps are drawn when you resize the window to cater
			for responsive design.</p>
		<p>Make sure the enabled option is checked to start recording mouse
			clicks. To view the mouse clicks, add query parameter
			drawHotSpots=true to the URL (i.e. www.mywebsite.com?drawHotSpots=true
			or www.mywebsite.com?cat=1&drawHotSpots=true).
		</p>

	<form method="post" action="#" id="hotSpotsOptionsForm">
			<h2>Options</h2>
			
			<div id="hotSpotsMessages"></div>
		
			<ul>
				<li>
					<input type="checkbox" value="<?php echo $default_enabled ?>" name="enabled" id="enabled" <?php if ($default_enabled == "on") { ?> checked="checked" <?php } ?> />
					<label for="enabled">Enable</label><p class="description">Turn on to save mouse clicks and to be able to display the heat map of mouse clicks.</p>
				</li>
				<li>
					<input type="checkbox" value="<?php echo $default_showOnClick ?>" name="showOnClick" id="showOnClick" <?php if ($default_showOnClick == "on") { ?> checked="checked" <?php } ?> />
					<label for="showOnClick">Show on click</label>
					<p class="description">Turn on to draw each hot spot on every mouse
						click. Enabled option needs to be turned on. This option is useful
						for testing that that the mouse clicks are being recorded and that
						the drawing of the hot spots is working (i.e. every time you click,
						a spot will be drawn). You also need to set the URL query parameter
						drawHotSpots=true.</p>
				</li>
				<li>
					<input type="checkbox" value="<?php echo $default_isResponsive ?>" name="isResponsive" id="isResponsive" <?php if ($default_isResponsive == "on") { ?> checked="checked" <?php } ?> />
					<label for="showOnClick">Is your website responsive?</label>
					<p class="description">Turn on if you have a responsive website (i.e. stretches and shrinks to fit multiple devices and screen sizes). This includes text wrapping and fixed width middle or right alignment. It is recommended to keep this option on.</p>
				</li>
				
				<li>
					<input type="checkbox" value="<?php echo $default_homePageOnly ?>" name="homePageOnly" id="homePageOnly" <?php if ($default_homePageOnly == "on") { ?> checked="checked" <?php } ?> />
					<label for="homePageOnly">Only enable for home page</label>
					<p class="description">Turn on if you are only interested in using this plugin for the home page.</p>
				</li>				
				
				<h3>Heat Map</h3>
				<p>The hot spots are shown as a heat map with a colour range from
					green (cold), to orange (warm) and red (hot). Each mouse click is
					represented as a coloured spot or circle. The colour of the spot is
					calculated based on how many other spots it is touching within it's
					radius (i.e if a spot is touching another spot, then it has a heat
					value of 1. If it is touching two spots, then it has a heat value of
					2 and so on).
				</p>
			<li><label for="hotValue" class="smallWidth">Set a hot value</label>
					<input type="text" value="<?php echo $default_hotValue ?>" name="hotValue" id="hotValue" />
					<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
				</li>
				<li>
					<label for="spotRadius" class="smallWidth">Set the spot radius</label>
					<input type="text" value="<?php echo $default_spotRadius ?>" name="spotRadius" id="spotRadius" />
					<p class="description">Set the radius of each spot. Note: This will
					effect the heat value calculation as spots with a greater radius
					are more likely to touch other spots.</p>
			</li>
				<li>
					<label for="spotOpacity" class="smallWidth">Set the spot opacity</label>
					<input type="text" value="<?php echo $default_spotOpacity ?>" name="spotOpacity" id="spotOpacity" />
					<p class="description">Set the opacity value of the spots. This is
					the degree of how much of the background you can see where there
					are spots.</p>
			</li>
				<li>
					<input  type="button" name="saveChangesBtn" id="saveChangesBtn" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</li>
				
				<h3>Database</h3>	
				<li>
					<input type='button' name="refreshBtn" id="refreshBtn" value='<?php esc_attr_e('Refresh database'); ?>' class='button-secondary' />
					<p class="description">Delete all mouse click records in the database</p>
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
	
	// isResponsive option
	if (isset($_POST['isResponsive'])) {
		update_option('isResponsive', 'on');
	} else { // make sure it's off anyway
		update_option('isResponsive', 'off');
	}
	
	// homePageOnly option
	if (isset($_POST['homePageOnly'])) {
		update_option('homePageOnly', 'on');
	} else { // make sure it's off anyway
		update_option('homePageOnly', 'off');
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


/**
 * Removes a query string parameter from URL
 * @param $url
 * @param $param
 * @return string
 */
function removeQueryStringParam($url, $param) {
 	$url = preg_replace('/(.*)(\?|&)' . $param . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
    $url = substr($url, 0, -1);
	return $url;
}

?>