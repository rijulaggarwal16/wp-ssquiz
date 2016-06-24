
// jQuery Color Animation
(function(d){d.each(["backgroundColor","borderBottomColor","borderLeftColor","borderRightColor","borderTopColor","color","outlineColor"],function(f,e){d.fx.step[e]=function(g){if(!g.colorInit){g.start=c(g.elem,e);g.end=b(g.end);g.colorInit=true}g.elem.style[e]="rgb("+[Math.max(Math.min(parseInt((g.pos*(g.end[0]-g.start[0]))+g.start[0]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[1]-g.start[1]))+g.start[1]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[2]-g.start[2]))+g.start[2]),255),0)].join(",")+")"}});function b(f){var e;if(f&&f.constructor==Array&&f.length==3){return f}if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return[parseInt(e[1]),parseInt(e[2]),parseInt(e[3])]}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return[parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55]}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}if(e=/rgba\(0, 0, 0, 0\)/.exec(f)){return a.transparent}return a[d.trim(f).toLowerCase()]}function c(g,e){var f;do{f=d.css(g,e);if(f!=""&&f!="transparent"||d.nodeName(g,"body")){break}e="backgroundColor"}while(g=g.parentNode);return b(f)}var a={}})(jQuery);

jQuery(document).ready(function($){
	$.extend($.support, {
			touch: "ontouchend" in document
	});
	
	$.fn.addTouch = function() {
			if ($.support.touch) {
					this.each(function(i,el){
							el.addEventListener("touchstart", iPadTouchHandler, false);
							el.addEventListener("touchmove", iPadTouchHandler, false);
							el.addEventListener("touchend", iPadTouchHandler, false);
							el.addEventListener("touchcancel", iPadTouchHandler, false);
					});
			}
	};
	
	var lastTap = null;  
	var tapValid = false;			// Are we still in the .6 second window where a double tap can occur
	var tapTimeout = null;			// The timeout reference
	
	function cancelTap() {
		tapValid = false;
	}
	
	
	var rightClickPending = false;	// Is a right click still feasible
	var rightClickEvent = null;		// the original event
	var holdTimeout = null;			// timeout reference
	var cancelMouseUp = false;		// prevents a click from occuring as we want the context menu
	
	
	function cancelHold() {
		if (rightClickPending) {
			window.clearTimeout(holdTimeout);
			rightClickPending = false;
			rightClickEvent = null;
		}
	}
	
	function startHold(event) {
		if (rightClickPending)
			return;
	
		rightClickPending = true; // We could be performing a right click
		rightClickEvent = (event.changedTouches)[0];
		holdTimeout = window.setTimeout("doRightClick();", 800);
	}
	
	
	function doRightClick() {
		rightClickPending = false;
	
		//
		// We need to mouse up (as we were down)
		//
		var first = rightClickEvent,
			simulatedEvent = document.createEvent("MouseEvent");
		simulatedEvent.initMouseEvent("mouseup", true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
				false, false, false, false, 0, null);
		first.target.dispatchEvent(simulatedEvent);
	
		//
		// emulate a right click
		//
		simulatedEvent = document.createEvent("MouseEvent");
		simulatedEvent.initMouseEvent("mousedown", true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
				false, false, false, false, 2, null);
		first.target.dispatchEvent(simulatedEvent);
	
		//
		// Show a context menu
		//
		simulatedEvent = document.createEvent("MouseEvent");
		simulatedEvent.initMouseEvent("contextmenu", true, true, window, 1, first.screenX + 50, first.screenY + 5, first.clientX + 50, first.clientY + 5,
									  false, false, false, false, 2, null);
		first.target.dispatchEvent(simulatedEvent);
	
	
		//
		// Note:: I don't mouse up the right click here however feel free to add if required
		//
	
	
		cancelMouseUp = true;
		rightClickEvent = null; // Release memory
	}
	
	
	//
	// mouse over event then mouse down
	//
	function iPadTouchStart(event) {
		var touches = event.changedTouches,
			first = touches[0],
			type = "mouseover",
			simulatedEvent = document.createEvent("MouseEvent");
		//
		// Mouse over first - I have live events attached on mouse over
		//
		simulatedEvent.initMouseEvent(type, true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, 0, null);
		first.target.dispatchEvent(simulatedEvent);
	
		type = "mousedown";
		simulatedEvent = document.createEvent("MouseEvent");
	
		simulatedEvent.initMouseEvent(type, true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, 0, null);
		first.target.dispatchEvent(simulatedEvent);
	
	
		if (!tapValid) {
			lastTap = first.target;
			tapValid = true;
			tapTimeout = window.setTimeout("cancelTap();", 600);
			startHold(event);
		}
		else {
			window.clearTimeout(tapTimeout);
	
			//
			// If a double tap is still a possibility and the elements are the same
			//	Then perform a double click
			//
			if (first.target == lastTap) {
				lastTap = null;
				tapValid = false;
	
				type = "click";
				simulatedEvent = document.createEvent("MouseEvent");
	
				simulatedEvent.initMouseEvent(type, true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, 0/*left*/, null);
				first.target.dispatchEvent(simulatedEvent);
	
				type = "dblclick";
				simulatedEvent = document.createEvent("MouseEvent");
	
				simulatedEvent.initMouseEvent(type, true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, 0/*left*/, null);
				first.target.dispatchEvent(simulatedEvent);
			}
			else {
				lastTap = first.target;
				tapValid = true;
				tapTimeout = window.setTimeout("cancelTap();", 600);
				startHold(event);
			}
		}
	}
	
	function iPadTouchHandler(event) {
		var type = "",
			button = 0; /*left*/
	
		if (event.touches.length > 1)
			return;
	
		switch (event.type) {
			case "touchstart":
				if ($(event.changedTouches[0].target).is("select")) {
					return;
				}
				iPadTouchStart(event); /*We need to trigger two events here to support one touch drag and drop*/
				event.preventDefault();
				return false;
				break;
	
			case "touchmove":
				cancelHold();
				type = "mousemove";
				event.preventDefault();
				break;
	
			case "touchend":
				if (cancelMouseUp) {
					cancelMouseUp = false;
					event.preventDefault();
					return false;
				}
				cancelHold();
				type = "mouseup";
				break;
	
			default:
				return;
		}
	
		var touches = event.changedTouches,
			first = touches[0],
			simulatedEvent = document.createEvent("MouseEvent");
	
		simulatedEvent.initMouseEvent(type, true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, button, null);
	
		first.target.dispatchEvent(simulatedEvent);
	
		if (type == "mouseup" && tapValid && first.target == lastTap) {	// This actually emulates the ipads default behaviour (which we prevented)
			simulatedEvent = document.createEvent("MouseEvent");		// This check avoids click being emulated on a double tap
	
			simulatedEvent.initMouseEvent("click", true, true, window, 1, first.screenX, first.screenY, first.clientX, first.clientY,
								false, false, false, false, button, null);
	
			first.target.dispatchEvent(simulatedEvent);
		}
	}
});