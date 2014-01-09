<?php 

if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-list-table.php' );
}
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'common.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'query-helper.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'local-data-services.php';


/**
 * A table for filtering users
 *
 * @author dpowney
 *
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

	/** (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ) {
			$query_helper = new HUT_Query_Helper();
			$query_helper->get_session_filters(array('last_days' => true, 'ip_address' => true, 'username' => true, 'role' => true));
				
			$filters = array(
					'last_days' => true,
					'ip_address' => true,
					'username' => true,
					'role' => true
			);
			$query_helper->show_filters($filters);
			
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
				'session_id' => __('Session ID'),
				'ip_address' => __('IP Address'),
				'username' => __('Username'),
				'role' => __('Role'),
				'record_date' => __('Record Date'),
				'id' => __('Id'),
				'user_id' => __('User Id'),
				'user_env_id' => __('User Env Id'),
				'count_total_events' => ('Total Events'),
				'count_mouse_clicks' => ('Mouse Clicks'),
				'count_touchscreen_taps' => ('Touchscreen Taps'),
				'count_ajax_actions' => ('AJAX Actions'),
				'count_page_views' => ('Page Views'),
				'count_custom' => ('Custom Events'),
				'action' => __('Action'),
				'device' => __('Device'),
				'browser' => __('Browser'),
				'os' => __('Operating System'),
				'page_width' => __('Page Width')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		
		$query_helper = new HUT_Query_Helper();
		$query_helper->get_session_filters(array('last_days' => true, 'ip_address' => true, 'username' => true, 'role' => true));
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$query_helper->get_http_filters('POST');
		} else {
			$query_helper->get_http_filters('GET');
		}
		$query_helper->set_session_filters();

		// Register the columns
		$columns = $this->get_columns();

		$hidden = array('user_env_id', 'user_id', 'id');
			
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$items_per_page = 25;
		// Ensure paging is reset on filter submit by checking HTTP method as well
		$page_num = !empty($_GET["paged"]) && ($_SERVER['REQUEST_METHOD'] != 'POST') ? mysql_real_escape_string($_GET["paged"]) : '';
		if (empty($page_num) || !is_numeric($page_num) || $page_num<=0 ) {
			$page_num = 1;
		}
		
		global $hut_admin_controller;
		$data = $hut_admin_controller->get_data_services()->users_table_data($query_helper->get_filters(), $items_per_page, $page_num);
		
		$this->set_pagination_args( $data['pagination_args'] );
		$this->items =   $data['items'];
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'action' :
			case 'event_type':
				return $item[$column_name];
				break;
			case 'count_mouse_clicks':
				echo $this->get_event_type_count(HUT_Common::MOUSE_CLICK_EVENT_TYPE, $item['user_id']);
				break;
			case 'count_touchscreen_taps':
				echo $this->get_event_type_count(HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE, $item['user_id']);
				break;
			case 'count_ajax_actions':
				echo $this->get_event_type_count(HUT_Common::AJAX_ACTION_EVENT_TYPE, $item['user_id']);
				break;
			case 'count_page_views':
				echo $this->get_event_type_count(HUT_Common::PAGE_VIEW_EVENT_TYPE, $item['user_id']);
				break;
			case 'count_custom':
				echo $this->get_event_type_count(null, $item['user_id']);
				break;
			case 'record_date':
				echo date("F j, Y, g:i a", strtotime($item[$column_name]));
				break;
			case 'page_width':
				echo $item[$column_name] . 'px';
				break;
			default:
				echo $item[$column_name];
				break;
		}
	}
		
	function column_action( $item ){
		$ip_address = $item[HUT_Common::IP_ADDRESS_COLUMN];
		$session_id = $item[HUT_Common::SESSION_ID_COLUMN];
		echo '<a href="admin.php?page=' . HUT_Common::USERS_PAGE_SLUG . '&tab=' . HUT_Common::USER_ACTIVITY_TAB . '&ip_address=' . $ip_address . '&session_id=' . $session_id . '">View User Activity</a>';
	}

	public static function get_event_type_count($event_type, $user_id) {
		global $wpdb;
		$query = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' as u_event WHERE u_event.'
		. HUT_Common::USER_ID_COLUMN . ' = "' . $user_id . '"';
		if ($event_type != null) {
			$query .= ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' = "' . $event_type . '"';
		} else {
			$query .= ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::MOUSE_CLICK_EVENT_TYPE . '"' .
			' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"' .
			' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::AJAX_ACTION_EVENT_TYPE . '"' .
			' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"' .
			' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::PAGE_VIEW_EVENT_TYPE . '"';
		}
		$count = $wpdb->get_col($query, 0);
		echo $count[0];
	}
}

?>