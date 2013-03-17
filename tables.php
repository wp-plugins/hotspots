<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * A table for viewing information about heat maps including URL's widths, 
 * zoom levels and device pixel ratios.
 * 
 * @author dpowney
 *
 */
class Heat_Map_Table extends WP_List_Table {
	
	const 
	SINGULAR_LABEL 					= 'Heat Map',
	PLURAL_LABEL 					= 'Heat Maps';
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> Heat_Map_Table::SINGULAR_LABEL,
				'plural' => Heat_Map_Table::PLURAL_LABEL,
				'ajax'	=> false
		) );
	}
	
	/** (non-PHPdoc)
	* @see WP_List_Table::extra_tablenav()
	*/
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//echo '<br />';
		}
		if ( $which == "bottom" ){
			echo '<p class="description">Heat maps are best viewed using actual screen resolutions, real devices or emulators. Width is the inner width of the browser window,  excluding any vertical scrollbar if present and also including any remaining horizontal scroll. Zoom level is a browser setting and device pixel ratio is device dependant. IP addresses are stored to be able to detemermine the number of unique visitors.</p>';
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
				'count' => __('Count'),
				'heatMapData' => __('Heat Map Data'),
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
		$hidden = array('id');
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
	
		// get table data
		$query = 'SELECT '. HotSpots::URL_COLUMN . ', COUNT(*) AS count, uuid() AS id FROM '.$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME.' WHERE 1 GROUP BY '.HotSpots::URL_COLUMN . ' ORDER BY count DESC';
		
		// pagination
		$item_count = $wpdb->query($query); //return the total number of affected rows
		$items_per_page = 5;
		$page_num = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
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
	
	
	function highestCommonFactor($a,$b) {
	    if ($b==0) return $a;
	    return $this->highestCommonFactor($b, $a % $b);
	}
	
	/**
	 * Converts a decimal to a fraction that can be returned as a ratio
	 * 
	 * @param decimal i.e. 1.75
	 */
	function convert_decimalto_ratio($decimal) {
		
		$decimal = strval($decimal);

		$decimal_array = explode('.', $decimal);
		
		// if a whole number
		if (count($decimal_array) !== 2) {
			return $decimal . ':1';
		} else {
			$left_decimal_part = $decimal_array[0]; // 1
			$right_decimal_part = $decimal_array[1]; // 75
		
			$numerator = $left_decimal_part + $right_decimal_part; // 175
			$denominator = pow(10,strlen($right_decimal_part)); // 100
			$factor = $this->highestCommonFactor($numerator, $denominator); // 25
			$denominator /= $factor;
			$numerator /= $factor;
			
			return $numerator . ':' . $denominator;
		}
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
			case 'heatMapData':
			case 'action':
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
		echo stripslashes($item[HotSpots::URL_COLUMN]);
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
		$totalCount = $item['count'] . ' ';
		$url = $item['url'];
		global $wpdb;
		$query = 'SELECT * FROM '.$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME.' WHERE ' . HotSpots::URL_COLUMN . ' = "' . $url . '" AND ' . HotSpots::IS_TOUCH_COLUMN . ' = 1';
		$tapCount = $wpdb->query($query); //return the total number of affected rows
		echo $totalCount . ' (' . ($totalCount - $tapCount) . ' clicks & ' . $tapCount . ' taps)';
	}	
	
	/**
	 *
	 * @param unknown_type $item
	 */
	function column_action($item) {
		$id = $item['id'];
		echo '<input id="viewHeatMapBtn_' . $id .'" type="button" class="button viewHeatMapBtn" value="View Heat Map" />';
	}
	
	/**
	 * 
	 * @param unknown_type $item
	 */
	function column_heatMapData( $item ) {
		global $wpdb;
		$width_query = 'SELECT '. HotSpots::WIDTH_COLUMN . ', COUNT(*) AS count FROM '.$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" GROUP BY '.HotSpots::WIDTH_COLUMN . ' ORDER BY count DESC';
		$width_rows = $wpdb->get_results( $width_query );
		$url = $item[HotSpots::URL_COLUMN];
		$id = $item['id'];
		
		echo '<table class="widefat" cellspacing="0">';
		echo '<thead><tr><th class="manage-column column-width">Width</th><th class="manage-column column-users">Visitors</th><th class="manage-column column-browserDevice">Zoom Level & Device Pixel Ratio</th></tr></thead>';
		
		echo '<tbody>';
		$row_count = 0;
		foreach ($width_rows as $width_row) {
			
			// width column
			$width = $width_row->screenWidth;
			$width_count = $width_row->count;
			if ( ( $row_count++ % 2 ) == 0 )
				echo '<tr class="alternate">';
			else
				echo '<tr>';
			echo '<td class="column-width">'. $width . 'px (' . $width_count . ')</td>';
			
			// count of users column
			$users_query = 'SELECT COUNT(*) AS count FROM '.$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" AND screenWidth = "' . $width . '" GROUP BY '.HotSpots::IP_ADDRESS_COLUMN;
			$users_count = $wpdb->query($users_query);
			echo '<td class="column-users">' . $users_count . '</td>';
			
			// zoom level and device pixel ratio column
			echo '<td class="column-browserDevice">';
			$browser_device_query = 'SELECT '. HotSpots::ZOOM_LEVEL_COLUMN . ', ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ', COUNT(*) AS count FROM '.$wpdb->prefix.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" AND screenWidth = "' . $width . '" GROUP BY '.HotSpots::ZOOM_LEVEL_COLUMN . ', ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ' ORDER BY count DESC';
			$browser_device_rows = $wpdb->get_results($browser_device_query);
			foreach ($browser_device_rows as $browser_device_row) {
				$zoom_level = $browser_device_row->zoomLevel;
				$count = $browser_device_row->count;				
				$device_pixel_ratio = $this->convert_decimalto_ratio($browser_device_row->devicePixelRatio);
				echo ($zoom_level * 100). '% & ' . $device_pixel_ratio . ' (' . $count . ')<br />';
			}
			echo '</td>';
			
			echo '</tr>';
		}
		echo '</tbody></table>';
			

	}
}

/**
 * URL_Filter_Table class used for whitelist or blacklist filtering of URL's
 *
 * @author dpowney
 * @since 2.0
 */
class URL_Filter_Table extends WP_List_Table {

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
				'singular'=> URL_Filter_Table::SINGULAR_LABEL,
				'plural' => URL_Filter_Table::PLURAL_LABEL,
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
				URL_Filter_Table::CHECKBOX_COLUMN => '<input type="checkbox" />',
				URL_Filter_Table::URL_COLUMN =>__(URL_Filter_Table::URL_LABEL),
				URL_Filter_Table::ID_COLUMN => __('')
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
		$hidden = array(URL_Filter_Table::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.$wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME;
		
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
			case URL_Filter_Table::CHECKBOX_COLUMN :
			case URL_Filter_Table::ID_COLUMN :
			case URL_Filter_Table::URL_COLUMN :
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
		echo stripslashes( $item[URL_Filter_Table::URL_COLUMN] );
	}

	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="'.URL_Filter_Table::DELETE_CHECKBOX.'" value="%s" />', $item[URL_Filter_Table::ID_COLUMN]
		);
	}
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		$actions = array(
				URL_Filter_Table::DELETE_BULK_ACTION_NAME => URL_Filter_Table::DELETE_BULK_ACTION_LABEL
		);
		return $actions;
	}

	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		if ($this->current_action() === URL_Filter_Table::DELETE_BULK_ACTION_NAME) {
			global $wpdb;

			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );
			
			foreach($checked as $id) {
				$query = "DELETE FROM ". $wpdb->prefix.URL_Filter_Table::FILTER_TBL_NAME . " WHERE " .  URL_Filter_Table::ID_COLUMN . " = " . $id;
				$results = $wpdb->query( $query );
			}
		}
	}
}

