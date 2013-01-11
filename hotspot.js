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

/**
 * Helper function to get the query string parameters from the URL
 */
jQuery.extend({
	getUrlVars : function() {
		var vars = [], hash;
		var hashes = window.location.href.slice(
				window.location.href.indexOf('?') + 1).split('&');
		for ( var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	},
	getUrlVar : function(name) {
		return jQuery.getUrlVars()[name];
	}
});

jQuery(document).ready(function() {
	enabled = (hotSpotData.enabled == "on") ? true : false;
	showOnClick = (hotSpotData.showOnClick) == "on" ? true : false;
	spotRadius = parseInt(hotSpotData.spotRadius);
	hot = parseInt(hotSpotData.hotValue);
	warm = hot / 2;
	opacity = hotSpotData.spotOpacity;
	drawHotSpots = jQuery.getUrlVar('drawHotSpots') === "true" ? true : false;
	
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
		
		// Add mouse clicks
		jQuery(document).live('click',function(e) {
			addMouseClick(e);
		});
	}
});


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
		posx : clickX,
		posy : clickY
	}
}

/**
 * Gets mouse click coordinates on the screen and submits to the server
 */
function addMouseClick(e) {
	var coords = getMouseClickCoords(e);

	var data =  { action : "add_mouse_click", nonce : hotSpotData.ajaxNonce, x : coords.posx, y : coords.posy };
	jQuery.post(hotSpotData.ajaxUrl, data, function(response) {
		// do nothing
	});
	
	if (drawHotSpots === true && showOnClick === true) {		
		// draw the mouse click on the canvas
		var heatValue = calculateHeatValue(coords.posx, coords.posy);
		
		drawMouseClick(coords.posx, coords.posy, heatValue);
		
		// Add mouse click last so that it does not affect the heat value
		allMouseClicks.push({ "x" : coords.posx, "y" : coords.posy });
	}
}

/**
 * Draws all mouse clicks on the screen
 * @param response
 */
function drawAllMouseClicks(response) {
	allMouseClicks = [];
	
	for (var index in response) {
		var coords = response[index];
		var posx = coords.x;
		var posy = coords.y;
		
		// Add mouse click x and y position to array
		allMouseClicks.push({ "x" : posx, "y" : posy });
	}
	for (var index in allMouseClicks) {
		var coords = allMouseClicks[index];
		var posx = coords.x;
		var posy = coords.y;
		var heatValue = calculateHeatValue(posx, posy);
	
		// draw the mouse click on the canvas
		drawMouseClick(posx, posy, heatValue);
	}
}

/**
 * Draws a mouse clicks circle with heat
 * @param posx
 * @param posy
 * @param allMouseClicks
 * @param heatValue
 * @returns
 */
function drawMouseClick(posx, posy, heatValue) {
	var canvas = jQuery("#canvas").get(0);
	var context = canvas.getContext("2d");
	context.beginPath();
	context.arc(posx, posy, spotRadius, 0, 2 * Math.PI);

	/* 
	 * Calculates RGB colour for corresponding heat value. From Green to Red, 
	 * therefore Blue is always 0.
	 * Green is cold, Orange is warm and Red is hot
	 * Green is 0, 255, 0 and Red is 255, 0, 0. In between is 255, 255, 0
	 *
	 * We need to figure out the red or green colur values in the heat value scale.
	 * Let the scale be 0 - 10 where 10 is hot, 5 is warm and the heat value is 7.
	 * Therefore we need to determine the red colur value as it's hottern than warm.
	 * We have 255 possible red colour values and the red colour scale is 6-10. 
	 * So the equation for red is x = 255 X (<heat value> - warm / warm) 
	 *                      		= 255 X 2/5
	 *                      		= 255 X 0.4
	 *                      		= 102.
	 * The equation for green is x = 255 X heat value / warm.
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
	
	// Create a blank div where we are going to put the canvas into.
	var canvasContainer = document.createElement('div');
	document.body.appendChild(canvasContainer);
	canvasContainer.style.position = "absolute";
	canvasContainer.style.left = "0px";
	canvasContainer.style.top = "0px";
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
 */
function calculateHeatValue(posx, posy) {
	// Calculate heat value
	var heatValue = 0;
	for ( var index in allMouseClicks) {
		var tempx = allMouseClicks[index].x;
		var tempy = allMouseClicks[index].y;
		var diffx = posx - tempx;
		var diffy = posy - tempy;
		var hotx = (diffx > -spotRadius && diffx < spotRadius);
		var hoty = (diffy > -spotRadius && diffy < spotRadius);
		if (hotx && hoty) {
			heatValue++;
		}
	}
	return heatValue;
}
