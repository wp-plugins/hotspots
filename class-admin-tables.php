<?php 

if (!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}



/**
 * HUT_User_Summary_Table class displays user summary for session
 *
 * @author dpowney
 * @since 2.0
 */
class HUT_User_Summary_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> 'User Summary',
				'plural' => 'User Summary',
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
				'zoom_level' => __('Zoom Level'),
				'width' => __('Width'),
				'device_pixel_ratio' => __('Device Pixel Ratio'),
				'browser_family' => __('Browser'),
				'browser_version' => __(''),
				'device' => __('Device'),
				'os_family' => __('Operating System'),
				'os_version' => __('')
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
		$hidden = array('browser_version', 'os_version' );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : '';
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : '';

		$query = 'SELECT zoom_level, width, device_pixel_ratio, browser_family, browser_version, device, os_family, os_version FROM  ' . $wpdb->prefix.HUT_Common::CLICK_TAP_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '" GROUP BY zoom_level, width, device_pixel_ratio, browser_family, browser_version, device, os_family, os_version';

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
			case 'zoom_level' :
				echo (100 * $item['zoom_level']) . '%';
				break;
			case 'device_pixel_ratio':
				echo HUT_Common::convert_decimalto_ratio($item['device_pixel_ratio']);
				break;
			case 'width' :
				echo $item['width'] . 'px';
				break;
			case 'device':
			case 'browser_version':
			case 'os_version':
				echo $item[$column_name];
				break;
			case 'browser_family':
				echo $item['browser_family'] . ' ' . $item['browser_version'];
				break;
			case 'os_family':
				echo $item['os_family'] . ' ' . $item['os_version'];
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
				'element_selector_count' => __('Elements'),
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
		
		$ip_address = isset($_REQUEST["ip_address"]) ? $_REQUEST["ip_address"]  : null;
		$session_id = isset($_REQUEST["session_id"]) ?  $_REQUEST["session_id"] : null;
		
		if ($ip_address && $session_id) {
			$hidden = array( 'view_user_acticity' );
		}
		
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// search inputs
		$start_date = null;
		$end_date = null;
		$url = null;
		
		if ($ip_address == null || $session_id == null) {
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
		}
		
		// get table data
		// FIXME order by record_date, temporary workaround using alias as r for ORDER BY
		$query = 'SELECT ip_address, session_id, record_date as r, record_date FROM ( SELECT * FROM ' . $wpdb->prefix . HUT_Common::URL_PING_TBL_NAME . ' ';
		if ($start_date != null || $end_date != null || $url != null) {
			$query .= 'WHERE ';
		
			if ($start_date && $end_date)
				$query .= HUT_Common::RECORD_DATE_COLUMN . ' >= "' . $start_date . '" AND ' . HUT_Common::RECORD_DATE_COLUMN . ' <= "' . $end_date . '" ';
			else if ($start_date)
				$query .=  HUT_Common::RECORD_DATE_COLUMN . ' >= "' . $start_date . '" ';
			else if ($end_date)
				$query .=  HUT_Common::RECORD_DATE_COLUMN . ' <= "' . $end_date . '" ';
			if ($url && ($start_date || $end_date))
				$query .= 'AND ';
			if ($url)
				$query .= HUT_Common::URL_COLUMN . ' = "' . $url . '" ';
		} else if ($session_id || $ip_address) {
			$query .= 'WHERE ';
			
			if ($session_id && $ip_address)
				$query .= 'session_id = "' . $session_id . '" AND ip_address = "' . $ip_address . '"';
			else if ($session_id)
				$query .= 'session_id = "' . $session_id . '"';
			else
				$query .= 'ip_address = "' . $ip_address . '"';
		}
		
		$query .= 'ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC ) AS a GROUP BY ' . HUT_Common::IP_ADDRESS_COLUMN . ', session_id ORDER BY r DESC';
		
		// pagination
		if ($ip_address == null || $session_id == null) {
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
		}
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