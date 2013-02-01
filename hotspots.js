// constants
var MAX_COLOUR = 255;
var MIN_COLOUR = 0;
var debug = false; // default is false
var saveMouseClicks = false; // default is false
var spotRadius = 6;
var hot = 20; // default is 20
var warm = hot / 2; // default is 10
var opacity = 0.2; // default is 0.2
var drawHotSpotsEnabled = false; // default is false
var isResponsive = false; // default is false

jQuery(window).load(function() {	
	// get options
	drawHotSpotsEnabled = (hotSpotsData.drawHotSpotsEnabled) == "1" ? true : false;
	debug = (hotSpotsData.debug) == "1" ? true : false;
	saveMouseClicks = (hotSpotsData.saveMouseClicks) == "1" ? true : false;
	spotRadius = parseInt(hotSpotsData.spotRadius);
	hot = parseInt(hotSpotsData.hotValue);
	warm = hot / 2;
	opacity = hotSpotsData.spotOpacity;
	isResponsive = (hotSpotsData.isResponsive) == "1" ? true : false;
	
	// Check for drawHotSpots query param
	var drawHotSpotsQueryParam = urlHelper.getUrlParamByName(window.location.href,
			'drawHotSpots') === "true" ? true : false;
	if (drawHotSpotsQueryParam == false) {
		// cannot nabled drawing hotspots without the query param set to true
		drawHotSpotsEnabled = false;
	}
	
	// For draw hotspots enabled option
	if (drawHotSpotsEnabled) {
		
		// If from settings page view site, resize to selected width
		var innerWidth = urlHelper.getUrlParamByName(window.location.href, 'width');
		if (innerWidth && windowReady == false) {
			resizeToInner(innerWidth, window.innerHeight);
		}
		
		initCanvas();
				
		// Get mouse clicks ands draw them
		var inner = getInnerSize();
		var data =  { action : "get_mouse_clicks", nonce : hotSpotsData.ajaxNonce, url : window.location.href, width : inner[0] };
		jQuery.post(hotSpotsData.ajaxUrl, data, function(response) {
			drawAllMouseClicks(jQuery.parseJSON(response));
		});
			
		// redraw canvas if window is resized
		if (isResponsive) {
			jQuery(window).resize(function() {
				// TODO: don't do anything until a small delay
					
				// remove canvas element and create it again to refresh
				jQuery("#canvasContainer").remove();
				initCanvas();
				
				// Get mouse clicks and draw them
				var inner = getInnerSize();
				var data =  { action : "get_mouse_clicks", nonce : hotSpotsData.ajaxNonce, url : window.location.href, width : inner[0] };
				jQuery.post(hotSpotsData.ajaxUrl, data, function(response) {
					drawAllMouseClicks(jQuery.parseJSON(response));
				});
			});
		}
	}
	
	// For save mouse clicks option
	if (saveMouseClicks) {
		jQuery(document).live('click',function(e) {
			addMouseClick(e);
		});
	}
});


// http://www.hypergeneric.com/corpus/javascript-inner-viewport-resize/
/**
 * getInnerSize
 */
function getInnerSize() {
	var width = null;
	var height = null;
	if (self.innerHeight) { // all except Explorer
		// hack for Google Chrome innerWidth/outerWidth
		if (typeof window.chrome === "object") {
			width = self.outerWidth;
			height = self.outerHeight;
		} else {
			width = self.innerWidth;
			height = self.innerHeight;
		}
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		width = document.documentElement.clientWidth;
		height = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		width = document.body.clientWidth;
		height = document.body.clientHeight;
	}
	return [width, height];
}
/**
 * resizeToInner
 * 
 * @param width
 * @param height
 * @param screenX
 * @param screenY
 */
function resizeToInner(width, height, screenX, screenY) {
	// make sure we have a final x/y value
	// pick one or the other windows value, not both
	if (screenX==undefined) {
		screenX = window.screenLeft || window.screenX;
	}
	if (screenY==undefined) {
		screenY = window.screenTop || window.screenY;
	}
	
	// for now, move the window to the top left
	// then resize to the maximum viewable dimension possible
	window.moveTo(0, 0);
	window.resizeTo(screen.availWidth, screen.availHeight);
	
	// now that we have set the browser to it's biggest possible size
	// get the inner dimensions.  the offset is the difference.
	var inner = getInnerSize();
	
	var diffX = screen.availWidth - inner[0]; 
	var diffY = screen.availHeight - inner[1];
	
	// now that we have an offset value, size the browser
	// and position it
	window.resizeTo((parseInt(width) + diffX), height + diffY);
	window.moveTo(screenX, screenY);
}
				

/**
 * Helper function to get the query string parameters from the URL
 */
var urlHelper = new function() {
	/**
	 * Retrieves an array of URL query string parameters in order
	 * @param url
	 * @returns params JSON object
	 */
	this.getUrlParams = function(url) {
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

/**
 * Gets the mouse click coordinates for different browsers and scrolling
 * 
 */
function getMouseClickCoords(e) {
	var evt = e ? e : window.event;
	var clickX = 0, clickY = 0;

	if ((evt.clientX || evt.clientY) && document.body
			&& document.body.scrollLeft != null) {
		clickX = evt.clientX + document.body.scrollLeft;
		clickY = evt.clientY + document.body.scrollTop;
	}
	if ((evt.clientX || evt.clientY) && document.compatMode == 'CSS1Compat'
			&& document.documentElement
			&& document.documentElement.scrollLeft != null) {
		clickX = evt.clientX + document.documentElement.scrollLeft;
		clickY = evt.clientY + document.documentElement.scrollTop;
	}
	if (evt.pageX || evt.pageY) {
		clickX = evt.pageX;
		clickY = evt.pageY;
	}
	return {
		posX : clickX,
		posY : clickY
	};
}

/**
 * Gets mouse click coordinates on the screen and submits to the server
 */
function addMouseClick(e) {
	var coords = getMouseClickCoords(e);
	
	var inner = getInnerSize();
	var data =  { action : "add_mouse_click", nonce : hotSpotsData.ajaxNonce, x : coords.posX, y : coords.posY, url : window.location.href, width : inner[0] };
	jQuery.post(hotSpotsData.ajaxUrl, data, function(response) {
		var jsonResponse = jQuery.parseJSON(response);

		if (drawHotSpotsEnabled === true && debug === true) {
			var heatValue = jsonResponse.heatValue;
			drawMouseClick(coords.posX, coords.posY, heatValue);
		}
	});
}

/**
 * Draws all mouse clicks on the screen
 * 
 * @param response
 */
function drawAllMouseClicks(response) {
	for (var index in response) {
		var mouseClick = response[index];
		// draw the mouse click on the canvas
		drawMouseClick(mouseClick.x, mouseClick.y, mouseClick.heatValue);
	}
}

/**
 * Draws a mouse clicks circle with heat
 * 
 * @param posX
 * @param posY
 * @param heatValue
 * @returns
 */
function drawMouseClick(posX, posY, heatValue) {
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
			var someGreen = MAX_COLOUR - (MAX_COLOUR * ((heatValue - warm) / warm));
			fillStyle = "rgba(" + MAX_COLOUR + ", " + Math.round(someGreen) + ", "
					+ MIN_COLOUR + ", " + opacity + ")";
		} else { // more green
			var someRed = MAX_COLOUR * (heatValue / warm);
			fillStyle = "rgba(" + Math.round(someRed) + ", " + MAX_COLOUR + ", "
					+ MIN_COLOUR + ", " + opacity + ")";
		}
	}
	
	context.fillStyle = fillStyle;
	context.fill();
}

/**
 * Initialises the canvas element
 * 
 */
function initCanvas() {
	var docWidth = jQuery(document).width();
	var docHeight = jQuery(document).height();
	
	// WordPress admin bar fix.
	var top = 0;
	if (jQuery.browser.msie && jQuery('#wpadminbar').length > 0) {
		top = jQuery('#wpadminbar').height();
	}
	
	// Create a blank div where we are going to put the canvas into.
	var canvasContainer = document.createElement('div');
	document.body.appendChild(canvasContainer);
	canvasContainer.setAttribute("id", "canvasContainer");
	canvasContainer.style.position = "absolute";
	canvasContainer.style.left = "0px";
	canvasContainer.style.top = top + "px";
	canvasContainer.style.width = "100%"; 
	canvasContainer.style.height = "100%"; 
	canvasContainer.style.zIndex = "1000";

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
			jQuery(this).css({ opacity: 0.99 });
		}
		// check z-index to ensure heat map is overlayed on top of any element
		var zIndex = jQuery(this).css("z-index");
		if (zIndex > 1000) {
			var canvasContainer = jQuery("#canvasContainer");
			canvasContainer.css("z-index", zIndex + 1);
		}
	});
}
