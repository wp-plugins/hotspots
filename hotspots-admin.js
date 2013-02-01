/**
 * Refresh database button
 */
jQuery("#refreshBtn").live('click',function(e) {
	e.preventDefault();
	jQuery.post(hotSpotsData.ajaxUrl,  { action : "refreshDatabase", nonce : hotSpotsData.ajaxNonce }, function(response) {
		var responseJSON = jQuery.parseJSON(response)
		jQuery('#hotSpotsMessages').empty();
		if (responseJSON.success === true) {
			jQuery('#hotSpotsMessages').append('<div class="updated"><p>Database refresh completed.</p></div>');
		} else {
			jQuery('#hotSpotsMessages').append('<div class="error"><p>' + responseJSON.errors + '</p></div>');
		}
	});	
});

// View different URL widths
jQuery("input.viewBtn").live('click', function(e) {
	e.preventDefault();
	var btnId = this.id;
	var parts = btnId.split("_"); 
	var rowId = parts[1];
	var width = jQuery("#width_" + rowId + " option:selected").val();
	var url = jQuery("#url_" + rowId).val();
	// Pass width in URL as Chrome does not care
	var queryParams = "drawHotSpots=true&width=" + width;
	if (url.indexOf("?") >= 0) {
		url += "&";
	} else {
		url += "?";
	}
	url += queryParams;
	window.open(url, "_blank", 'width=' + width + ', scrollbars=yes, resizable=yes, location=yes, toolbar=yes');
});


jQuery("#saveChangesBtn").live('click',function(e) {
	e.preventDefault();
	
	var drawHotSpotsEnabled = jQuery("#drawHotSpotsEnabled").is(':checked');
	var debug = jQuery("#debug").is(':checked');
	var saveMouseClicks = jQuery("#saveMouseClicks").is(':checked');
	var hotValue = jQuery("#hotValue").val();
	var spotOpacity = jQuery("#spotOpacity").val();
	var spotRadius = jQuery("#spotRadius").val();
	var isResponsive = jQuery("#isResponsive").is(':checked');
	var homePageOnly = jQuery("#homePageOnly").is(':checked');
	
	var data = {
		action : "saveChanges",
		nonce : hotSpotsData.ajaxNonce,
		drawHotSpotsEnabled : drawHotSpotsEnabled,
		saveMouseClicks : saveMouseClicks,
		debug : debug,
		hotValue : hotValue,
		spotOpacity : spotOpacity,
		spotRadius : spotRadius,
		isResponsive : isResponsive,
		homePageOnly : homePageOnly
	};
	jQuery.post(hotSpotsData.ajaxUrl,  data, function(response) {
		var responseJSON = jQuery.parseJSON(response)
		jQuery('#hotSpotsMessages').empty();
		if (responseJSON.success === true) {
			jQuery('#hotSpotsMessages').append('<div class="updated"><p>Changes saved successfully.</p></div>');
		} else {
			jQuery('#hotSpotsMessages').append('<div class="error">' + responseJSON.errors + '</div>');
		}
	});	
});

// If draw hotspots enabled option is not checked, then disable debug
jQuery("#drawHotSpotsEnabled").live('click', function(e) {
	if (jQuery("#drawHotSpotsEnabled").is(':checked')) {
		jQuery("#debug").removeAttr('disabled');
	} else {
		jQuery("#debug").attr('disabled','disabled');
	}
});
jQuery(document).ready(function() {	
	if (jQuery("#drawHotSpotsEnabled").is(':checked')) {
		jQuery("#debug").removeAttr('disabled');
	} else {
		jQuery("#debug").attr('disabled','disabled');
	}
});