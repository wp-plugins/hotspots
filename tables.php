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
class HeatMapTable extends WP_List_Table {
	
	const 
	SINGULAR_LABEL 					= "Heat Map",
	PLURAL_LABEL 					= 'Heat Maps';
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( array(
				'singular'=> HeatMapTable::SINGULAR_LABEL,
				'plural' => HeatMapTable::PLURAL_LABEL,
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
			echo '<p class="description">Heat maps are best viewed using actual screen resolutions, real devices or emulators.</p>';
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
				'heatMapData' => __('Hest Map Data'),
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
		$query = 'SELECT '. HotSpots::URL_COLUMN . ', COUNT(*) AS count, uuid() AS id FROM '.HotSpots::TABLE_PREFIX.HotSpots::HOTSPOTS_TBL_NAME.' WHERE 1 GROUP BY '.HotSpots::URL_COLUMN . ' ORDER BY count DESC';
		
		// pagination
		$itemsCount = $wpdb->query($query); //return the total number of affected rows
		$itemsPerPage = 5;
		$pageNum = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if (empty($pageNum) || !is_numeric($pageNum) || $pageNum<=0 ) {
			$pageNum = 1;
		}
		$totalPages = ceil($itemsCount/$itemsPerPage);
		// adjust the query to take pagination into account
		if (!empty($pageNum) && !empty($itemsPerPage)) {
			$offset=($pageNum-1)*$itemsPerPage;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$itemsPerPage;
		}
		$this->set_pagination_args( array( "total_items" => $itemsCount, "total_pages" => $totalPages, "per_page" => $itemsPerPage ) );
		
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
	function convertDecimalToRatio($decimal) {
		
		$decimal = strval($decimal);

		$decimalArray = explode('.', $decimal);
		
		// if a whole number
		if (count($decimalArray) !== 2) {
			return $decimal . ':1';
		} else {
			$leftDecimalPart = $decimalArray[0]; // 1
			$rightDecimalPart = $decimalArray[1]; // 75
		
			$numerator = $leftDecimalPart + $rightDecimalPart; // 175
			$denominator = pow(10,strlen($rightDecimalPart)); // 100
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
		echo $item['count'];
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
	function column_heatMapData($item) {
		global $wpdb;
		$widthQuery = 'SELECT '. HotSpots::WIDTH_COLUMN . ', COUNT(*) AS count FROM '.HotSpots::TABLE_PREFIX.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" GROUP BY '.HotSpots::WIDTH_COLUMN . ' ORDER BY count DESC';
		$widthRows = $wpdb->get_results($widthQuery);
		$url = $item[HotSpots::URL_COLUMN];
		$id = $item['id'];
		
		
		echo '<table class="widefat" cellspacing="0">';
		
		echo '<thead><tr><th class="manage-column column-width">Window Width</th><th class="manage-column column-zoomLevels">Zoom Level & Device Pixel Ratio</th></tr></thead>';
		echo '<tbody>';
		$rowCount = 0;
		foreach ($widthRows as $widthRow) {
			$width = $widthRow->screenWidth;
			$widthCount = $widthRow->count;
			
			if (($rowCount++ % 2) == 0) {
				echo '<tr class="alternate">';
			} else {
				echo '<tr>';
			}
					
			echo '<td class="column-width">'. $width . 'px (' . $widthCount . ')</td>';
			
			echo '<td class="column-zoomLevels">';
			$zoomLevelsRatio = 'SELECT '. HotSpots::ZOOM_LEVEL_COLUMN . ', ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ', COUNT(*) AS count FROM '.HotSpots::TABLE_PREFIX.HotSpots::HOTSPOTS_TBL_NAME.' WHERE url = "'. $item[HotSpots::URL_COLUMN] . '" AND screenWidth = "' . $width . '" GROUP BY '.HotSpots::ZOOM_LEVEL_COLUMN . ', ' . HotSpots::DEVICE_PIXEL_RATIO_COLUMN . ' ORDER BY count DESC';
			$zoomLevelRows = $wpdb->get_results($zoomLevelsRatio);
			foreach ($zoomLevelRows as $zoomLevelRow) {
				$zoomLevel = $zoomLevelRow->zoomLevel;
				$count = $zoomLevelRow->count;				
				$devPixRatio = $this->convertDecimalToRatio($zoomLevelRow->devicePixelRatio);
				
				echo ($zoomLevel * 100). '% & ' . $devPixRatio . ' (' . $count . ')<br />';
			}
			echo '</td>';
			
			echo '</tr>';
		}
		echo '</tbody></table>';

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
		
		// Process any bulk actions first
		$this->process_bulk_action();

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array(FilterTable::ID_COLUMN );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// get table data
		$query = 'SELECT * FROM '.HotSpots::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME;
		
		// pagination
		$itemsCount = $wpdb->query($query); //return the total number of affected rows
		$itemsPerPage = 10;
		$pageNum = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		if (empty($pageNum) || !is_numeric($pageNum) || $pageNum<=0 ) {
			$pageNum = 1;
		}
		$totalPages = ceil($itemsCount/$itemsPerPage);
		// adjust the query to take pagination into account
		if (!empty($pageNum) && !empty($itemsPerPage)) {
			$offset=($pageNum-1)*$itemsPerPage;
			$query .= ' LIMIT ' .(int)$offset. ',' .(int)$itemsPerPage;
		}
		$this->set_pagination_args( array( "total_items" => $itemsCount, "total_pages" => $totalPages, "per_page" => $itemsPerPage ) );
		
		
		
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
		if ($this->current_action() === FilterTable::DELETE_BULK_ACTION_NAME) {
			global $wpdb;

			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );
			
			foreach($checked as $id) {
				$query = "DELETE FROM ". HotSpots::TABLE_PREFIX.FilterTable::FILTER_TBL_NAME . " WHERE " .  FilterTable::ID_COLUMN . " = " . $id;
				$results = $wpdb->query($query);
			}
		}
	}
}

