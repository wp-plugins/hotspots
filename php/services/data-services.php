<?php 

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'common.php';

/**
 * Interface for retrieving event data
 * 
 * @author dpowney
 *
 */
interface HUT_Data_Services {

	public function distinct_url_from_user_events();
	public function distinct_event_type_from_user_events();
	public function distinct_role_from_user();
	public function distinct_device_from_user_env();
	public function distinct_os_from_user_env();
	public function distinct_browser_from_user_env();
	public function distinct_page_width_from_user_events();
	public function heatmaps_table_data($filters, $items_per_page, $page_num);
	public function users_table_data($filters, $items_per_page, $page_num);
	public function user_activity_table_data($filters, $items_per_page, $page_num);
	public function user_activity_summary_data($filters);
	public function summary_report_data($filters, $items_per_page, $page_num);
	public function events_report_data($filters);
	public function custom_events_report_data($filters);
	public function clear_database();
	public function add_retrieve_user_environment_details($user_id, $create_if_empty, $browser, $os, $device, $current_time);
	public function add_retrieve_user_details($ip_address, $session_id, $create_if_empty, $current_time, $user_role, $username);
}

?>