<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * A table of statistics
 * @author dpowney
 *
 */
class StatsTable extends WP_List_Table {
	
	const 
	SINGULAR_LABEL 					= "Stat",
	PLURAL_LABEL 					= 'Stats';
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> StatsTable::SINGULAR_LABEL,
				'plural' => StatsTable::PLURAL_LABEL,
				'ajax'	=> false
		) );
	}
	
	/** (non-PHPdoc)
	* @see WP_List_Table::extra_tablenav()
	*/
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			echo '<p>The table below lists the URL\'s, count of mouse click (in brackets) and available window sizes. Click view site to open a new window of the URL with the selected window width.</p>';
		}
		if ( $which == "bottom" ){
			echo '<p class="note">Note: Google Chrome browser has some issues setting the width '
			. 'of the window when opening a popup so it may not work.</p>';
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				'id' => __(''),
				HotSpots::URL_COLUMN =>__('URL'),
				'count' => __('Mouse Clicks Count'),
				'select' => __('Window Size Widths'),
				'action' => __('Action')
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
		$hidden = array('id', 'count');
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
	
		// get table data
		$query = 'SELECT '. HotSpots::URL_COLUMN . ', COUNT(*) AS count, uuid() AS id FROM '.HotSpots::TABLE_PREFIX.HotSpots::HOTSPOTS_TBL_NAME.' WHERE 1 GROUP BY '.HotSpots::URL_COLUMN;
		$this->items = $wpdb->get_results($query, ARRAY_A);
	}
	
	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case HotSpots::URL_COLUMN :
			case 'count':
			case 'action':
			case 'select':
			case 'id':
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
	function column_url($item){
		echo stripslashes($item[HotSpots::URL_COLUMN]) . ' (' . $item['count'] . ')';
		$id = $item['id'];
		$url = $item['url'];
		echo '<input type="hidden" id="url_' . $id . '" name="url_' . $id . '" value="' . $url  .'"></input>';
	}
	
	/**
	 * 
	 * @param unknown_type $item
	 */
	function column_count($item) {
		// get widths for url, and create a select
		echo $item['count'];
	}
	
	/**
	 * 
	 * @param unknown_type $item
	 */
	function column_select($item) {
		global $wpdb;
		$query = 'SELECT '. HotSpots::WIDTH_COLUMN . ', COUNT(*) AS count FROM '.HotSpots::TABLE_PREFIX.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" GROUP BY '.HotSpots::WIDTH_COLUMN;
		$rows = $wpdb->get_results($query);
		$url = $item[HotSpots::URL_COLUMN];
		$id = $item['id'];
		echo '<select id="width_' . $id . '" name="' . $id . '">';
		foreach ($rows as $row) {
			$width = $row->screenWidth;
			$count = $row->count;
			echo '<option value="' . $width . '">' . $width . 'px (' . $count . ')</option>';
		}
		echo '</select>';
	}
	
	/**
	 *
	 * @param unknown_type $item
	 */
	function column_action($item) {
		$id = $item['id'];
		echo '<input id="action_' . $id .'"type="button" class="button viewBtn" value="View site" />';
	}
}

/**
 * FilterTable class used for whitelist or blacklist filtering of URL's
 *
 * @author dpowney
 * @since 2.0
 */
class FilterTable extends WP_List_Table {

	const
	URL_COLUMN 						= 'url',
	ID_COLUMN 						= 'id',
	CHECKBOX_COLUMN 				= 'cb',
	SINGULAR_LABEL 					= "Filter URL",
	PLURAL_LABEL 					= 'Filter URL\'s',
	URL_LABEL 						= 'URL',
	ID_LABEL 						= "ID",
	DELETE_CHECKBOX 				= 'delete[]',
	FILTER_TBL_NAME 				= 'filter',
	DELETE_BULK_ACTION_NAME			= 'delete',
	DELETE_BULK_ACTION_LABEL		= 'Delete';


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> FilterTable::SINGULAR_LABEL,
				'plural' => FilterTable::PLURAL_LABEL,
				'ajax'	=> true
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			// do nothing
		}
		if ( $which == "bottom" ){
			// do nothing
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		return $columns= array(
				FilterTable::CHECKBOX_COLUMN => '<input type="checkbox" />',
				FilterTable::URL_COLUMN =>__(FilterTable::URL_LABEL),
				FilterTable::ID_COLUMN => __('')
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
		$hidden = array($this::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.HotSpots::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME;
		$this->items = $wpdb->get_results($query, ARRAY_A);
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case FilterTable::CHECKBOX_COLUMN :
			case FilterTable::ID_COLUMN :
			case FilterTable::URL_COLUMN :
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
	function column_url($item){
		echo stripslashes($item[FilterTable::URL_COLUMN]);
	}

	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb($item) {
		return sprintf(
				'<input type="checkbox" name="'.FilterTable::DELETE_CHECKBOX.'" value="%s" />', $item[FilterTable::ID_COLUMN]
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
				FilterTable::DELETE_BULK_ACTION_NAME => FilterTable::DELETE_BULK_ACTION_LABEL
		);
		return $actions;
	}

	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		$action = $this->current_action();

		if ($action === FilterTable::DELETE_BULK_ACTION_NAME) {
			global $wpdb;
				
			foreach($_GET['delete'] as $id) {
				$query = "DELETE FROM ". HotSpots::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME . " WHERE " .  FilterTable::ID_COLUMN . " = " . $id;

			}
		}
	}
}

