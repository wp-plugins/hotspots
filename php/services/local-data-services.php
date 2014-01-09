<?php 

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'common.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data-services.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'query-helper.php';

/**
 * Implements retrival of event data from wpdb
 * 
 * @author dpowney
 *
 */
class HUT_Local_Data_Services implements HUT_Data_Services {

	public function distinct_url_from_user_events() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::URL_COLUMN . ' FROM '.$wpdb->prefix. HUT_Common::USER_EVENT_TBL_NAME;
		return $wpdb->get_results($query);
	}
	
	public function distinct_event_type_from_user_events() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::EVENT_TYPE_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME;
		return $wpdb->get_results($query);
	}
	
	public function distinct_role_from_user() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::USER_ROLE_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME;
		return $wpdb->get_results($query);
	}
	
	public function distinct_page_width_from_user_events() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::PAGE_WIDTH_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME;
		return $wpdb->get_results($query);
	}
	
	public function distinct_device_from_user_env() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::DEVICE_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix .  HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN;
		return $wpdb->get_results($query);
	}
	
	public function distinct_os_from_user_env() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::OS_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix .  HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN;
		return $wpdb->get_results($query);
	}
	
	public function distinct_browser_from_user_env() {
		global $wpdb;
		$query = 'SELECT DISTINCT ' . HUT_Common::BROWSER_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix .  HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN;
		return $wpdb->get_results($query);
	}
	
	public function heatmaps_table_data($filters, $items_per_page, $page_num) {
		global $wpdb;
		
		// get table data
		$query = 'SELECT COUNT(*) as count, u_event.' . HUT_Common::URL_COLUMN 
				. ' AS ' . HUT_Common::URL_COLUMN . ', u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' AS ' 
				. HUT_Common::RECORD_DATE_COLUMN . ', u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' AS ' 
				. HUT_Common::EVENT_TYPE_COLUMN . ', u_event.' . HUT_Common::PAGE_WIDTH_COLUMN . ' AS ' 
				. HUT_Common::PAGE_WIDTH_COLUMN . ', u_event.' . HUT_Common::DESCRIPTION_COLUMN . ' AS ' 
				. HUT_Common::DESCRIPTION_COLUMN . ', u_event.' . HUT_Common::ID_COLUMN . ' AS ' . HUT_Common::ID_COLUMN 
				. ', u_env.' . HUT_Common::ID_COLUMN . ' AS ' . HUT_Common::USER_ENV_ID_COLUMN . ' FROM ' . $wpdb->prefix 
				. HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME 
				. ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN . ' AND (' 
				. HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::MOUSE_CLICK_EVENT_TYPE . '" OR ' 
				. HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '")';

		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' GROUP BY u_event.' . HUT_Common::URL_COLUMN . ', u_event.' . HUT_Common::PAGE_WIDTH_COLUMN . ', u_event.' . HUT_Common::EVENT_TYPE_COLUMN;
		$query .= ' ORDER BY count DESC';

		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		
		$total_pages = ceil($item_count/$items_per_page);
		// adjust the query to take pagination into account
		if (!empty($page_num) && !empty($items_per_page)) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$items_per_page;
		}
		
		$pagination_args = array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page );
		$items = $wpdb->get_results($query, ARRAY_A);
	
		return array('pagination_args' => $pagination_args, 'items' => $items);
	}

	public function users_table_data($filters, $items_per_page, $page_num) {
		
		global $wpdb;
		
		// get table data
		$query = 'SELECT COUNT(*) as count_total_events, u_env.' . HUT_Common::DEVICE_COLUMN . ' AS ' . HUT_Common::DEVICE_COLUMN . ','
		. 'u_env.' . HUT_Common::BROWSER_COLUMN . ' AS ' . HUT_Common::BROWSER_COLUMN . ','
		. 'u_env.' . HUT_Common::OS_COLUMN . ' AS ' . HUT_Common::OS_COLUMN . ','
		. 'u.' . HUT_Common::SESSION_ID_COLUMN . ' AS ' . HUT_Common::SESSION_ID_COLUMN . ', u.'
		. HUT_Common::IP_ADDRESS_COLUMN . ' AS ' . HUT_Common::IP_ADDRESS_COLUMN . ', u.' . HUT_Common::USERNAME_COLUMN
		. ' AS ' . HUT_Common::USERNAME_COLUMN . ', u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' AS '
		. HUT_Common::RECORD_DATE_COLUMN . ', u.' . HUT_Common::USER_ROLE_COLUMN . ' AS '
		. HUT_Common::USER_ROLE_COLUMN . ', u_event.' . HUT_Common::PAGE_WIDTH_COLUMN . ' AS '
		. HUT_Common::PAGE_WIDTH_COLUMN . ', u_env.' . HUT_Common::ID_COLUMN . ' AS '
		. HUT_Common::USER_ENV_ID_COLUMN . ', u.' . HUT_Common::ID_COLUMN . ' AS '
		. HUT_Common::USER_ID_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME . ' AS u, ' . $wpdb->prefix
		. HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME
		. ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.'
		. HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN;
		
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);

		$query .= ' GROUP BY u.' . HUT_Common::IP_ADDRESS_COLUMN . ', u.' . HUT_Common::SESSION_ID_COLUMN;
		$query .= ' ORDER BY u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' DESC';
		
		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		
		$total_pages = ceil($item_count/$items_per_page);
		// adjust the query to take pagination into account
		if (!empty($page_num) && !empty($items_per_page)) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$items_per_page;
		}
		
		$pagination_args = array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page );
		$items = $wpdb->get_results($query, ARRAY_A);
		
		return array('pagination_args' => $pagination_args, 'items' => $items);
	}
	
	public function user_activity_table_data($filters, $items_per_page, $page_num) {
		global $wpdb;
		
		// get table data
		$query = 'SELECT u_env.' . HUT_Common::DEVICE_COLUMN . ' AS ' . HUT_Common::DEVICE_COLUMN . ','
		. 'u_env.' . HUT_Common::BROWSER_COLUMN . ' AS ' . HUT_Common::BROWSER_COLUMN . ','
		. 'u_env.' . HUT_Common::OS_COLUMN . ' AS ' . HUT_Common::OS_COLUMN . ','
		. 'u.' . HUT_Common::SESSION_ID_COLUMN . ' AS ' . HUT_Common::SESSION_ID_COLUMN . ', u.'
		. HUT_Common::IP_ADDRESS_COLUMN . ' AS ' . HUT_Common::IP_ADDRESS_COLUMN . ', u.' . HUT_Common::USERNAME_COLUMN
		. ' AS ' . HUT_Common::USERNAME_COLUMN . ', u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' AS '
		. HUT_Common::RECORD_DATE_COLUMN . ', u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' AS '
		. HUT_Common::EVENT_TYPE_COLUMN . ', u_event.' . HUT_Common::URL_COLUMN . ' AS '
		. HUT_Common::URL_COLUMN . ', u_event.' . HUT_Common::DESCRIPTION_COLUMN . ' AS '
		. HUT_Common::DESCRIPTION_COLUMN . ', u_event.' . HUT_Common::DATA_COLUMN . ' AS '
		. HUT_Common::DATA_COLUMN . ', u.' . HUT_Common::USER_ROLE_COLUMN . ' AS '
		. HUT_Common::USER_ROLE_COLUMN . ', u_event.' . HUT_Common::PAGE_WIDTH_COLUMN . ' AS '
		. HUT_Common::PAGE_WIDTH_COLUMN . ', u_event.' . HUT_Common::ID_COLUMN . ' AS ' . HUT_Common::ID_COLUMN
		. ', u_env.' . HUT_Common::ID_COLUMN . ' AS ' . HUT_Common::USER_ENV_ID_COLUMN . ', u.' . HUT_Common::ID_COLUMN . ' AS '
		. HUT_Common::USER_ID_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME . ' AS u, ' . $wpdb->prefix
		. HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME
		. ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.'
		. HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN;
		
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' ORDER BY u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' DESC';
		
		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		
		$total_pages = ceil($item_count/$items_per_page);
		// adjust the query to take pagination into account
		if (!empty($page_num) && !empty($items_per_page)) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$items_per_page;
		}
		
		$pagination_args = array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page );
		$items = $wpdb->get_results($query, ARRAY_A);
		
		return array('pagination_args' => $pagination_args, 'items' => $items);
		
	}
	
	public function user_activity_summary_data($filters) {
		global $wpdb;
		
		$query = 'SELECT MIN(u_event.' . HUT_Common::RECORD_DATE_COLUMN . ') AS oldest_record_date, u.' . HUT_Common::IP_ADDRESS_COLUMN . ', u.' . HUT_Common::SESSION_ID_COLUMN . ', u.' . HUT_Common::USERNAME_COLUMN
		. ', u.' . HUT_Common::USER_ROLE_COLUMN . ', MAX(u_event.' . HUT_Common::RECORD_DATE_COLUMN . ') as latest_record_date, COUNT(*) AS count_total'
		. ', count(case when ' . HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::MOUSE_CLICK_EVENT_TYPE . '" THEN 1 ELSE null end) AS count_mouse_clicks '
		. ', count(case when ' . HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::PAGE_VIEW_EVENT_TYPE . '" THEN 1 ELSE null end) AS count_page_views '
		. ', count(case when ' . HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::AJAX_ACTION_EVENT_TYPE . '" THEN 1 ELSE null end) AS count_ajax_actions '
		. ', count(case when ' . HUT_Common::EVENT_TYPE_COLUMN . ' = "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '" THEN 1 ELSE null end) AS count_touchscreen_taps '
		. ', u_env.' . HUT_Common::DEVICE_COLUMN . ' AS ' . HUT_Common::DEVICE_COLUMN . ', u_env.' . HUT_Common::BROWSER_COLUMN
		. ' AS ' . HUT_Common::BROWSER_COLUMN . ', u_env.' . HUT_Common::OS_COLUMN . ' AS ' . HUT_Common::OS_COLUMN . ', u_event.' . HUT_Common::PAGE_WIDTH_COLUMN
		. ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME . ' AS u, ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, '
		. $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN
		. ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.' . HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN;

		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC';
		
		return $wpdb->get_row($query, OBJECT, 0);
		
		
	}
	
	public function summary_report_data($filters, $items_per_page, $page_num) {
		global $wpdb;
		$query = 'SELECT COUNT(*) as ' . HUT_Common::TOTAL_COLUMN . ', u_event.' . HUT_Common::EVENT_TYPE_COLUMN
		. ', u_event.' . HUT_Common::RECORD_DATE_COLUMN . ' AS ' . HUT_Common::RECORD_DATE_COLUMN
		. ', u_env.' . HUT_Common::DEVICE_COLUMN . ' AS ' . HUT_Common::DEVICE_COLUMN . ','
		. 'u_env.' . HUT_Common::BROWSER_COLUMN . ' AS ' . HUT_Common::BROWSER_COLUMN . ','
		. 'u_env.' . HUT_Common::OS_COLUMN . ' AS ' . HUT_Common::OS_COLUMN
		. ', COUNT(DISTINCT u_event.' . HUT_Common::USER_ID_COLUMN . ') as count_users '
		. ', COUNT(DISTINCT u_event.' . HUT_Common::URL_COLUMN . ') as count_pages ' . ' FROM '
		. $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' as u_event, '
		. $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN
		. ' = u_env.' . HUT_Common::ID_COLUMN;
		
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' GROUP BY ' . HUT_Common::EVENT_TYPE_COLUMN;
		
		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( !empty( $page_num ) && !empty( $items_per_page ) ) {
			$offset=($page_num-1)*$items_per_page;
			$query .= ' LIMIT ' .(int) $offset. ',' .(int) $items_per_page;
		}
		
		$pagination_args = array( "total_items" => $item_count, "total_pages" => $total_pages, "per_page" => $items_per_page );
		$items = $wpdb->get_results($query, ARRAY_A);
		
		return array('pagination_args' => $pagination_args, 'items' => $items);
	}
	
	public function events_report_data($filters) {
		global $wpdb;
		
		// Time graph
		$query = 'SELECT DISTINCT DATE(  ' . HUT_Common::RECORD_DATE_COLUMN . ' ) AS day, count(*) as count FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME
		. ' AS u, ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME
		. ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.'
		. HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN;
		
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' GROUP BY day ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC';
		
		$rows = $wpdb->get_results($query);
			
		$time_data = array();
		foreach ($rows as $row) {
			$day = $row->day;
			$count = $row->count;
			// TODO if a day has no data, then make it 0 visitors.
			// Otherwise, it is not plotted on the graph as 0.
		
			array_push($time_data, array((strtotime($day) * 1000), intval($count)));
		}

		return array('time_data' => $time_data);
	}
	
	public function custom_events_report_data($filters) {
		global $wpdb;
		
		// Counts data
		$query = 'SELECT count(*) as count, u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME
		. ' AS u, ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME
		. ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN . ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.'
		. HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::MOUSE_CLICK_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::AJAX_ACTION_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::PAGE_VIEW_EVENT_TYPE . '"';
		
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' GROUP BY ' . HUT_Common::EVENT_TYPE_COLUMN;
		
		$rows = $wpdb->get_results($query);
		$count_data = array();
		foreach ($rows as $row) {
			$event_type = $row->event_type;
			$count = $row->count;
			array_push($count_data, array($event_type, $count));
		}
		
		// Time graph data
		$query = 'SELECT DISTINCT DATE(  ' . HUT_Common::RECORD_DATE_COLUMN . ' ) AS day, u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ', count(*) as count FROM '
		. $wpdb->prefix . HUT_Common::USER_TBL_NAME . ' AS u, ' . $wpdb->prefix . HUT_Common::USER_EVENT_TBL_NAME . ' AS u_event, '
		. $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME . ' AS u_env WHERE u_event.' . HUT_Common::USER_ENV_ID_COLUMN
		. ' = u_env.' . HUT_Common::ID_COLUMN . ' AND u.' . HUT_Common::ID_COLUMN . ' = u_event.' . HUT_Common::USER_ID_COLUMN
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::MOUSE_CLICK_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::AJAX_ACTION_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::TOUCHSCREEN_TAP_EVENT_TYPE . '"'
		. ' AND u_event.' . HUT_Common::EVENT_TYPE_COLUMN . ' != "' . HUT_Common::PAGE_VIEW_EVENT_TYPE . '"';
		$query = HUT_Query_Helper::apply_query_filters($query, $filters);
		
		$query .= ' GROUP BY ' . HUT_Common::EVENT_TYPE_COLUMN . ', day ORDER BY ' . HUT_Common::RECORD_DATE_COLUMN . ' DESC';
		
		$rows = $wpdb->get_results($query);
		
		$time_data = array();
		foreach ($rows as $row) {
			$day = $row->day;
			$count = $row->count;
			$event_type = $row->event_type;
			// TODO if a day has no data, then make it 0 visitors.
			// Otherwise, it is not plotted on the graph as 0.
		
			$data = array();
			if (isset($time_data[$event_type]))
				$data = $time_data[$event_type];
		
			array_push($data, array((strtotime($day) * 1000), $count));
			$time_data[$event_type] = $data;
		}
		
		return array('count_data' => $count_data, 'time_data' => $time_data);
	}
	
	public function clear_database() {
		$response = array('status' => 'OK', 'message' => 'Database cleared successfully');
		global $wpdb;
		try {
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::USER_EVENT_TBL_NAME.' WHERE 1' );
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::USER_ENV_TBL_NAME.' WHERE 1' );
			$rows = $wpdb->get_results( 'DELETE FROM '.$wpdb->prefix.HUT_Common::USER_TBL_NAME.' WHERE 1' );
			$success_message .= 'Database cleared successfully.';
		} catch ( Exception $e ) {
			$response = array('error' => 'OK', 'message' => 'An error has occured. ' . $e->getMessage());
		}
		return $response;
	}
	
	public function add_retrieve_user_environment_details($user_id, $create_if_empty, $browser, $os, $device, $current_time) {
		global $wpdb;
		$query = 'SELECT ' . HUT_Common::ID_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME . ' WHERE '
		. HUT_Common::USER_ID_COLUMN . ' = "' . $user_id . '"';
		$user_environment_id = $wpdb->get_col( $query, 0 );
			
		if ($user_environment_id == null && $create_if_empty) {
			$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::USER_ENV_TBL_NAME,
					array(
							HUT_Common::BROWSER_COLUMN => $browser,
							HUT_Common::OS_COLUMN => $os,
							HUT_Common::DEVICE_COLUMN => $device,
							HUT_Common::LAST_UPDT_DATE_COLUMN => $current_time,
							HUT_Common::USER_ID_COLUMN => $user_id
					)
			);
			$user_environment_id = $wpdb->insert_id;
		} else {
			$user_environment_id = $user_environment_id[0];
		}

		return $user_environment_id;
	}
	
	public function add_retrieve_user_details($ip_address, $session_id, $create_if_empty, $current_time, $user_role, $username) {
		global $wpdb;
		$query = 'SELECT ' . HUT_Common::ID_COLUMN . ' FROM ' . $wpdb->prefix . HUT_Common::USER_TBL_NAME . ' WHERE ' . HUT_Common::IP_ADDRESS_COLUMN
		. ' = "' . $ip_address . '" AND ' . HUT_Common::SESSION_ID_COLUMN . ' = "' . $session_id . '"';
		
		$user_id = '';
		
		// don't insert if ip_address and session_id have not been provided
		if ($ip_address && $session_id) {
			$user_id = $wpdb->get_col( $query, 0 );
			if ($user_id == null && $create_if_empty) {
				$rowsAffected = $wpdb->insert( $wpdb->prefix . HUT_Common::USER_TBL_NAME,
						array(
								HUT_Common::IP_ADDRESS_COLUMN => $ip_address,
								HUT_Common::LAST_UPDT_DATE_COLUMN => $current_time,
								HUT_Common::SESSION_ID_COLUMN => $session_id,
								HUT_Common::USER_ROLE_COLUMN => $user_role,
								HUT_Common::USERNAME_COLUMN => $username,
						)
				);
				$user_id = $wpdb->insert_id;
			} else {
				$user_id = $user_id[0];
			}
		}

		return $user_id;
	}
}

?>