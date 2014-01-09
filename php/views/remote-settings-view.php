<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'common.php';

class HUT_Remote_Settings_View {
	
	/**
	* Database settings description
	*/
	public static function section_remote_desc() {
		?>
		<p>Currently the plugin should not be used where performance is critical as an additional server request is made for 
		each mouse click, touchscreen tap, AJAX action, page view and custom event. A remote setting can be setup to direct 
		all user activity events to be saved on a remote host and database to reduce load on your server. Two separate plugins 
		are required to be installed, one on your existing WordPress website which extends the plugin to enable remote 
		capabilities (the client) and another on a separate WordPress website to manage the data (the server). For more 
		informatiom, <a href="http://danielpowney.com/downloads/hotspots-analytics-remote-bundle">click here</a>.</p>
		<?php
	
	}
	
	public static function field_remote_url() {
		$remote_settings = (array) get_option( HUT_Common::REMOTE_SETTINGS_KEY );
		$remote_url = $remote_settings[HUT_Common::REMOTE_URL_OPTION];
		?>
		<input type="text" name="<?php echo HUT_Common::REMOTE_SETTINGS_KEY; ?>[<?php echo HUT_Common::REMOTE_URL_OPTION; ?>]" class="regular-text" value="<?php echo $remote_url; ?>" />
		<p class="description">Configire plugin to use a remote URL.</p>
		<?php
	}
	
	public static function field_remote_api_key() {
		$remote_settings = (array) get_option( HUT_Common::REMOTE_SETTINGS_KEY );
		$remote_api_key = $remote_settings[HUT_Common::REMOTE_API_KEY_OPTION];
		?>
			<input type="text" name="<?php echo HUT_Common::REMOTE_SETTINGS_KEY; ?>[<?php echo HUT_Common::REMOTE_API_KEY_OPTION; ?>]" class="regular-text" value="<?php echo $remote_api_key; ?>" />
			<p class="description">You need an API key to connect remotely for security. This will be encoded set in the header of each request.</p>
			<?php
		}
			
	function sanitize_remote_settings($input) {
		
		$remote_url = $input[HUT_Common::REMOTE_URL_OPTION];
		$remote_url = HUT_Common::normalize_url($remote_url);
		$input[HUT_Common::REMOTE_URL_OPTION] = $remote_url;
		
		$api_key = $input[HUT_Common::REMOTE_API_KEY_OPTION];
		
		if ( isset( $_POST['test-connection-flag'] ) && $_POST['test-connection-flag'] === "true" ) {
			$query_string = '?action=test&apiKey=' . base64_encode( $api_key ) . '&url=' . HUT_Common::get_current_url();
			
			$args = array(
					'timeout' => 60
				);
			
			$http_response = wp_remote_get($remote_url . $query_string , $args);
			
			$http_response_body = wp_remote_retrieve_body($http_response);
			
			if ($http_response_body == 'true') {
				add_settings_error( HUT_Common::REMOTE_SETTINGS_KEY, 'remote-test-connection-success', 'Remote connection successful.', 'updated');
			} else {
				add_settings_error( HUT_Common::REMOTE_SETTINGS_KEY, 'remote-test-connection-error', $http_response_body.'Remote connection failed. Please check your configuration settings are correct.', 'error');
			}
		}
		
		return $input;
	}
		
}
?>