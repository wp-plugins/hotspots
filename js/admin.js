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
	
	var devicePixelRatio = jQuery("#" + btnId + "-device_pixel_ratio");
	var zoomLevel = jQuery("#" + btnId + "-zoom_level");
	var width = jQuery("#" + btnId + "-width");
	
	var os_family = jQuery("#" + btnId + "-os_family");
	var browserFamily = jQuery("#" + btnId + "-browser_family");
	var device = jQuery("#" + btnId + "-device");
	
	var clickTapId = jQuery("#" + btnId + "-click_tap_id");	
	if (clickTapId && clickTapId.val())
		url += "&clickTapId=" + clickTapId.val();
		
	if (width && width.val())
		url += "&width=" + width.val();
	if (devicePixelRatio && devicePixelRatio.val())
		url += "&devicePixelRatio=" + devicePixelRatio.val();
	if (zoomLevel && zoomLevel.val())
		url +="&zoomLevel=" + zoomLevel.val();
	if (os_family && os_family.val())
		url += "&osFamily=" + os_family.val();
	if (browserFamily && browserFamily.val())
		url += "&browserFamily=" + browserFamily.val();
	if (device && device.val())
		url += "&device=" + device.val();
	
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

function onchangeUAParser() {
	if (jQuery("#show_uaparser").is(":checked")) {
		jQuery("#browser_family").prop('disabled', false);
		jQuery("#os_family").prop('disabled', false);
		jQuery("#device").prop('disabled', false);
	} else {
		jQuery("#browser_family").prop('disabled', true);
		jQuery("#os_family").prop('disabled', true);
		jQuery("#device").prop('disabled', true);
	}
}

jQuery(document).ready(function() {	
	// Add date pickers
	jQuery('.date-field').datepicker({
        dateFormat : 'yy-mm-dd'
    });
	
	// On change show UA parser
	jQuery("#show_uaparser").live('click', function(e) {
		onchangeUAParser();
	});
	
	onchangeUAParser();
	
});