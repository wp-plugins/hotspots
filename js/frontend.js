// constants
var MAX_COLOUR = 255;
var MIN_COLOUR = 0;
var debug = false; // default is false
var saveClickOrTapEnabled = false; // default is false
var spotRadius = 6;
var hot = 20; // default is 20
var warm = hot / 2; // default is 10
var opacity = 0.2; // default is 0.2
var drawHeatMapEnabled = false; // default is false
var zIndex = 1000;
var role = null;


/**
 * After page load setup plugin functions
 * 
 */
jQuery(window).load(function() {
	
	// initialise the plugin options
	initOptions();

	jQuery("<div id='loadingDialog' title=\"Loading...\">" +
			"<p>Loading heat map...</p>" +
			"</div>").appendTo("body");
	jQuery("#loadingDialog").dialog( { autoOpen: false });
	
	// setup and draw hot spots if option is enabled
	if (drawHeatMapEnabled) {		
		setupDrawing();
	}

	// setup saving mouse clicks and touch screen taps if option is enabled
	if (saveClickOrTapEnabled) {
		setupSaving();
	}
});


/**
 * Initialises the plugin options
 * 
 */
function initOptions() {

	// set all options
	drawHeatMapEnabled = (configData.drawHeatMapEnabled) == "1" ? true
			: false;
	debug = (configData.debug) == "1" ? true : false;
	
	var urlDBLimitReached = (configData.urlDBLimitReached) == "1" ? true : false;
	var urlExcluded = (configData.urlExcluded) == "1" ? true : false;
	var scheduleCheck = (configData.scheduleCheck) == "1" ? true : false;
	saveClickOrTapEnabled = (configData.saveClickOrTapEnabled) == "1" ? true : false;
	if (urlDBLimitReached == true || scheduleCheck == false || urlExcluded == true)
		saveClickOrTapEnabled = false;
	
	spotRadius = parseInt(configData.spotRadius);
	hot = parseInt(configData.hotValue);
	warm = hot / 2;
	opacity = configData.spotOpacity;

	// Check for drawHeatMap query param
	var drawHeatMapQueryParam = urlHelper.getUrlParamByName(
			window.location.href, 'drawHeatMap') === "true" ? true : false;
	if (drawHeatMapQueryParam == false) {
		// cannot enable drawing heat map without the query param set to true
		drawHeatMapEnabled = false;
	}
	role = configData.role;

}


/**
 * Sets up saving mouse clicks and touch screen taps
 * 
 */
function setupSaving() {

	if (!("ontouchstart" in window)) { // mouse clicks
		jQuery(document).live('click', function(e) {
			var event = e ? e : window.event;
			var posX = 0;
			var posY = 0;

			if ((event.clientX || event.clientY) && document.body
					&& document.body.scrollLeft != null) {
				posX = event.clientX + document.body.scrollLeft;
				posY = event.clientY + document.body.scrollTop;
			}
			if ((event.clientX || event.clientY) && document.compatMode == 'CSS1Compat'
					&& document.documentElement
					&& document.documentElement.scrollLeft != null) {
				posX = event.clientX + document.documentElement.scrollLeft;
				posY = event.clientY + document.documentElement.scrollTop;
			}
			if (event.pageX || event.pageY) {
				posX = event.pageX;
				posY = event.pageY;
			}
			
			saveClickOrTap(posX, posY, false);
		});
	} else { // touch screens, we only care about taps

		// based on http://www.gianlucaguarini.com/blog/detecting-the-tap-event-on-a-mobile-touch-device-using-javascript/
		var touchData = {
			started : null, // detect if a touch event is sarted
			currrentX : 0,
			currentY : 0,
			previousX : 0,
			previousY : 0,
			touch : null
		};

		jQuery(document).on("touchstart", function(e) {
			touchData.started = new Date().getTime();
			var touch = e.originalEvent.touches[0];
			touchData.previousX = touch.pageX;
			touchData.previousY = touch.pageY;
			touchData.touch = touch;
		});

		jQuery(document).on(
				"touchend touchcancel",
				function(e) {
					var now = new Date().getTime();

					// Detecting if after 200ms if in the same position.
					// FIXME taps are not always recorded if the browser 
					// takes over before the AJAX call is made. So this 
					// means we will get some, and lose some.
					if ((touchData.started !== null)
							&& ((now - touchData.started) < 200)
							&& (touchData.touch !== null)) {
						var touch = touchData.touch;
						var currentX = touch.pageX;
						var currentY = touch.pageY;
						if ((touchData.previousX === currentX)
								&& (touchData.previousY === currentY)) {
							saveClickOrTap(currentX, currentY, true);
						}
					}
					touchData.started = null;
					touchData.touch = null;
				});
	}
}


/**
 * Adds a small information panel to the bottom right corner with width, 
 * height, zoom level and device pixel ratio
 */
function setupInfoPanel() {
	
	jQuery("<div id='infoPanel'>" +
			"Width: <div id='infoWidth' />px, " +
			"Zoom Level: <div id='infoZoomLevel' />, " +
			"Device Pixel Ratio: <div id='infoDevPixRat' />" +
			"</div>").appendTo("body");

	// Add 1 to zIndex so it shows on top of the canvas
	jQuery("#infoPanel").css("z-index", zIndex + 1);
	jQuery("#infoPanel *").css("z-index", zIndex + 1);
	
	refreshInfoPanel();
	
	// get query params
	var url = window.location.href;
	var widthQueryParam = urlHelper.getUrlParamByName(url, "width");
	var devicePixelRatioQueryParam = urlHelper.getUrlParamByName(url, "devicePixelRatio");
	var zoomLevelQueryParam = urlHelper.getUrlParamByName(url, "zoomLevel");
	// current data
	var width = getWidth();
	var zoomLevel = detectZoom.zoom();
	var devicePixelRatio =  detectZoom.device();
	
	var message = "";
	if (widthQueryParam !== undefined && widthQueryParam !== "" && width != widthQueryParam) {
		message += "<p style='color: Orange;'>Resize browser window width to " + widthQueryParam + ".</p>";
	}
	if (devicePixelRatioQueryParam != undefined && devicePixelRatioQueryParam !== "" && devicePixelRatio != devicePixelRatioQueryParam) {
		message += "<p style='color: Orange;'>Modify device pixel ratio to " + devicePixelRatioQueryParam + ".</p>";
	}
	if (zoomLevelQueryParam != undefined && zoomLevelQueryParam !== "" && zoomLevel != zoomLevelQueryParam) {
		message += "<p style='color: Orange;'>Modify browser zoom level to " + zoomLevelQueryParam + ".</p>";
	}
	if (message.length > 0)
		jQuery(message).appendTo("#infoPanel");
	
	// Update information on window resize
	jQuery(window).resize(function() {
		refreshInfoPanel();
		
	});
}


/**
 * Refreshes the information panel with current width, zoom level and device
 * pixel ration data
 */
function refreshInfoPanel() {
	var width = getWidth();
	
	jQuery("#infoWidth").html(width);		
	var zoomLevel = detectZoom.zoom();
	var devicePixelRatio = detectZoom.device();
	jQuery("#infoZoomLevel").html(zoomLevel * 100 + "%");
	jQuery("#infoDevPixRat").html(convertDecimalToRatio(devicePixelRatio));
	
}


/**
 * Setup and draw hot spots if option is enabled
 * 
 */
function setupDrawing() {

	// Remove the WordPress admin bar and margin style
	jQuery('#wpadminbar').remove();
	var css = 'html { margin-top: 0px !important; } * html body { margin-top: 0px !important; }';
	var head = document.head || document.getElementsByTagName('head')[0];
	style = document.createElement('style');
	style.type = 'text/css';
	if (style.styleSheet){
		style.styleSheet.cssText = css;
	} else {
		style.appendChild(document.createTextNode(css));
	}
	head.appendChild(style);

	// Overlay canvas
	initCanvas();
	
	// redraw heat map if window is resized
	jQuery(window).resize(function() {
		// TODO: don't do anything until a small delay

		// remove canvas element and create it again to refresh
		jQuery("#canvasContainer").remove();
		jQuery("#infoPanel").remove();
		setupInfoPanel();
		
		initCanvas();

		// redraw the heat map
		drawHeatMap();
	});
	
	setupInfoPanel();
	
	// Now draw the hot spots
	drawHeatMap();
}


/**
 * Adds mouse click or touch screen tap coordinates to the server
 * 
 */
function saveClickOrTap(posX, posY, isTap) {

	// remove hash tags from URL
	var url = window.location.href;
	var hashIndex = url.indexOf('#');
	if (hashIndex > 0) {
		url = url.substring(0, hashIndex);
	}

	var width = getWidth();
	
	if (jQuery('#wpadminbar').length > 0) {
		posY -= jQuery('#wpadminbar').height();
	}
	
	var data = {
		action : "save_click_or_tap",
		nonce : configData.ajaxNonce,
		x : posX,
		y : posY,
		url : url,
		width : width,
		isTap : isTap,
		zoomLevel : detectZoom.zoom(),
		devicePixelRatio : detectZoom.device(),
		role : role
	};

	jQuery.post(configData.ajaxUrl, data, function(response) {
		var jsonResponse = jQuery.parseJSON(response);
		if (drawHeatMapEnabled === true && debug === true) {
			var heatValue = jsonResponse.heatValue;
			drawClickOrTap(posX, posY, heatValue);
		}
	});
}


/**
 * Draws heat map containing all mouse clicks and touch screen 
 * taps given a URL, width, zoom level and device pixel ratio.
 * 
 */
function drawHeatMap() {	
	jQuery("#loadingDialog").dialog('open');

	// remove hash tags from URL
	var url = window.location.href;
	var hashIndex = url.indexOf('#');
	if (hashIndex > 0) {
		url = url.substring(0, hashIndex);
	}
	
	var width = getWidth();
	
	var clickTapId = urlHelper.getUrlParamByName(url, "clickTapId");
	
	var data = {
		action : "retrieve_clicks_and_taps",
		nonce : configData.ajaxNonce,
		url : url,
		width : width,
		zoomLevel : detectZoom.zoom(),
		devicePixelRatio : detectZoom.device(),
		clickTapId : (clickTapId !== undefined && clickTapId !== "") ? clickTapId : null
	};
	jQuery.post(configData.ajaxUrl, data, function(response) {
		var jsonResponse = jQuery.parseJSON(response);

		for ( var index in jsonResponse) {
			var clickOrTapData = jsonResponse[index];
			// draw the mouse click or touch screen tap on the canvas
			drawClickOrTap(clickOrTapData.x, clickOrTapData.y, 
					clickOrTapData.heatValue);
		}
		
		jQuery("#loadingDialog").dialog('close');
	});
}


/**
 * Draws a mouse click or touch screen tap on the canvas
 * 
 * @param posX
 * @param posY
 * @param heatValue
 * @returns
 */
function drawClickOrTap(posX, posY, heatValue) {
	var canvas = jQuery("#canvas").get(0);
	var context = canvas.getContext("2d");
	context.beginPath();
	context.arc(posX, posY, spotRadius, 0, 2 * Math.PI);

	/* 
	 * Calculates RGB colour for corresponding heat value. From Green to Red, 
	 * therefore Blue is always 0.
	 * Green is cold, Orange is warm and Red is hot
	 * Green is 0, 255, 0 and Red is 255, 0, 0. In between is 255, 255, 0
	 */
	var fillStyle = null;
	if (heatValue === 0) { // green
		fillStyle = "rgba(" + MIN_COLOUR + ", " + MAX_COLOUR + ", "
				+ MIN_COLOUR + ", " + opacity + ")";
	} else if (heatValue === warm) { // orange
		fillStyle = "rgba(" + MAX_COLOUR + ", " + MAX_COLOUR + ", "
				+ MIN_COLOUR + ", " + opacity + ")";
	} else if (heatValue >= hot) { // red
		fillStyle = "rgba(" + MAX_COLOUR + ", " + MIN_COLOUR + ", "
				+ MIN_COLOUR + ", " + opacity + ")";
	} else { // in between
		if (heatValue > warm) { // more red
			var someGreen = MAX_COLOUR
					- (MAX_COLOUR * ((heatValue - warm) / warm));
			fillStyle = "rgba(" + MAX_COLOUR + ", " + Math.round(someGreen)
					+ ", " + MIN_COLOUR + ", " + opacity + ")";
		} else { // more green
			var someRed = MAX_COLOUR * (heatValue / warm);
			fillStyle = "rgba(" + Math.round(someRed) + ", " + MAX_COLOUR
					+ ", " + MIN_COLOUR + ", " + opacity + ")";
		}
	}

	context.fillStyle = fillStyle;
	context.fill();
}


/**
 * Creates and initialises the canvas
 * 
 */
function initCanvas() {

	var docWidth = jQuery(document).width();
	var docHeight = jQuery(document).height();
	
	// Create a blank div where we are going to put the canvas into.
	var canvasContainer = document.createElement('div');
	document.body.appendChild(canvasContainer);
	canvasContainer.setAttribute("id", "canvasContainer");
	canvasContainer.style.position = "absolute";
	canvasContainer.style.left = "0px";
	canvasContainer.style.top = "0px";
	canvasContainer.style.width = "100%";
	canvasContainer.style.height = "100%";
	canvasContainer.style.zIndex = zIndex;

	// create the canvas
	var canvas = document.createElement("canvas");
	canvas.setAttribute("id", "canvas");
	canvas.style.width = docWidth;
	canvas.style.height = docHeight;
	canvas.width = docWidth;
	canvas.height = docHeight;
	canvas.style.overflow = 'visible';
	canvas.style.position = 'absolute';
	
	canvasContainer.appendChild(canvas);
	
	// set opacity for all elements so that hot spots are visible
	jQuery("body *").each(function() {
		// if current element already already has opacity < 1, leave as is
		var opacity = jQuery(this).css("opacity");
		if (opacity !== undefined && opacity === 1) {
			jQuery(this).css({
				opacity : 0.99
			});
		}
		// check z-index to ensure heat map is overlayed on top of any element
		var tempZIndex = jQuery(this).css("z-index");
		if (tempZIndex > zIndex) {
			zIndex = tempZIndex + 1;
			var canvasContainer = jQuery("#canvasContainer");
			canvasContainer.css("z-index", zIndex);
		}
	});
}


/**
 * Returns the inner width of the window, then subtracts vertical 
 * scrollbar width and adds any remaining horizontal scroll.
 * 
 * @returns width of web page
 */
function getWidth() {
	var width = 0;
	//if ("ontouchstart" in window) { // Mobiles
	//	// FIXME iOS does not flip dimensions when orientation is changed
	if (window.innerWidth) {
		if (typeof window.chrome === "object") { // hack for Chrome browser
			width = self.outerWidth;
		} else {
			width = window.innerWidth;
		}
	} else if (document.documentElement
			&& document.documentElement.clientWidth != 0) {
		width = document.documentElement.clientWidth;
	} else if (document.body) {
		width = document.body.clientWidth;
	}
	
	// Exclude vertical scrollbar width and add any remaining horizontal scroll
	if (width > 0) {
		width += getRemainingScrollWidth();
		// do not add vertical scrollbar width for Firefox??????/
		if (hasVerticalScrollbar()) { // && !jQuery.browser.mozilla) {
			width -= getVerticalScrollbarWidth();
		}
	}
	return width;
}


/**
 * Returns the remaining horizontal scroll width available. It does not include
 * the actual scrollbar.
 * 
 * @returns remaining scrolling width
 */
function getRemainingScrollWidth() {
	if ('scrollMaxX' in window) { // only supported by Firefox
		return window.scrollMaxX;
	} else {
		return (document.documentElement.scrollWidth - document.documentElement.clientWidth);
	}
}


/**
 * Calculates the vertical scrollbar width
 * 
 * @returns scrollbar width
 */
function getVerticalScrollbarWidth() {

	var scrollDiv = document.createElement("div");
	scrollDiv.className = "scrollbar-measure";
	scrollDiv.style.width = "100px";
	scrollDiv.style.height = "100px";
	scrollDiv.style.overflow = "scroll";
	scrollDiv.style.position = "absolute";
	scrollDiv.style.top = "-9999px";
	document.body.appendChild(scrollDiv);

	// Get the scrollbar width
	var scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth;

	// Delete the DIV 
	document.body.removeChild(scrollDiv);
	
	return scrollbarWidth;
}


/**
 * Checks if a vertical scrollbar exists
 * 
 * @returns true if a vertical scrollbar exists, otherwise, false
 */
function hasVerticalScrollbar() {
	// Check if body height is higher than window height
	if (jQuery(document).height() > jQuery(window).height()) { 
		return true;
	}
	return false;
}

/**
 * Calculates the highest common factor between two numbers
 * 
 * @param a
 * @param b
 * @returns
 */
function highestCommonFactor(a,b) {
    if (b==0) return a;
    return highestCommonFactor(b,a%b);
}


/**
 * Converts a decimal to a fraction that can be returned as a ratio
 * 
 * @param decimal i.e. 1.75
 */
function convertDecimalToRatio(decimal) {
	if (typeof decimal === "number") {
		decimal = decimal.toString();
	}
	
	var decimalArray = decimal.split(".");
	// if a whole number
	if (decimalArray.length !== 2) {
		return decimal + ":1";
	}
	
	var leftDecimalPart = decimalArray[0]; // 1
	var rightDecimalPart = decimalArray[1]; // 75

	var numerator = leftDecimalPart + rightDecimalPart; // 175
	var denominator = Math.pow(10,rightDecimalPart.length); // 100
	var factor = highestCommonFactor(numerator, denominator); // 25
	denominator /= factor;
	numerator /= factor;
	
	return numerator + ":" + denominator;

}

/**
 * Helper class to get the query string parameters from the URL
 */
var urlHelper = new function() {

	/**
	 * Retrieves an array of URL query string parameters in order
	 * @param url
	 * @returns params
	 */
	this.getUrlParams = function(url) {
		
		// ignore hash # in URL when retrieving params
	    var hashIndex = url.indexOf('#');
	    if (hashIndex > 0) {
	    	url = url.substring(0, hashIndex);
	    }
		
		var params = [], hash;
		if (url.indexOf("?") !== -1) {
			var hashes = url.slice(url.indexOf('?') + 1).split('&');
			for ( var i = 0; i < hashes.length; i++) {
				hash = hashes[i].split('=');
				params.push(hash[0]);
				params[hash[0]] = hash[1];
			}
		}
		return params;
	};

	
	/**
	 * Gets a URL query string parameter by name
	 * 
	 * @param url
	 * @param name
	 * @returns
	 */
	this.getUrlParamByName = function(url, name) {
		return this.getUrlParams(url)[name];
	};
	
};