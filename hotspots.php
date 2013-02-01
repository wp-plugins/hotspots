<?php 
/*
 Plugin Name: HotSpots
Plugin URI: http://wordpress.org/extend/plugins/hotspots/
Description: HotSpots is a plugin which draws a heat map of mouse clicks overlayed on your webpage allowing you to improve usability by analysing user behaviour.
Version: 2.0.1
Author: Daniel Powney
Auhtor URI: www.danielpowney.com
License: GPL2
*/
?>
<?php

require dirname(__FILE__).'/tables.php';

/**
 * HotSpots class
 *
 * @since 2.0
 * @author dpowney
 */
class HotSpots {

	// constants
	const
	VERSION 						= '0.1',
	ID					 			= 'hotspots',

	/* Front end */
	HOTSPOTS_DATA 					= 'hotSpotsData',
	DRAW_HOTSPOTS_QUERY_PARAM_NAME 	= 'drawHotSpots',

	/* database */
	TABLE_PREFIX 					= 'hsp_',
	HOTSPOTS_TBL_NAME 				= 'hotspot',
	ID_COLUMN						= "id",
	X_COLUMN						= 'x',
	Y_COLUMN						= 'y',
	URL_COLUMN						= 'url',
	WIDTH_COLUMN					= 'screenWidth',

	/* options */
	SAVE_MOUSE_CLICKS_OPTION 		= 'savedMouseClicks',
	DRAW_HOTSPOTS_ENABLED_OPTION 	= 'drawHotSpotsEnabled',
	DEBUG_OPTION 					= 'debug',
	HOT_VALUE_OPTION 				= 'hotValue',
	SPOT_OPACITY_OPTION 			= 'spotOpacity',
	SPOT_RADIUS_OPTION 				= 'spotRadius',
	IS_RESPONSIVE_OPTION			= 'isResponsive',
	HOME_PAGE_ONLY_OPTION			= 'homePageOnly',
	/* default values */
	DEFAULT_SAVE_MOUSE_CLICKS 		= true,
	DEFAULT_DRAW_HOTSPOTS_ENABLED 	= false,
	DEFAULT_DEBUG					= false,
	DEFAULT_HOT_VALUE				= '20',
	DEFAULT_SPOT_OPACITY			= '0.2',
	DEFAULT_SPOT_RADIUS				= '8',
	DEFAULT_IS_RESPONSIVE			= true,
	DEFAULT_HOME_PAGE_ONLY			= false;
	
	public static $ignoreQueryParams = array('drawHotSpots', 'width');

	/**
	 * Constructor
	 *
	 * @since 2.0
	 */
	function __construct() {

		// Create settings page, add JavaScript and CSS
		if(is_admin()) {
			add_action('admin_menu', array($this, 'createSettingsPage'));
			add_action('admin_enqueue_scripts', array($this, 'adminAssets'));
		} else {
			add_action('wp_enqueue_scripts', array($this, 'assets'));
		}

		// Register plugin
		register_activation_hook(__FILE__, function() {
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				
			// Create database tables if they does not exist
			$sql1 = "CREATE TABLE ".self::TABLE_PREFIX.self::HOTSPOTS_TBL_NAME." (
			".self::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
			".self::X_COLUMN." int(11) NOT NULL,
			".self::Y_COLUMN." int(11) NOT NULL,
			".self::URL_COLUMN." varchar(255),
			".self::WIDTH_COLUMN." int(11),
			PRIMARY KEY (id)
			) ENGINE=InnoDB AUTO_INCREMENT=1;";
			dbDelta($sql1);

			$sql2 = "CREATE TABLE ".self::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME." (
			".FilterTable::ID_COLUMN." int(11) NOT NULL AUTO_INCREMENT,
			".FilterTable::URL_COLUMN." varchar(255),
			PRIMARY KEY (id)
			) ENGINE=InnoDB AUTO_INCREMENT=1;";
			dbDelta($sql2);
				
			// Add options
			add_option(self::SAVE_MOUSE_CLICKS_OPTION, self::DEFAULT_SAVE_MOUSE_CLICKS, '', 'yes');
			add_option(self::DRAW_HOTSPOTS_ENABLED_OPTION, self::DEFAULT_DRAW_HOTSPOTS_ENABLED, '', 'yes');
			add_option(self::DEBUG_OPTION, self::DEFAULT_DEBUG, '', 'yes');
			add_option(self::HOT_VALUE_OPTION, self::DEFAULT_HOT_VALUE, '', 'yes');
			add_option(self::SPOT_OPACITY_OPTION, self::DEFAULT_SPOT_OPACITY, '', 'yes');
			add_option(self::SPOT_RADIUS_OPTION, self::DEFAULT_SPOT_RADIUS, '', 'yes');
			add_option(self::IS_RESPONSIVE_OPTION, self::DEFAULT_IS_RESPONSIVE, '', 'yes');
			add_option(self::HOME_PAGE_ONLY_OPTION, self::DEFAULT_HOME_PAGE_ONLY, '', 'yes');
		});

		// Setup AJAX calls
		$this::addAjaxActions();
	}

	/**
	 * Register AJAX call actions
	 *
	 * @since 1.2
	 */
	public function addAjaxActions() {
		if (is_admin()) {
			add_action('wp_ajax_add_mouse_click', array($this, 'addMouseClick'));
			add_action('wp_ajax_get_mouse_clicks',  array($this, 'getMouseClicks'));
			add_action('wp_ajax_refreshDatabase',  array($this, 'refreshDatabase'));
			add_action('wp_ajax_saveChanges', array($this, 'saveChanges'));
		} else {
			add_action('wp_ajax_nopriv_add_mouse_click',  array($this, 'addMouseClick'));
			add_action('wp_ajax_nopriv_get_mouse_clicks',  array($this, 'getMouseClicks'));
		}

	}

	/**
	 * Records mouse click information and saves to database
	 *
	 * @since 2.0
	 */
	function addMouseClick() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];

		if (wp_verify_nonce($ajaxNonce, self::ID.'-nonce')) {
			$x = isset($_POST['x']) ? $_POST['x'] : '';
			$y = isset($_POST['y']) ? $_POST['y'] : '';
			$url = isset($_POST['url']) ? $this::removeQueryStringParams(addslashes($_POST['url']), self::$ignoreQueryParams) : '';
			$width = isset($_POST['width']) ? intval($_POST['width']) : '';
			$rowsAffected = $wpdb->insert( self::TABLE_PREFIX . self::HOTSPOTS_TBL_NAME, array( self::X_COLUMN => $x, self::Y_COLUMN => $y, self::URL_COLUMN => $url, self::WIDTH_COLUMN => $width ) );
			$id = $wpdb->insert_id;
				
			$debug = get_option(self::DEBUG_OPTION);
			if ($debug == true) {
				// get all mouse clicks and calculate heat value for added mouse click
				$query = "SELECT id, ".self::X_COLUMN.", ".self::Y_COLUMN.", ".self::URL_COLUMN.", ".self::WIDTH_COLUMN." FROM ".self::TABLE_PREFIX.self::HOTSPOTS_TBL_NAME." WHERE ".self::URL_COLUMN." = '" . $url . "'";
				$isResponsive = get_option(self::IS_RESPONSIVE_OPTION);
				if ($isResponsive == true && isset($_POST['width'])){
					$range = 6; // allow a range of 6 pixels either side to be the same
					$width = intval($_POST['width']);
					$diffLeft = $width - $range;
					$diffRight = $width + $range;
					$query .= ' AND '.self::WIDTH_COLUMN.' >= ' . $diffLeft . ' AND '.self::WIDTH_COLUMN.' <= '. $diffRight;
				}

				$rows = $wpdb->get_results($query);
				$heatValue = $this::calculateHeatValue($x, $y, $id, $rows);
				$response = array('id' => $id, 'heatValue' => $heatValue);
			} else {
				$response = array('id' => $id);
			}
				
			echo json_encode($response);
		}
		die();
	}

	/**
	 * Gets all mouse clicks
	 *
	 * @since 1.0
	 */
	function getMouseClicks() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];
		$rows = null;
		if (wp_verify_nonce($ajaxNonce, self::ID .'-nonce')) {
			$url = isset($_POST['url']) ? $this::removeQueryStringParams(addslashes($_POST['url']), self::$ignoreQueryParams) : '';
			$query = "SELECT id, ".self::X_COLUMN.", ".self::Y_COLUMN.", ".self::URL_COLUMN.", ".self::WIDTH_COLUMN." FROM ".self::TABLE_PREFIX.self::HOTSPOTS_TBL_NAME." WHERE ".self::URL_COLUMN." = '" . $url . "'";

			$isResponsive = get_option(self::IS_RESPONSIVE_OPTION);
			if ($isResponsive == true && isset($_POST['width'])){
				$range = 6; // allow a range of 6 pixels either side to be the same
				$width = intval($_POST['width']);
				$diffLeft = $width - $range;
				$diffRight = $width + $range;
				$query .= ' AND '.self::WIDTH_COLUMN.' >= ' . $diffLeft . ' AND '.self::WIDTH_COLUMN.' <= '. $diffRight;
			}

			$rows = $wpdb->get_results($query);
				
			// create mouse clicks array with heat values
			$mouseClicks = array();
			$index = 0;
			foreach ($rows as $row) {
				$id = $row->id;
				$x = $row->x;
				$y = $row->y;
				$url = $row->url;
				$width = $row->screenWidth;
				$heatValue = $this::calculateHeatValue($x, $y, $id, $rows);
				$mouseClicks[$index++] = array('id' => $id, 'x' => $x, 'y' => $y, 'width' => $width, 'url' => $url, 'heatValue' => $heatValue);
			}
		}
		echo json_encode($mouseClicks);
		die();
	}


	/**
	 * Calculates the heat value given closeness of existing mouse clicks
	 *
	 * @param x
	 * @param y
	 * @param id
	 * @param rows
	 */
	public function calculateHeatValue($x, $y, $id, $rows) {
		$heatValue = 0;
		$spotRadius = get_option(self::SPOT_RADIUS_OPTION);

		foreach ($rows as $row) {
			$currentX = $row->x;
			$currentY = $row->y;
			$currentId = $row->id;

			// skip if comparing the same mouse click
			if ($id == $currentId) {
				continue;
			}
				
			// Check if the spot is touching other spots
			$diffX = $x - $currentX;
			$diffY = $y - $currentY;
			$hotX = ($diffX > - $spotRadius && $diffX < $spotRadius);
			$hotY = ($diffY > - $spotRadius && $diffY < $spotRadius);
			if ($hotX && $hotY) {
				$heatValue++;
			}
		}
		return $heatValue;
	}


	/**
	 * Deletes all rows in the hotspots DB table
	 *
	 * @since 1.0
	 */
	function refreshDatabase() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];
		$rows = null;
		if (wp_verify_nonce($ajaxNonce, self::ID.'-nonce')) {
			$rows = $wpdb->get_results( "DELETE FROM ".self::TABLE_PREFIX.self::HOTSPOTS_TBL_NAME." WHERE 1" );
			echo json_encode(array('success' => true));
		} else {
			echo json_encode(array('success' => false));
		}

		die();
	}

	/**
	 * Save settings changes
	 *
	 * @since 1.2
	 */
	function saveChanges() {

		$errors = "";
		// draw hotspots enabled option
		if (isset($_POST['drawHotSpotsEnabled'])) {
			if ($_POST['drawHotSpotsEnabled'] == "true") {
				update_option(self::DRAW_HOTSPOTS_ENABLED_OPTION, true);
			} else {
				update_option(self::DRAW_HOTSPOTS_ENABLED_OPTION, false);
			}
		}

		// Save mouse clicks option
		if (isset($_POST['saveMouseClicks'])) {
			if ($_POST['saveMouseClicks'] == "true") {
				update_option(self::SAVE_MOUSE_CLICKS_OPTION, true);
			} else {
				update_option(self::SAVE_MOUSE_CLICKS_OPTION, false);
			}
		}

		// debug option
		if (isset($_POST['debug'])) {
			if ($_POST['debug'] == "true") {
				update_option(self::DEBUG_OPTION, true);
			} else {
				update_option(self::DEBUG_OPTION, false);
			}
		}

		// isResponsive option
		if (isset($_POST['isResponsive'])) {
			if ($_POST['isResponsive'] == "true") {
				update_option(self::IS_RESPONSIVE_OPTION, true);
			} else {
				update_option(self::IS_RESPONSIVE_OPTION, false);
			}
		}

		// homePageOnly option
		if (isset($_POST['homePageOnly'])) {
			if ($_POST['homePageOnly'] == "true") {
				update_option(self::HOME_PAGE_ONLY_OPTION, true);
			} else {
				update_option(self::HOME_PAGE_ONLY_OPTION, false);
			}
		}

		// hot value option
		if (isset($_POST['hotValue'])) {
			if (is_numeric($_POST['hotValue'])) {
				$hotValue = intval($_POST['hotValue']);
				if ($hotValue > 0) {
					update_option(self::HOT_VALUE_OPTION, $hotValue);
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
					update_option(self::SPOT_OPACITY_OPTION, $spotOpacity);
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
					update_option(self::SPOT_RADIUS_OPTION, $spotRadius);
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

	/**
	 * Javascript and CSS used by the plugin
	 *
	 * @since 2.0
	 */
	public function assets(){
		wp_enqueue_script('jquery');
		wp_enqueue_script(self::ID, plugins_url('hotspots.js', __FILE__), array('jquery'), self::VERSION, true);

		$drawHotSpotsEnabled = get_option(self::DRAW_HOTSPOTS_ENABLED_OPTION);
		$saveMouseClicks = get_option(self::SAVE_MOUSE_CLICKS_OPTION);

		// check the home page only option
		$homePageOnly = get_option(self::HOME_PAGE_ONLY_OPTION);
		if ($homePageOnly == true && is_home() == false) {
			// false = 0
			$drawHotSpotsEnabled = 0;
			$saveMouseClicks = 0; 
		}

		$config_array = array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'ajaxNonce' => wp_create_nonce(self::ID.'-nonce'),
				'drawHotSpotsEnabled' => $drawHotSpotsEnabled,
				'saveMouseClicks' => $saveMouseClicks,
				'debug' => get_option(self::DEBUG_OPTION),
				'isResponsive' => get_option(self::IS_RESPONSIVE_OPTION),
				'hotValue' => get_option(self::HOT_VALUE_OPTION),
				'spotOpacity' => get_option(self::SPOT_OPACITY_OPTION),
				'spotRadius' => get_option(self::SPOT_RADIUS_OPTION)
		);
		wp_localize_script(self::ID, self::HOTSPOTS_DATA, $config_array);
	}


	/**
	 * Admin assets
	 *
	 * @since 1.2.8
	 */
	public function adminAssets() {
		$config_array = array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'ajaxNonce' => wp_create_nonce(self::ID.'-nonce')
		);
		wp_enqueue_script('jquery');

		if (is_admin()) {
			wp_enqueue_style('hotspots-admin-style', plugins_url('hotspots-admin.css', __FILE__));
			wp_enqueue_script(self::ID.'-admin', plugins_url('hotspots-admin.js', __FILE__), array('jquery'));
			wp_localize_script(self::ID.'-admin', self::HOTSPOTS_DATA, $config_array);

		}

	}

	/**
	 * Creates the Settings page
	 *
	 * @since 2.0
	 */
	public function createSettingsPage() {
		add_options_page(__('HotSpots', self::ID), __('HotSpots', self::ID), 'manage_options', self::ID, array($this, 'settingsPage'));
	}


	/**
	 * Shows the admin options page
	 *
	 * @since 2.0
	 */
	public function settingsPage() {

		// current values
		$current_drawHotSpotsEnabled = get_option(self::DRAW_HOTSPOTS_ENABLED_OPTION);
		$current_saveMouseClicks = get_option(self::SAVE_MOUSE_CLICKS_OPTION);
		$current_debug = get_option(self::DEBUG_OPTION);
		$current_hotValue = get_option(self::HOT_VALUE_OPTION);
		$current_spotOpacity = get_option(self::SPOT_OPACITY_OPTION);
		$current_spotRadius = get_option(self::SPOT_RADIUS_OPTION);
		$current_isResponsive = get_option(self::IS_RESPONSIVE_OPTION);
		$current_homePageOnly = get_option(self::HOME_PAGE_ONLY_OPTION);
		
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
	
			<form method="post" action="#" id="hotSpotsOptionsForm">
				<h2>Options</h2>
				
				<!-- messages are shown in this div -->
				<div id="hotSpotsMessages"></div>
			
				<ul>
					<li>
						<input type="checkbox" value="<?php echo $current_saveMouseClicks ?>" name="saveMouseClicks" id="saveMouseClicks" <?php if ($current_saveMouseClicks) { ?> checked="checked" <?php } ?> />
						<label for="saveMouseClicks">Save mouse clicks</label><p class="description">Turn on to start recording mouse click information on your website.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_drawHotSpotsEnabled ?>" name="drawHotSpotsEnabled" id="drawHotSpotsEnabled" <?php if ($current_drawHotSpotsEnabled) { ?> checked="checked" <?php } ?> />
						<label for="drawHotSpotsEnabled">Enable drawing hotspots</label>
						<p class="description">Allows adding query parameter
							drawHotSpots=true to the URL ((i.e.
							www.mywebsite.com?drawHotSpots=true or
							www.mywebsite.com?cat=1&drawHotSpots=true) which will then draw a
							heat map of mouse clicks overlayed on your website.</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_debug ?>" name="debug" id="debug" <?php if ($current_debug) { ?> checked="checked" <?php } ?> />
						<label for="debug">Debug</label>
						<p class="description">Turn on to debug and draw hot spots on every
							mouse click. This option is useful for testing that that the mouse
							clicks are being recorded and that the drawing of the hot spots is
							working (i.e. every time you click, a spot will be drawn).</p>
					</li>
					<li>
						<input type="checkbox" value="<?php echo $current_isResponsive ?>" name="isResponsive" id="isResponsive" <?php if ($current_isResponsive) { ?> checked="checked" <?php } ?> />
						<label for="isResponsive">Is your website responsive?</label>
						<p class="description">Turn on if you have a responsive website (i.e. stretches and shrinks to fit multiple devices and screen sizes). This includes text wrapping, middle or right alignment and floating elements. It is recommended to keep this option on.</p>
					</li>
					
					<li>
						<input type="checkbox" value="<?php echo $current_homePageOnly ?>" name="homePageOnly" id="homePageOnly" <?php if ($current_homePageOnly) { ?> checked="checked" <?php } ?> />
						<label for="homePageOnly">Only enable for home page</label>
						<p class="description">Turn on if you are only interested in using this plugin for the home page. All other pages will be ignored.</p>
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
					<li>
						<label for="hotValue" class="smallWidth">Set a hot value</label>
						<input type="text" value="<?php echo $current_hotValue ?>" name="hotValue" id="hotValue" />&nbsp;(must be greater than 0) 
						<p class="description">Set the heat value for the hottest spots which will show as red colour.</p>
					</li>
					<li>
						<label for="spotRadius" class="smallWidth">Set the spot radius</label>
						<input type="text" value="<?php echo $current_spotRadius ?>" name="spotRadius" id="spotRadius" />&nbsp;pixels&nbsp(between 1 and 25)
						<p class="description">Set the radius of each spot. Note: This will
						effect the heat value calculation as spots with a greater radius
						are more likely to touch other spots.</p>
					</li>
					<li>
						<label for="spotOpacity" class="smallWidth">Set the spot opacity</label>
						<input type="text" value="<?php echo $current_spotOpacity ?>" name="spotOpacity" id="spotOpacity" />&nbsp(between 0 and 1)
						<p class="description">Set the opacity value of the spots. This is
						the degree of how much of the background you can see where there
						are spots.</p>
					</li>
					<li>
						<input  type="button" name="saveChangesBtn" id="saveChangesBtn" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
						<input type='button' name="refreshBtn" id="refreshBtn" value='<?php esc_attr_e('Clear Database'); ?>' class='button-secondary' />
					</li>
				</ul>
			</form>

			<h2>Mouse Clicks</h2>
			<?php 
			$stats = new StatsTable();
			$stats->prepare_items();
			$stats->display();
			?>
		</div>
		<?php 
	}

	/**
	 * Removes a query string parameter from URL
	 * @param $url
	 * @param $param
	 * @return string
	 */
	public function removeQueryStringParams($url, $params) {
		foreach ($params as $param) {
			$url = preg_replace('/(.*)(\?|&)' . $param . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
			$url = substr($url, 0, -1);
		}
		return $url;
	}


	/**
	 * Creates a URL filter
	 *
	 * @since 2.0
	 */
	public function addFilter() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];
		if (wp_verify_nonce($ajaxNonce, self::ID.'-nonce')) {
			$url = isset($_POST['url']) ? $_POST['url'] : '';
			$results = $wpdb->insert( self::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME, array( 'url' => $url ) );
		}
		die();
	}

	/**
	 * Deletes a URL filter
	 *
	 * @since 21.0
	 */
	public function deleteFilter() {
		global $wpdb;

		$ajaxNonce = $_POST['nonce'];
		if (wp_verify_nonce($ajaxNonce, self::ID.'-nonce')) {
			$id = isset($_POST['id']) ? intval($_POST['id']) : '';

			$results = $wpdb->get_results( "DELETE FROM ".self::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME." WHERE id = ".$id );
		}
		die();
	}
}

$hotSpots = new HotSpots();

?>