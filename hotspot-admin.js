/**
 * Refresh database button
 */
jQuery("#refreshBtn").live('click',function(e) {
	e.preventDefault();
	jQuery.post(hotSpotData.ajaxUrl,  { action : "refreshDatabase", nonce : hotSpotData.ajaxNonce }, function(response) {
		var responseJSON = jQuery.parseJSON(response)
		jQuery('#hotSpotsMessages').empty();
		if (responseJSON.success === true) {
			jQuery('#hotSpotsMessages').append('<div class="updated"><p>Database refresh completed.</p></div>');
		} else {
			jQuery('#hotSpotsMessages').append('<div class="error"><p>' + responseJSON.errors + '</p></div>');
		}
	});	
});


jQuery("#saveChangesBtn").live('click',function(e) {
	e.preventDefault();
	
	var enabled = jQuery("#enabled").is(':checked');
	var showOnClick = jQuery("#showOnClick").is(':checked');
	var hotValue = jQuery("#hotValue").val();
	var spotOpacity = jQuery("#spotOpacity").val();
	var spotRadius = jQuery("#spotRadius").val();
	var isResponsive = jQuery("#isResponsive").is(':checked');
	var homePageOnly = jQuery("#homePageOnly").is(':checked');
	
	var data = {
		action : "saveChanges",
		nonce : hotSpotData.ajaxNonce,
		enabled : enabled,
		showOnClick : showOnClick,
		hotValue : hotValue,
		spotOpacity : spotOpacity,
		spotRadius : spotRadius,
		isResponsive : isResponsive,
		homePageOnly : homePageOnly
	};
	jQuery.post(hotSpotData.ajaxUrl,  data, function(response) {
		var responseJSON = jQuery.parseJSON(response)
		jQuery('#hotSpotsMessages').empty();
		if (responseJSON.success === true) {
			jQuery('#hotSpotsMessages').append('<div class="updated"><p>Changes saved successfully.</p></div>');
		} else {
			jQuery('#hotSpotsMessages').append('<div class="error">' + responseJSON.errors + '</div>');
		}
	});	
});