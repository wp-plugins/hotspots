// constants
var allMouseClicks = null;
var MAX_COLOUR = 255;
var MIN_COLOUR = 0;

var showOnClick = false; // default is false
var enabled = false; // default is false
var spotRadius = 6;
var hot = 20; // default is 20
var warm = hot / 2; // default is 10
var opacity = 0.2; // default is 0.2
var drawHotSpots = false; // default is false

jQuery(document).ready(function() {	
	
	init();
	
	if (enabled) {
		if (drawHotSpots === true) {
			createCanvasElement();
			
			// set opacity for all elements so that hot spots are visible
			jQuery("body *").each(function() {
				 jQuery(this).css({ opacity: 0.99 });
			});
			
			// Get mouse clicks
			var data =  { action : "get_mouse_clicks", nonce : hotSpotData.ajaxNonce };
			jQuery.post(hotSpotData.ajaxUrl, data, function(response) {
				drawAllMouseClicks(jQuery.parseJSON(response));
			});
		}
		
		// Register event to add mouse clicks
		jQuery(document).live('click',function(e) {
			addMouseClick(e);
		});
	}
});

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
		var hashes = url.slice(url.indexOf('?') + 1).split('&');
		for ( var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			params.push(hash[0]);
			params[hash[0]] = hash[1];
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

	/**
	 * Checks whether two URL's are the same. The query string parameters can be
	 * in different orders and some query string parameter names can be ignored
	 * 
	 * @param url1
	 * @param url2
	 * @param ignoreParams
	 */
	this.equals = function(url1, url2, ignoreParams) {
		var params1 = this.getUrlParams(url1);
		var params2 = this.getUrlParams(url2);
		
		// iterate params1 and check for matches
		for (var i=0; i<params1.length; i++) {
			// skip if this param is ignored
			if (!this.isIgnored(params1[i], ignoreParams)) {
				var foundMatch = false;
				for (var j=0; j<params2.length; j++) {
					if (params1[i] === params2[j]) {
						foundMatch = true;
						// same query parameter names, check values now
						if (this.getUrlParamByName(url1, params1[i]) !== this.getUrlParamByName(url2, params1[j])) {
							return false;
						}
					}
				}
				// If no match is found, URL's are not the same
				if (foundMatch === false) {
					return false;
				}
			}
		}

		// Iterate params2 and check for matches in case it was missing in params1
		for (var i=0; i<params2.length; i++) {
			if (!this.isIgnored(params2[i], ignoreParams)) {
				var foundMatch = false;
				for (var j=0; j<params1.length; j++) {
					if (params1[i] === params2[j]) {
						foundMatch = true;
						// we do not need to check values here as this was done above
					}
				}
				// If no match is found, URL's are not the same
				if (foundMatch === false) {
					return false;
				}
			}
		}
		return true;
	};
	

	/** 
	 * Checks if the param name is to be ignored
	 * 
	 * @param param
	 * @param ignoreParams
	 */
	this.isIgnored = function(param, ignoreParams) {
		for ( var k = 0; k < ignoreParams.length; k++) {
			if (param == ignoreParams[k]) {
				return true;
			}
		}
		return false;
	};
};

/**
 * Initialises constants
 */
function init() {
	enabled = (hotSpotData.enabled == "on") ? true : false;
	showOnClick = (hotSpotData.showOnClick) == "on" ? true : false;
	spotRadius = parseInt(hotSpotData.spotRadius);
	hot = parseInt(hotSpotData.hotValue);
	warm = hot / 2;
	opacity = hotSpotData.spotOpacity;
	drawHotSpots = urlHelper.getUrlParamByName(window.location.href,
			'drawHotSpots') === "true" ? true : false;
}

/**
 * Gets the mouse click coordinates for different browsers and scrolling
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
	}
}

/**
 * Gets mouse click coordinates on the screen and submits to the server
 */
function addMouseClick(e) {
	var coords = getMouseClickCoords(e);
	
	var data =  { action : "add_mouse_click", nonce : hotSpotData.ajaxNonce, x : coords.posX, y : coords.posY, url : window.location.href };
	var id = "";
	jQuery.post(hotSpotData.ajaxUrl, data, function(response) {
		id = response;
		
		if (drawHotSpots === true && showOnClick === true) {		
			// draw the mouse click on the canvas
			var heatValue = calculateHeatValue(coords.posX, coords.posY);
			
			drawMouseClick(coords.posX, coords.posY, heatValue);
			
			// Add mouse click last so that it does not affect the heat value
			allMouseClicks.push({ "x" : coords.posX, "y" : coords.posY, "id" : id });
		}
	});
}

/**
 * Draws all mouse clicks on the screen
 * @param response
 */
function drawAllMouseClicks(response) {
	allMouseClicks = [];
	
	for (var index in response) {
		var clickData = response[index];
		
		// add mouse click if it was the same URL
		if (urlHelper.equals(window.location.href, clickData.url, ['drawHotSpots'])) {
			allMouseClicks.push({ "x" : clickData.x, "y" : clickData.y, "id" : clickData.id });
		}
	}
	for (var index in allMouseClicks) {
		var clickData = allMouseClicks[index];
		var posX = clickData.x;
		var posY = clickData.y;
		var id = clickData.id;
		var heatValue = calculateHeatValue(posX, posY, id);
	
		// draw the mouse click on the canvas
		drawMouseClick(posX, posY, heatValue);
	}
}

/**
 * Draws a mouse clicks circle with heat
 * @param posX
 * @param posY
 * @param allMouseClicks
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
			var red = MAX_COLOUR - (MAX_COLOUR * ((heatValue - warm) / warm));
			fillStyle = "rgba(" + MAX_COLOUR + ", " + Math.round(red) + ", "
					+ MIN_COLOUR + ", " + opacity + ")";
		} else { // more green
			var green = MAX_COLOUR - (MAX_COLOUR * (heatValue / warm));
			fillStyle = "rgba(" + Math.round(green) + ", " + MAX_COLOUR + ", "
					+ MIN_COLOUR + ", " + opacity + ")";
		}
	}
	
	context.fillStyle = fillStyle;
	context.fill();
}

/**
 * Creates the canvas element
 */
function createCanvasElement() {
	var docWidth = jQuery(document).width();
	var docHeight = jQuery(document).height();
	
	

	// WordPress admin bar fix.
	var top = 0;
	if (jQuery('#wpadminbar').length > 0) {
		top = jQuery('#wpadminbar').height();
	}
	
	// Create a blank div where we are going to put the canvas into.
	var canvasContainer = document.createElement('div');
	document.body.appendChild(canvasContainer);
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
}

/**
 * Calculates the heat value given closeness of existing mouse clicks
 * 
 * @param posX
 * @param posY
 * @param id
 */
function calculateHeatValue(posX, posY, id) {
	// Calculate heat value
	var heatValue = 0;
	for ( var index in allMouseClicks) {
		var currentX = allMouseClicks[index].x;
		var currentY = allMouseClicks[index].y;
		var currentId = allMouseClicks[index].y;
		
		// skip if comparing the same mouse click
		if (id !== undefined && id === currentId) {
			continue;
		}
		
		var diffX = posX - currentX;
		var diffY = posY - currentY;
		var hotX = (diffX > -spotRadius && diffX < spotRadius);
		var hotY = (diffY > -spotRadius && diffY < spotRadius);
		if (hotX && hotY) {
			heatValue++;
		}
	}
	return heatValue;
}
