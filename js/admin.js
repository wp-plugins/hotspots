// View different URL widths
jQuery(".view-heat-map-button").live('click', function(e) {
	e.preventDefault();
	var btnId = this.id; 
	
	// now we can lookup the url from a hidden input field given we have the rowId
	var url = jQuery("#url_" + btnId).val();
	
	var queryParams = "drawHeatMap=true";
	if (url.indexOf("?") >= 0) {
		url += "&";
	} else {
		url += "?";
	}
	url += queryParams;
	
	var data = jQuery("#data_" + btnId).val();
	if (data !== undefined) {
		var parts = data.split("_"); 
		var click_tap_id = parts[0];
		var width = parts[1];
		var devicePixelRatio = parts[2];
		var zoomLevel = parts[3];
		if (click_tap_id !== "")
			url += "&clickTapId=" + click_tap_id;
		if (width !== "")
			url += "&width=" + width;
		if (devicePixelRatio !== "")
			url += "&devicePixelRatio=" + devicePixelRatio;
		if (zoomLevel !== "")
			url +="&zoomLevel=" + zoomLevel;
	}
	
	
	window.open(url, "_blank", 'scrollbars=yes, resizable=yes, location=yes, toolbar=yes');
});

// Clear database button submit
jQuery("#clear-database").live('click',function(e) {
	jQuery("#clear-db-flag").val("true");
});

// Add URL filter submit
jQuery("#add-URL-filter").live('click', function(e) {
	jQuery("#add-URL-filter-flag").val("true");
});