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
	// get options
	enabled = (hotSpotData.enabled == "on") ? true : false;
	showOnClick = (hotSpotData.showOnClick) == "on" ? true : false;
	spotRadius = parseInt(hotSpotData.spotRadius);
	hot = parseInt(hotSpotData.hotValue);
	warm = hot / 2;
	opacity = hotSpotData.spotOpacity;
	drawHotSpots = urlHelper.getUrlParamByName(window.location.href,
			'drawHotSpots') === "true" ? true : false;
	
	if (enabled) {
		if (drawHotSpots === true) {
			createCanvasElement();
			
			// set opacity for all elements so that hot spots are visible
			jQuery("body *").each(function() {
				// if current element already already has opacity < 1, leave as is
				var opacity = jQuery(this).css("opacity");
				if (opacity !== undefined && opacity === 1) {
					jQuery(this).css({ opacity: 0.99 });
				}
			});
				
			// Get mouse clicks
			var data =  { action : "get_mouse_clicks", nonce : hotSpotData.ajaxNonce, url : window.location.href };
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
		// Fix in v1.2.2 do not need to worry about url as this is handled by server
		//if (urlHelper.equals(window.location.href, clickData.url, ['drawHotSpots'])) {
			allMouseClicks.push({ "x" : clickData.x, "y" : clickData.y, "id" : clickData.id });
		//}
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
 * TODO move to server
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
		var currentId = allMouseClicks[index].id;
		
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
