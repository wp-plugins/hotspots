// View different URL widths
jQuery("input.viewHeatMapBtn").live('click', function(e) {
	e.preventDefault();
	var btnId = this.id; 
	// button id is in the format viewHeatMapBtn_<rowId>
	var parts = btnId.split("_"); 
	var rowId = parts[1];
	// now we can lookup the url from a hidden input field given we have the rowId
	var url = jQuery("#url_" + rowId).val();
	var queryParams = "drawHeatMap=true";
	if (url.indexOf("?") >= 0) {
		url += "&";
	} else {
		url += "?";
	}
	url += queryParams;
	window.open(url, "_blank", 'scrollbars=yes, resizable=yes, location=yes, toolbar=yes');
});


jQuery("#saveChangesBtn").live('click',function(e) {
	e.preventDefault();
	
	var drawHotSpotsEnabled = jQuery("#drawHotSpotsEnabled").is(':checked');
	var debug = jQuery("#debug").is(':checked');
	var saveMouseClicks = jQuery("#saveMouseClicks").is(':checked');
	var hotValue = jQuery("#hotValue").val();
	var spotOpacity = jQuery("#spotOpacity").val();
	var spotRadius = jQuery("#spotRadius").val();
	var applyFilters = jQuery("#applyFilters").is(':checked');
	
	var data = {
		action : "saveChanges",
		nonce : hotSpotsData.ajaxNonce,
		drawHotSpotsEnabled : drawHotSpotsEnabled,
		saveMouseClicks : saveMouseClicks,
		debug : debug,
		hotValue : hotValue,
		spotOpacity : spotOpacity,
		spotRadius : spotRadius,
		applyFilters : applyFilters
	};
	jQuery.post(hotSpotsData.ajaxUrl,  data, function(response) {
		var responseJSON = jQuery.parseJSON(response);
		jQuery('#messages').empty();
		if (responseJSON.success === true) {
			jQuery('#messages').append('<div class="updated"><p>Changes saved successfully.</p></div>');
		} else {
			jQuery('#messages').append('<div class="error">' + responseJSON.errors + '</div>');
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

// Set filter type option
jQuery("input[name=filterType]:radio").live('click', function(e) {
	var value = jQuery("input[name=filterType]:radio:checked").val();
	var data =  { action : "setFilterType", nonce : hotSpotsData.ajaxNonce, filterType : value };
	jQuery.post(hotSpotsData.ajaxUrl,  data, function(response) {
		var responseJSON = jQuery.parseJSON(response);
		if (responseJSON.success === false) {
			jQuery('#messages').empty();
			jQuery('#messages').append('<div class="error">' + responseJSON.errors + '</div>');
		}
	});
});

// Add URL filter submit
jQuery("#addFilterBtn").live('click', function(e) {
	jQuery("#addFilterSubmit").val("true");
	jQuery("#clearDatabaseSubmit").val("false");
});
//Clear database button submit
jQuery("#refreshBtn").live('click',function(e) {
	jQuery("#clearDatabaseSubmit").val("true");
	jQuery("#addFilterSubmit").val("false");
});