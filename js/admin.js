// View different URL widths
jQuery(".view-heat-map-button").live('click', function(e) {
	e.preventDefault();
	var btnId = this.id; 
	
	// now we can lookup the url from a hidden input field given we have the rowId
	var url = jQuery("#" + btnId + "-url").val();
	
	var queryParams = "drawHeatMap=true";
	if (url.indexOf("?") >= 0) {
		url += "&";
	} else {
		url += "?";
	}
	url += queryParams;
	
	var devicePixelRatio = jQuery("#" + btnId + "-device_pixel_ratio").val();
	var zoomLevel = jQuery("#" + btnId + "-zoom_level").val();
	var width = jQuery("#" + btnId + "-width").val();
	
	// TODO
	//var clickTapId = jQuery("#" + btnId + "-click_tap_id").val();	
	// FIXME if (click_tap_id !== "")
	//	url += "&clickTapId=" + click_tap_id;
		
	if (width)
		url += "&width=" + width;
	if (devicePixelRatio)
		url += "&devicePixelRatio=" + devicePixelRatio;
	if (zoomLevel)
		url +="&zoomLevel=" + zoomLevel;
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

// Add date pickers
jQuery(document).ready(function() {	
	jQuery('.date-field').datepicker({
        dateFormat : 'yy-mm-dd'
    });
});