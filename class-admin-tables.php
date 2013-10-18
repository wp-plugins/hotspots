<?php 

if (!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}



/**
 * HUT_Element_Selector_Report_Table class
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_Element_Selector_Report_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'Element Selector Report',
				'plural' => 'Element Selector Report',
				'ajax'	=> false
		) );
	}
	
	function display_tablenav( $which ) {
		if ( $which == "bottom" ){
			return;
		}
		parent::display_tablenav($which);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			?>
			<div class="alignleft">
				<?php 
				global $wpdb;
				$query = 'SELECT DISTINCT ' . HUT_Common::URL_COLUMN . ' FROM '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME;
				$rows = $wpdb->get_results($query);
				echo '<label for="' . HUT_Common::URL_SEARCH_INPUT . '">URL</label>&nbsp;';
				echo '<select name="' . HUT_Common::URL_SEARCH_INPUT . '" id="' . HUT_Common::URL_SEARCH_INPUT . '">';
				echo '<option value=""></option>';
				foreach ($rows as $row) {
					$url = $row->url;
					$selected = '';
					if ($url == $_POST[HUT_Common::URL_SEARCH_INPUT])
						$selected = ' selected="selected"';
					echo '<option value="' . $row->url . '"' . $selected . '>' . $row->url . '</option>';
				}
				echo '</select>';
				
				$start_date = isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::START_DATE_SEARCH_INPUT] : '';
				$end_date = isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::END_DATE_SEARCH_INPUT] : '';
				?>
				<label>Start date</label>&nbsp;
				<input type="text" name="<?php echo HUT_Common::START_DATE_SEARCH_INPUT; ?>" id="<?php echo HUT_Common::START_DATE_SEARCH_INPUT; ?>" class="date-field" value="<?php echo $start_date; ?>" />
				<label>End date</label>&nbsp;
				<input type="text" name="<?php echo HUT_Common::END_DATE_SEARCH_INPUT; ?>" id="<?php echo HUT_Common::END_DATE_SEARCH_INPUT; ?>" class="date-field" value="<?php echo $end_date; ?>" />
				<?php 
				submit_button( __( 'Filter' ), 'submit', false, false, array( 'id' => 'filter-submit' ) );
				?>
			</div><?php 
		}
		
		if ( $which == "bottom" ){
			echo '';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		global $wpdb;
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME;
		
		// search inputs
		$url = null;
		$start_date = null;
		$end_date = null;
		if (isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) > 0) {
			if (HUT_Common::check_date_format($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) {
				$start_date = date("Y-m-d H:i:s", strtotime($_POST[HUT_Common::START_DATE_SEARCH_INPUT])); // default yyyy-mm-dd format
			}
		}
		if (isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) > 0) {
			if (HUT_Common::check_date_format($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) {
				list($yyyy, $mm, $dd) = explode('-', $_POST[HUT_Common::END_DATE_SEARCH_INPUT]);// default yyyy-mm-dd format
				$end_date = date("Y-m-d H:i:s", mktime(23, 59, 59, $mm, $dd, $yyyy) );
			}
		}
		if (isset($_POST[HUT_Common::URL_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::URL_SEARCH_INPUT])) > 0) {
			$url = $_POST[HUT_Common::URL_SEARCH_INPUT];
		}
		if ($start_date != null || $end_date != null || $url != null) {
			$query .= ' WHERE ';
		
			if ($start_date && $end_date)
				$query .= HUT_Common::CREATED_DATE_COLUMN . ' >= "' . $start_date . '" AND ' . HUT_Common::CREATED_DATE_COLUMN . ' <= "' . $end_date . '" ';
			else if ($start_date)
				$query .=  HUT_Common::CREATED_DATE_COLUMN . ' >= "' . $start_date . '" ';
			else if ($end_date)
				$query .=  HUT_Common::CREATED_DATE_COLUMN . ' <= "' . $end_date . '" ';
			
			if ($url && ($start_date || $end_date))
				$query .= 'AND ';
			if ($url)
				$query .= HUT_Common::URL_COLUMN . ' = "' . $url . '"';
		}
		
		$total = $wpdb->query($query);
		
		return $columns= array(
				'element_selector' => __('Element Selector Name'),
				'count' => __('Impressions/Submits'),
				'percentage' => 'Percentage (Total Clicks/Taps: ' . $total . ')'
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array( );
		
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		
		
		// get table data
		$query = 'SELECT count(*) as count, element_selector FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME;
		$query = HUT_Common::add_filters_to_query($query);
		$query .= ' GROUP BY element_selector';
		
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'count':
			case 'element_selector':
				return $item[$column_name];
				break;
			case 'percentage': {
				global $wpdb;
				$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME;
				
				// search inputs
				$start_date = null;
				$end_date = null;
				$url = null;
				if (isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) > 0) {
					if (HUT_Common::check_date_format($_POST[HUT_Common::START_DATE_SEARCH_INPUT])) {
						$start_date = date("Y-m-d H:i:s", strtotime($_POST[HUT_Common::START_DATE_SEARCH_INPUT])); // default yyyy-mm-dd format
					}
				}
				if (isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) > 0) {
					if (HUT_Common::check_date_format($_POST[HUT_Common::END_DATE_SEARCH_INPUT])) {
						list($yyyy, $mm, $dd) = explode('-', $_POST[HUT_Common::END_DATE_SEARCH_INPUT]);// default yyyy-mm-dd format
						$end_date = date("Y-m-d H:i:s", mktime(23, 59, 59, $mm, $dd, $yyyy) );
					}
				}
				if (isset($_POST[HUT_Common::URL_SEARCH_INPUT]) && strlen(trim($_POST[HUT_Common::URL_SEARCH_INPUT])) > 0) {
					$url = $_POST[HUT_Common::URL_SEARCH_INPUT];
				}
				if ($start_date != null || $end_date != null || $url != null) {
					$query .= ' WHERE ';
				
					if ($start_date && $end_date)
						$query .= HUT_Common::CREATED_DATE_COLUMN . ' >= "' . $start_date . '" AND ' . HUT_Common::CREATED_DATE_COLUMN . ' <= "' . $end_date . '" ';
					else if ($start_date)
						$query .=  HUT_Common::CREATED_DATE_COLUMN . ' >= "' . $start_date . '" ';
					else if ($end_date)
						$query .=  HUT_Common::CREATED_DATE_COLUMN . ' <= "' . $end_date . '" ';
					if ($url && ($start_date || $end_date))
						$query .= 'AND ';
					if ($url)
						$query .= HUT_Common::URL_COLUMN . ' = "' . $url . '" ';
				}
					
				$total = $wpdb->query($query);
				
				if ($total != 0)
					echo round($item['count'] / doubleval($total) * 100, 2) . '%';
				else
					echo '100%';
			}
			break;
			default:
				return print_r( $item, true ) ;
		}
	}

}






/**
 * HUT_User_Activity_Sequence_Table class 
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_User_Activity_Sequence_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'User Activity Sequence Table',
				'plural' => 'User Activity Sequence Table',
				'ajax'	=> false
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){

		}
		if ( $which == "bottom" ){

		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				'order' => __('Order'),
				'event' => __('Event'),
				'description' => __('Description'),
				'date_time' => __('Date & Time'),
				'time_elapsed' => __('Time Elapsed'),
				'additional_info' => __('Addintion Info'),
				'actions' => __('Actions')
		);
	}


	function display_tablenav( $which ) {
		// do nothing
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array( );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		// TODO pagination

		$this->items = $this->construct_items_array();
	}
	
	/**
	 * Helper class to create the data items array
	 */
	function construct_items_array() {
		global $wpdb;
		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : null;
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : null;
		
		$user_activities = array();
		
		$query = 'SELECT * FROM ' . $wpdb->prefix.HUT_Common::URL_PING_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$rows = $wpdb->get_results($query);
		foreach ($rows as $row) {
			array_push($user_activities, array(
					'type' => 'url',
					'url' => $row->url,
					'record_date' => $row->record_date
			));
		}
		
		$query = 'SELECT * FROM ' . $wpdb->prefix.HUT_Common::AJAX_PING_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$rows = $wpdb->get_results($query);
		foreach ($rows as $row) {
			array_push($user_activities, array(
					'type' => 'ajax',
					'ajax_action' => $row->ajax_action,
					'status_text' => $row->status_text,
					'record_date' => $row->record_date
			));
		}
		
		$query = 'SELECT * FROM ' . $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$rows = $wpdb->get_results($query);
		foreach ($rows as $row) {
			array_push($user_activities, array(
					'type' => 'click_tap',
					'x' => $row->x,
					'y' => $row->y,
					'width' => $row->width,
					'is_tap' => $row->is_tap,
					'zoom_level' => $row->zoom_level,
					'device_pixel_ratio' => $row->device_pixel_ratio,
					'record_date' => $row->created_date,
					'url' => $row->url,
					'id' => $row->id
			));
		}
		
		$query = 'SELECT * FROM ' . $wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$rows = $wpdb->get_results($query);
		foreach ($rows as $row) {
				
			$query2 = 'SELECT name, is_form_submit FROM ' .  $wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME . ' WHERE ' . HUT_Common::ELEMENT_SELECTOR_COLUMN . ' = "' . $row->element_selector . '"';
			$url = $row->url;
			if (strlen($url) > 0)
				$query2 .= ' AND (' . HUT_Common::URL_COLUMN . ' = "' . $url . '" OR ' . HUT_Common::URL_COLUMN . ' = "")';
		
			$name = $wpdb->get_col($query2, 0);
			$is_form_submit = $wpdb->get_col($query2, 1);
				
			array_push($user_activities, array(
					'type' => 'element_selector',
					'name' => $name[0],
					'element_selector' => $row->element_selector,
					'record_date' => $row->record_date,
					'is_form_submit' => $is_form_submit[0]
			));
		}
		
		usort($user_activities, array( &$this, 'sort_user_activities_by_time' ) );
		
		// now the table data
		
		$items = array();
		if (count($user_activities) == 0)
			return $items;
		
		$row_count = 0;
		foreach ($user_activities as $user_activity) {
			$current_activity_date = $user_activity['record_date'];
				
			$previous_activity_date = null;
			if ($row_count != 0)
				$previous_activity_date = $user_activities[$row_count - 1]['record_date'];
				
			// we're not using inbuilt WordPress function human_time_diff because it's not accurate enough
			$human_time_diff = '';
			if ($previous_activity_date != null) {
				$current_activity_time = strtotime($current_activity_date);
				$previous_activity_time = strtotime($previous_activity_date);
				$human_time_diff = $this->human_time_diff($previous_activity_time, $current_activity_time);
			}
				
			$activity_type = $user_activity['type'];
				
			// Create table information
			$event = '';
			$description = '';
			$additional_info = '';
			$actions = '';
			if ($activity_type == 'ajax') {
				$event = 'Action';
				$ajax_action = $user_activity['ajax_action'];
				$status_text = $user_activity['status_text'];
				$description = 'AJAX action with name "' . $ajax_action . '" was processed';
				$additional_info = 'status text ' . $status_text;
			} else if ($activity_type == 'element_selector') {
				$event = 'Element selector';
				$name = $user_activity['name'];
				$is_form_submit = $user_activity['is_form_submit'];
				if ($is_form_submit)
					$description = $name . ' was submitted';
				else
					$description = $name . ' impression occured';
			} else if ($activity_type == 'url') {
				$event = 'Page load';
				$url = $user_activity['url'];
				$description = 'Navigated to URL <a href="' . stripslashes($url) . '">' . $url . '</a>';
			} else { // click_tap
				$x = $user_activity['x'];
				$y = $user_activity['x'];
				$width = $user_activity['width'];
				$is_tap = $user_activity['is_tap'];
				$device_pixel_ratio = $user_activity['device_pixel_ratio'];
				$zoom_level = $user_activity['zoom_level'];
				$url = $user_activity['url'];
				$click_tap_id = $user_activity['id'];
					
				$event = 'Mouse click';
				$description = 'A mouse click was made';
				if ($is_tap) {
					$event = 'Touch Screen Tap';
					$description = 'A touch screen tap was made';
				}
		
				$additional_info = 'x=' . $x . ', y=' . $y . ', width=' . $width .' pixels, device pixel ratio=' . HUT_Common::convert_decimalto_ratio($device_pixel_ratio) . ' and zoom level=' . $zoom_level * 100 . '%';
					
				$actions .= '<input type="hidden" id="' . $row_count . '-url" name="' . $row_count . '-url" value="' . addslashes($url) . '"></input>';
				$actions .= '<input type="hidden" id="' . $row_count . '-width" name="' . $row_count . '-width" value="' . $width . '"></input>';
				$actions .= '<input type="hidden" id="' . $row_count . '-click_tap_id" name="' . $row_count . '-click_tap_id" value="' . $click_tap_id . '"></input>';
				$actions .= '<input type="hidden" id="' . $row_count . '-device_pixel_ratio" name="' . $row_count . '-device_pixel_ratio" value="' . $device_pixel_ratio . '"></input>';
				$actions .= '<input type="hidden" id="' . $row_count . '-zoom_level" name="' . $row_count . '-zoom_level" value="' . $zoom_level . '"></input>';
				if (isset($item[ 'browser_family' ])) {
					$browser_family = $item[ 'browser_family' ];
					$actions .= '<input type="hidden" id="' . $row_count . '-browser_family" name="' . $row_count . '-browser_family" value="' . $browser_family . '"></input>';
				}
				if (isset($item[ 'os_family' ])) {
					$os_family = $item[ 'os_family' ];
					$actions .= '<input type="hidden" id="' . $row_count . '-os_family" name="' . $row_count . '-os_family" value="' . $os_family . '"></input>';
				}
				if (isset($item[ 'device' ])) {
					$device = $item[ 'device' ];
					$actions .= '<input type="hidden" id="' . $row_count . '-device" name="' . $row_count . '-device" value="' . $device . '"></input>';
				}
		
				// View heat map button
				$actions .= '<input id="' . $row_count .'" type="button" class="button view-heat-map-button" value="View ' . $event . '" />';
			}
				
				
			$previous_activity_type = null;
			if ($row_count > 1)
				$previous_activity_type = $user_activities[$row_count - 1]['type'];

			array_push($items, array('event' => $event, 'description' => $description, 'order' => $row_count, 'time_elapsed' => $human_time_diff, 'date_time' => $current_activity_date, 'actions' => $actions, 'additional_info' => $additional_info));
			
			$row_count++;
			
		}
		
		return $items;
	}
	
	/**
	 * Sorts user activities by time
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @return number
	 */
	function sort_user_activities_by_time($a, $b) {
		if ($a['record_date'] == $b['record_date']) {
			if ($a['type'] == 'url' || $b['type'] == 'url') {
				if ($b['type'] == 'form_submit')
					return 1;
				if ($a['type'] == 'form_submit')
					return -1;
			}
			return 0;
		}
	
		return ($a['record_date'] <= $b['record_date']) ? -1 : 1;
	}
	
	/**
	 * A more accurate hum_time_diff function than the one inbuilt with WordPress
	 *
	 * @param $from_date
	 * @param $to_date
	 * @return $human_time_diff
	 */
	function human_time_diff($from_date, $to_date) {
		$human_time_diff = '';
		$time_diff = $to_date - $from_date;
		$mins_diff = intval( ( $time_diff ) / 60 );
		$seconds_diff = ( $time_diff ) % 60;
		$hours_diff = 0;
		if ($mins_diff > 0)
			$hours_diff = intval( $mins_diff / 60);
	
		// days are not necessary
	
		// hours first
		if ($hours_diff > 0) {
			// must subtract here otherwise the minutes is not right
			$mins_diff -= $hours_diff * 60;
	
			$human_time_diff .= $hours_diff . ' hour';
			if ($human_time_diff != 1)
				$human_time_diff .= 's';
			if ($seconds_diff > 0 || $mins_diff > 0) {
				if (($seconds_diff > 0 && $hours_diff == 0)
						|| ($seconds_diff == 0 && $hours_diff > 0))
					$human_time_diff .= ' and ';
				else
					$human_time_diff .= ', ';
			}
		}
	
		// then minutes
		if ($mins_diff > 0) {
			$human_time_diff .= $mins_diff . ' minute';
			if ($mins_diff != 1)
				$human_time_diff .= 's';
			if ($seconds_diff > 0 )
				$human_time_diff .= ' and ';
		}
	
		// then seconds
		if ($seconds_diff > 0) {
			$human_time_diff .= $seconds_diff .= ' second';
			if ($seconds_diff != 1)
				$human_time_diff .= 's';
		}
	
		if (strlen($human_time_diff) == 0) {
			$human_time_diff .= '< 1 second';
		}
	
		return $human_time_diff;
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'order' :
			case 'event':
			case 'description' :
			case 'time_elapsed':
			case 'additional_info':
			case 'actions':
				return $item[$column_name];
			break;
			case 'date_time':
				echo date("F j, Y, g:i a", strtotime($item[$column_name]));
			break;
			default:
				return print_r( $item, true ) ;
		}
	}
}




/**
 * HUT_Users_Table class displays users
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_Users_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'User',
				'plural' => 'Users',
				'ajax'	=> false
		) );
	}

	function display_tablenav( $which ) {
		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : null;
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : null;
		if ($ip_address == null || $session_id == null)
			parent::display_tablenav($which);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : null;
			$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : null;
			if ($ip_address == null || $session_id == null) {
				?>
				<div class="alignleft">
					<?php 
					global $wpdb;
					$query = 'SELECT DISTINCT ' . HUT_Common::URL_COLUMN . ' FROM '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME;
					$rows = $wpdb->get_results($query);
					echo '<label for="' . HUT_Common::URL_SEARCH_INPUT . '">URL</label>&nbsp;';
					echo '<select name="' . HUT_Common::URL_SEARCH_INPUT . '" id="' . HUT_Common::URL_SEARCH_INPUT . '">';
					echo '<option value=""></option>';
					foreach ($rows as $row) {
						$url = $row->url;
						$selected = '';
						if ($url == $_POST[HUT_Common::URL_SEARCH_INPUT])
							$selected = ' selected="selected"';
						echo '<option value="' . $row->url . '"' . $selected . '>' . $row->url . '</option>';
					}
					echo '</select>';
					
					$start_date = isset($_POST[HUT_Common::START_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::START_DATE_SEARCH_INPUT] : '';
					$end_date = isset($_POST[HUT_Common::END_DATE_SEARCH_INPUT]) ? $_POST[HUT_Common::END_DATE_SEARCH_INPUT] : '';
					?>
					<label>Start date</label>&nbsp;
					<input type="text" name="<?php echo HUT_Common::START_DATE_SEARCH_INPUT; ?>" id="<?php echo HUT_Common::START_DATE_SEARCH_INPUT; ?>" class="date-field" value="<?php echo $start_date; ?>" />
					<label>End date</label>&nbsp;
					<input type="text" name="<?php echo HUT_Common::END_DATE_SEARCH_INPUT; ?>" id="<?php echo HUT_Common::END_DATE_SEARCH_INPUT; ?>" class="date-field" value="<?php echo $end_date; ?>" />
					<?php 
					submit_button( __( 'Filter' ), 'submit', false, false, array( 'id' => 'filter-submit' ) );
					?>
				</div><?php 
			}
		}
		if ( $which == "bottom" ){
			echo '';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				HUT_Common::IP_ADDRESS_COLUMN => __('IP Address'),
				HUT_Common::SESSION_ID_COLUMN => __('Session ID'),
				'user_login' => __('Username'),
				'role' => __('Role'),
				'date' => __('Last Page Hit Date'),
				'page_view_count' => __('Page Hits'),
				'ajax_action_count' => __('AJAX Actions'),
				'click_tap_count' => __('Clicks/Taps'),
				'element_selector_count' => __('Element Selectors'),
				'view_user_acticity' => __('Action')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array( );
		
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$query = 'SELECT ip_address, session_id, record_date as r, record_date FROM ( SELECT * FROM ' . $wpdb->prefix . HUT_Common::URL_PING_TBL_NAME . ' ';
		$query = HUT_Common::add_filters_to_query($query);
		 
		// FIXME order by record_date, temporary workaround using alias as r for ORDER BY
		$query .= 'ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC ) AS a GROUP BY ' . HUT_Common::IP_ADDRESS_COLUMN . ', session_id ORDER BY r DESC';
		
		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$items_per_page = 10;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if ( empty( $page_num ) || !is_numeric( $page_num ) || $page_num <= 0 ) {
			$page_num = 1;
		}
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( !empty( $page_num ) && !empty( $items_per_page ) ) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int) $offset. ',' .(int) $items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case HUT_Common::IP_ADDRESS_COLUMN :
			case 'session_id' :
			case HUT_Common::RECORD_DATE_COLUMN :
			case 'page_view_count' :
			case 'ajax_action_count' :
			case 'click_tap_count' :
			case 'view_user_acticity' :
			case 'element_selector_count' :
			case 'role' :
			case 'user_login' :
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ;
		}
	}
	function column_ip_address( $item ){
		echo $item['ip_address'];
	}
	function column_user_login( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		
		$query = 'SELECT user_login FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$user_login = $wpdb->get_col($query, 0);
		
		if (isset($user_login) && is_array($user_login) && count($user_login) > 0)
			echo $user_login[0];
		else
			echo '';
	}
	function column_session_id( $item ){
		echo $item['session_id'];
	}
	/**
	 * column date
	 * @param unknown_type $item
	 * @return string
	 */
	function column_date( $item ){
		echo date("F j, Y, g:i a", strtotime($item[HUT_Common::RECORD_DATE_COLUMN]));
	}
	
	/**
	 * column page view count
	 * @param unknown_type $item
	 * @return string
	 */
	function column_page_view_count( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::URL_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$count = $wpdb->query($query);
		
		echo $count;
	}
	
	/**
	 * column ajax action count
	 * @param unknown_type $item
	 * @return string
	 */
	function column_ajax_action_count( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::AJAX_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$count = $wpdb->query($query);
	
		echo $count;
	}
	
	/**
	 * column click tap count
	 * @param unknown_type $item
	 * @return string
	 */
	function column_click_tap_count( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$count = $wpdb->query($query);
	
		echo $count;
	}
	
	/**
	 * column click tap count
	 * @param unknown_type $item
	 * @return string
	 */
	function column_role( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
	
		$query = 'SELECT role FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$role = $wpdb->get_col($query, 0);
		
		global $wp_roles;
		if ( ! isset( $wp_roles) )
			$wp_roles = new WP_Roles();
		
		$roles = $wp_roles->get_names();
		
		if (count($role) > 0 && $role[0] != null)
			echo $roles[$role[0]];
		else
			echo '';
	}
	
	/**
	 * column Element selector
	 * @param unknown_type $item
	 * @return string
	 */
	function column_element_selector_count( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_PING_TBL_NAME . ' where '. HUT_Common::IP_ADDRESS_COLUMN .' = "' .$ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		$count = $wpdb->query($query);
	
		echo $count;
	}
	
	/**
	 * column click tap count
	 * @param unknown_type $item
	 * @return string
	 */
	function column_view_user_acticity( $item ){
		global $wpdb;
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		echo '<a href="admin.php?page=hut_user_tracking_page&tab=user_activity_tab&ip_address=' . $ip_address . '&session_id=' . $session_id . '">View User Activity</a>';
	}
}








/**
 * HUT_Element_Selector_Table class used for displaying element selectors that are tracked
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_Element_Selector_Table extends WP_List_Table {

	const
	CHECKBOX_COLUMN 				= 'cb',
	DELETE_CHECKBOX 				= 'delete[]',
	DELETE_BULK_ACTION_NAME			= 'delete',
	DELETE_BULK_ACTION_LABEL		= 'Delete';


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'Link Table',
				'plural' => 'Links Table',
				'ajax'	=> false
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			echo '';
		}
		if ( $which == "bottom" ){
			echo '';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				HUT_Element_Selector_Table::CHECKBOX_COLUMN => '<input type="checkbox" />',
				HUT_Common::ID_COLUMN => __(''),
				'element_selector' => __('Element Selector'),
				'name' => __('Name'),
				'is_form_submit' => __('Is Form Submit'),
				'url' => __('URL')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Process any bulk actions first
		$this->process_bulk_action();

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array(HUT_Common::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME;

		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$items_per_page = 10;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if ( empty( $page_num ) || !is_numeric( $page_num ) || $page_num <= 0 ) {
			$page_num = 1;
		}
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( !empty( $page_num ) && !empty( $items_per_page ) ) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int) $offset. ',' .(int) $items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );

		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {				
			case HUT_Common::ID_COLUMN :
			case 'name' :
			case 'element_selector':{
				echo $item[ $column_name ];
				break;
			}
			case 'is_form_submit': {
				echo ($item[ $column_name ] == 0) ? 'false' : 'true';
				break;
			}
			case 'url' :
			case HUT_Element_Selector_Table::CHECKBOX_COLUMN :
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ;
		}
	}
	
	/**
	 * url column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_url( $item ) {
		echo stripslashes( $item['url'] );
	}
	
	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="'.HUT_Element_Selector_Table::DELETE_CHECKBOX.'" value="%s" />', $item[HUT_Common::ID_COLUMN]
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
				HUT_Element_Selector_Table::DELETE_BULK_ACTION_NAME => 'Delete'
		);
		return $actions;
	}

	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		if ($this->current_action() === HUT_Element_Selector_Table::DELETE_BULK_ACTION_NAME) {
			global $wpdb;

			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );

			foreach($checked as $id) {
				$query = "DELETE FROM ". $wpdb->prefix.HUT_Common::ELEMENT_SELECTOR_TBL_NAME . " WHERE " .  HUT_Common::ID_COLUMN . " = " . $id;
				$results = $wpdb->query( $query );
			}
		}
	}
}




/**
 * A table for filtering heat map details
 *
 * @author dpowney
 *
 */
class HUT_Heat_Maps_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'Heat Map Detail',
				'plural' => 'Heat Maps Details',
				'ajax'	=> false
		) );
	}

	/** (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			$browser_family = isset($_SESSION['browser_family']) ? $_SESSION['browser_family'] : '';
			$os_family = isset($_SESSION['os_family']) ? $_SESSION['os_family'] : '';
			$device = isset($_SESSION['device']) ? $_SESSION['device'] : '';
			$url= isset($_SESSION['url']) ? $_SESSION['url'] : '';
			$width = isset($_SESSION['width']) ? $_SESSION['width'] : '';
			$zoom_level= isset($_SESSION['zoom_level']) ? $_SESSION['zoom_level'] : '';
			$device_pixel_ratio= isset($_SESSION['device_pixel_ratio']) ? $_SESSION['device_pixel_ratio'] : '';
			$show_uaparser= isset($_SESSION['show_uaparser']) ? $_SESSION['show_uaparser'] : false;

			global $wpdb;
			
			// URL
			$query = 'SELECT DISTINCT ' . HUT_Common::URL_COLUMN . ' FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '<label for="url">URL</label>';
			echo '&nbsp;<select name="url" id="url">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_url = stripslashes($row->url);
				$selected = '';
				if ($current_url == $url)
					$selected = ' selected="selected"';
				echo '<option value="' . addslashes($current_url) . '"' . $selected . '>' . $current_url . '</option>';
			}
			echo '</select>';
			
			// Width
			$query = 'SELECT DISTINCT width FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="width">Width</label>';
			echo '&nbsp;<select name="width" id="width">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_width= $row->width;
				$selected = '';
				if ($current_width == $width)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_width . '"' . $selected . '>' . $current_width . 'px</option>';
			}
			echo '</select>';
				
			// Device Pixel Ratio
			$query = 'SELECT DISTINCT device_pixel_ratio FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="device_pixel_ratio">Device Pixel Ratio</label>';
			echo '&nbsp;<select name="device_pixel_ratio" id="device_pixel_ratio">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_device_pixel_ratio= $row->device_pixel_ratio;
				$selected = '';
				if ($current_device_pixel_ratio == $device_pixel_ratio)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_device_pixel_ratio . '"' . $selected . '>' . HUT_Common::convert_decimalto_ratio($current_device_pixel_ratio) . '</option>';
			}
			echo '</select>';
				
			// Zoom level
			$query = 'SELECT DISTINCT zoom_level FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="zoom_level">Zoom Level</label>';
			echo '&nbsp;<select name="zoom_level" id="zoom_level">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_zoom_level = $row->zoom_level;
				$selected = '';
				if ($current_zoom_level == $zoom_level)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_zoom_level . '"' . $selected . '>' . ($current_zoom_level * 100). '%</option>';
			}
			echo '</select>';
				
			// Browser
			$query = 'SELECT DISTINCT browser_family FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="browser_family">Internet Browser</label>';
			echo '&nbsp;<select name="browser_family" id="browser_family">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_browser_family = $row->browser_family;
				$selected = '';
				if ($current_browser_family == $browser_family)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_browser_family . '"' . $selected . '>' . $current_browser_family . '</option>';
			}
			echo '</select>';
			
			// Operating System
			$query = 'SELECT DISTINCT os_family FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="os_family">Operating System</label>';
			echo '&nbsp;<select name="os_family" id="os_family">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_os_family = $row->os_family;
				$selected = '';
				if ($current_os_family == $os_family)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_os_family . '"' . $selected . '>' . $current_os_family . '</option>';
			}
			echo '</select>';
			
			// Device
			$query = 'SELECT DISTINCT device FROM '.$wpdb->prefix. HUT_Common::CLICK_TAP_TBL_NAME;
			$rows = $wpdb->get_results($query);
			echo '&nbsp;&nbsp;<label for="device">Device</label>';
			echo '&nbsp;<select name="device" id="device">';
			echo '<option value="">All</option>';
			foreach ($rows as $row) {
				$current_device = $row->device;
				$selected = '';
				if ($current_device == $device)
					$selected = ' selected="selected"';
				echo '<option value="' . $current_device . '"' . $selected . '>' . $current_device . '</option>';
			}
			echo '</select>';
			
			echo '<input type="checkbox" name="show_uaparser" id="show_uaparser" ' . checked(true, $show_uaparser, false) . '/>';
			echo '&nbsp;<label for="show_uaparser">Include browser, OS & device columns</label>';
			
			echo '&nbsp;&nbsp;<input type="submit" class="button" value="Filter" />';
			
		}
		if ( $which == "bottom" ){
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
			'id' => __('ID'),
			'url' => __('URL'),
			'width' => __('Width'),
			'count' => __('Clicks / Taps'),
			'zoom_level' => __('Zoom Level'),
			'device_pixel_ratio' => __('Device Pixel Ratio'),
			'browser_family' => __('Internet Browser'),
			'os_family' => 'Operating System',
			'device' => __('Device'),
			'action' => __('Action')
				
		);
	}

	/**
	 * Resets the session object if a POST has been made
	 */
	function reset_session_object() {

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$browser_family = isset($_POST["browser_family"]) ? $_POST["browser_family"]  : '';
			$os_family = isset($_POST["os_family"]) ? $_POST["os_family"]  : '';
			$device = isset($_POST["device"]) ? $_POST["device"]  : '';
			$url = isset($_POST["url"]) ? stripslashes($_POST["url"])  : '';
			$width = isset($_POST["width"]) ? $_POST["width"]  : '';
			$zoom_level = isset($_POST["zoom_level"]) ? $_POST["zoom_level"]  : '';
			$device_pixel_ratio = isset($_POST["device_pixel_ratio"]) ? $_POST["device_pixel_ratio"]  : '';
			$show_uaparser = false;
			if (isset($_POST["show_uaparser"])) {
				$show_uaparser = ($_POST["show_uaparser"] == "on") ? true  : false;
			}
			
			$_SESSION['browser_family'] = $browser_family;
			$_SESSION['os_family'] = $os_family;
			$_SESSION['device'] = $device;
			$_SESSION['url'] = $url;
			$_SESSION['width'] = $width;
			$_SESSION['zoom_level'] = $zoom_level;
			$_SESSION['device_pixel_ratio'] = $device_pixel_ratio;
			$_SESSION['show_uaparser'] = $show_uaparser;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		$this->reset_session_object();
		
		$show_uaparser= isset($_SESSION['show_uaparser']) ? $_SESSION['show_uaparser'] : false;

		global $wpdb;

		// Register the columns
		$columns = $this->get_columns();
		
		$hidden = array('id');
		if ($show_uaparser == false)
			$hidden = array('id', 'browser_family', 'os_family', 'device');
			
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		// Query params
		$browser_family = isset($_SESSION['browser_family']) ? $_SESSION['browser_family'] : '';
		$os_family = isset($_SESSION['os_family']) ? $_SESSION['os_family'] : '';
		$device = isset($_SESSION['device']) ? $_SESSION['device'] : '';
		$url= isset($_SESSION['url']) ? $_SESSION['url'] : '';
		$width = isset($_SESSION['width']) ? $_SESSION['width'] : '';
		$zoom_level= isset($_SESSION['zoom_level']) ? $_SESSION['zoom_level'] : '';
		$device_pixel_ratio= isset($_SESSION['device_pixel_ratio']) ? $_SESSION['device_pixel_ratio'] : '';
		
		// get table data
		$query = 'SELECT id, url, width, device_pixel_ratio, zoom_level, COUNT(*) AS count ';
		if ($show_uaparser)
			$query .= ', browser_family, os_family, device';
		$query .= ' FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE 1';
		if ($url != null) {
			$query .= ' AND url = "'. $url . '"';
		}
		if ($show_uaparser) {
			if ($browser_family != null) {
				$query .= ' AND browser_family = "' . $browser_family . '"';
			}
			if ($os_family != null) {
				$query .= ' AND os_family = "' . $os_family . '"';
			}
			if ($device != null) {
				$query .= ' AND device = "' . $device . '"';
			}
		}
		if ($width != null) {
			$query .= ' AND width = "' . $width . '"';
		}
		if ($device_pixel_ratio != null) {
			$query .= ' AND device_pixel_ratio = "' . $device_pixel_ratio . '"';
		}
		if ($zoom_level != null) {
			$query .= ' AND zoom_level = "' . $zoom_level . '"';
		}
		$query .= ' GROUP BY url, width, device_pixel_ratio, zoom_level';
		if ($show_uaparser)
			$query .= ', browser_family, os_family, device';
		$query .= ' ORDER BY count DESC';
		
		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		$items_per_page = 25;
		// Ensure paging is reset on filter submit by checking HTTP method as well
		$page_num = !empty($_GET["paged"]) && ($_SERVER['REQUEST_METHOD'] != 'POST') ? mysql_real_escape_string($_GET["paged"]) : '';
		if (empty($page_num) || !is_numeric($page_num) || $page_num<=0 ) {
			$page_num = 1;
		}
		$total_pages = ceil($item_count/$items_per_page);
		// adjust the query to take pagination into account
		if (!empty($page_num) && !empty($items_per_page)) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );
		
		$this->items =  $wpdb->get_results($query, ARRAY_A);
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id' :
			case 'browser_family':
			case 'os_family':
			case 'device':
				echo $item[ $column_name ];
				break;
			case 'url':
			case 'count':
			case 'width':
			case 'device_pixel_ratio':
			case 'zoom_level':
				return $item[ $column_name ];
				break;
			case 'action':
				// Hidden input for target data
				$id = $item[ 'id' ];
				$url = $item[ 'url' ];
				echo '<input type="hidden" id="' . $id . '-url" name="' . $id . '-url" value="' . addslashes($url) . '"></input>';
				$width = $item[ 'width' ];
				echo '<input type="hidden" id="' . $id . '-width" name="' . $id . '-width" value="' . $width . '"></input>';
				$device_pixel_ratio = $item[ 'device_pixel_ratio' ];
				echo '<input type="hidden" id="' . $id . '-device_pixel_ratio" name="' . $id . '-device_pixel_ratio" value="' . $device_pixel_ratio . '"></input>';
				$zoom_level = $item[ 'zoom_level' ];
				echo '<input type="hidden" id="' . $id . '-zoom_level" name="' . $id . '-zoom_level" value="' . $zoom_level . '"></input>';
				if (isset($item[ 'browser_family' ])) {
					$browser_family = $item[ 'browser_family' ];
					echo '<input type="hidden" id="' . $id . '-browser_family" name="' . $id . '-browser_family" value="' . $browser_family . '"></input>';
				}
				if (isset($item[ 'os_family' ])) {
					$os_family = $item[ 'os_family' ];
					echo '<input type="hidden" id="' . $id . '-os_family" name="' . $id . '-os_family" value="' . $os_family . '"></input>';
				}
				if (isset($item[ 'device' ])) {
					$device = $item[ 'device' ];
					echo '<input type="hidden" id="' . $id . '-device" name="' . $id . '-device" value="' . $device . '"></input>';
				}
				// View heat map button
				echo '<input id="' . $id .'" type="button" class="button view-heat-map-button" value="View Heat Map" />';

						
			break;
			default:
				return print_r( $item, true ) ;
		}
	}
	
	/**
	 * device pixel ratio column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_device_pixel_ratio($item){
		echo HUT_Common::convert_decimalto_ratio($item['device_pixel_ratio']);
	}
	
	/**
	 * zoom level column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_zoom_level($item){
		echo (100 * $item['zoom_level']) . '%';
	}
	
	/**
	 * width column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_width($item){
		echo $item['width'] . 'px';
	}

	/**
	 *
	 * @param unknown_type $item
	 */
	function column_count($item) {
		// get widths for url, and create a select
		$totalCount = $item['count'];
		$url = $item['url'];
		$device_pixel_ratio = $item['device_pixel_ratio'];
		$zoom_level = $item['zoom_level'];
		$width = $item['width'];
		
		global $wpdb;
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME.' WHERE ' . HUT_Common::IS_TAP_COLUMN . ' = 1';
		$query .= ' AND url = "' . $url . '" AND device_pixel_ratio = "' .  $device_pixel_ratio . '" AND zoom_level = "' . $zoom_level . '"';
		$query .= ' AND width = "' . $width . '"';
		
		if (isset($item[ 'browser_family' ]))
			$query .= ' AND browser_family = "' . $item[ 'browser_family' ] . '"';
		if (isset($item[ 'os_family' ]))
			$query .= ' AND os_family = "' . $item[ 'os_family' ] . '"';
		if (isset($item[ 'device' ]))
			$query .= ' AND device = "' . $item[ 'device' ] . '"';

		$tapCount = $wpdb->query($query); //return the total number of affected rows
		
		echo ($totalCount - $tapCount) . ' clicks and ' . $tapCount . ' taps';
	}
	
	/**
	 * count column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_url($item){
		$url = $item['url'];
		echo stripslashes($url);
	}
}


/**
 * HUT_URL_Filter_Table class used for whitelist or blacklist filtering of URL's
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_URL_Filter_Table extends WP_List_Table {

	const
	URL_COLUMN 						= 'url',
	ID_COLUMN 						= 'id',
	CHECKBOX_COLUMN 				= 'cb',
	URL_LABEL 						= 'URL',
	ID_LABEL 						= "ID",
	DELETE_CHECKBOX 				= 'delete[]',
	URL_FILTER_TBL_NAME 			= 'hut_url_filter',
	DELETE_BULK_ACTION_NAME			= 'delete',
	DELETE_BULK_ACTION_LABEL		= 'Delete';


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'URL Filter Table',
				'plural' => 'URL Filter Table',
				'ajax'	=> false
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//echo '<br />';
		}
		if ( $which == "bottom" ){
			echo '<br />';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				HUT_URL_Filter_Table::CHECKBOX_COLUMN => '<input type="checkbox" />',
				HUT_URL_Filter_Table::URL_COLUMN =>__(HUT_URL_Filter_Table::URL_LABEL),
				HUT_URL_Filter_Table::ID_COLUMN => __('')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;

		// Process any bulk actions first
		$this->process_bulk_action();

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array(HUT_URL_Filter_Table::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.$wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME;

		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$items_per_page = 10;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if ( empty( $page_num ) || !is_numeric( $page_num ) || $page_num <= 0 ) {
			$page_num = 1;
		}
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( !empty( $page_num ) && !empty( $items_per_page ) ) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int) $offset. ',' .(int) $items_per_page;
		}
		$this->set_pagination_args( array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page ) );



		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case HUT_URL_Filter_Table::CHECKBOX_COLUMN :
			case HUT_URL_Filter_Table::ID_COLUMN :
			case HUT_URL_Filter_Table::URL_COLUMN :
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ;
		}
	}

	/**
	 * description column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_url( $item ){
		echo stripslashes( $item[HUT_URL_Filter_Table::URL_COLUMN] );
	}

	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="'.HUT_URL_Filter_Table::DELETE_CHECKBOX.'" value="%s" />', $item[HUT_URL_Filter_Table::ID_COLUMN]
		);
	}
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
				HUT_URL_Filter_Table::DELETE_BULK_ACTION_NAME => HUT_URL_Filter_Table::DELETE_BULK_ACTION_LABEL
		);
		return $actions;
	}

	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		if ($this->current_action() === HUT_URL_Filter_Table::DELETE_BULK_ACTION_NAME) {
			global $wpdb;

			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );
				
			foreach($checked as $id) {
				$query = "DELETE FROM ". $wpdb->prefix.HUT_URL_Filter_Table::URL_FILTER_TBL_NAME . " WHERE " .  HUT_URL_Filter_Table::ID_COLUMN . " = " . $id;
				$results = $wpdb->query( $query );
			}
		}
	}
}

?>