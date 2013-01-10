/**
 * Refresh the DB table
 */
jQuery("#refreshBtn").live('click',function(e) {
	e.preventDefault();
	jQuery.post(hotSpotData.ajaxUrl,  { action : "refresh_hotSpots_db_table", nonce : hotSpotData.ajaxNonce }, function(response) {
		// do nothing
	});	
});