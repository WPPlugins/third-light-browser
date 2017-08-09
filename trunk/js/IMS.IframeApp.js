// This code allows for the startup of an application inside an
// iframe, along with the message passing functionality that goes
// along with it.
window.IMS = window.IMS || {};
IMS.IframeApp = function(url, options){

	// stringify options.options and append to URL as a querystring:
	url = (function(){
		var append = url.indexOf("?") != -1? "&" : "?";
		append += "options="+JSON.stringify(options.options);
		return url+append;
	}());

	//initialise our iframe into the target element.
	//set up a listener for events from it.
	//This is going to be a ref to our iframe window:
	var iframe;
	var otherWindow = (function(){

		iframe = document.createElement("iframe");
		var targetEl = typeof options.target == "string"?
			document.getElementById(options.target) :
			options.target;

		//iframe setup:
		iframe.src = url;
		iframe.style.position = "absolute";
		iframe.style.top = "0px";
		iframe.style.left = "0px";
		iframe.style.border = "0px";
		iframe.style.width = "100%";
		iframe.style.height = "100%";

		//remove any contents of the element we're putting the iframe in:
		while(targetEl.firstNode){targetEl.removeChild(targetEl.firstNode);}
		targetEl.appendChild(iframe);

		//return a reference to the iframes window object:
		return iframe.contentWindow;
	}());

	//manage subscribed functions:
	var subscribed = {};
	var empty = {};
	function getLoc(action){
		var output;
		if(empty[action] && empty[action].length) {
			output = empty[action].pop();
		} else if(subscribed[action]){
			output = subscribed[action].length;
		} else {
			subscribed[action] = [];
			output = 0;
		}
		return output;
	}
	function removeItem(action,loc){
		delete subscribed[action][loc];
		if(!empty[action]) empty[action] = [];
		empty[action].push(loc);
	}

	//do this when a message is received:
	function messageReceived(m){
		var action = m.action;
		var funcs = subscribed[action] || [];
		for(var i = 0; i < funcs.length; i++) {
			if(funcs[i]) funcs[i](m.data);
		}
	}

	//Try to convert between string and JSON for IE9's sake:
	function stringify(thing){
		if(window.JSON && window.JSON.stringify && typeof thing == "object") {
			return JSON.stringify(thing);
		}
		return thing;
	}
	function unstringify(thing){
		try {
			if(window.JSON && window.JSON.parse && typeof thing == "string") {
				thing = JSON.parse(thing);
			}
		}
		catch(e) {}
		return thing;
	}

	//listen for messages from the child iframe.
	//ignore any messages that are from somewhere else:
	function messageReceivedExternal(e){
		//try to convert data back into an object if needbe:
		var data = unstringify(e.data);

		//block messages from an iframe we didnt make:
		if(e.source != otherWindow) return;

		//check that data is in the correct format:
		if(typeof data != "object") return;
		if(typeof data.action != "string") return;
		if(data == null) return;

		//data is OK, let's use it:
		messageReceived(data);
	}

	//listen for messages (attachevent is for IE):
	if(window.addEventListener) window.addEventListener("message", messageReceivedExternal,false);
	else if(window.attachEvent) window.attachEvent("onmessage", messageReceivedExternal);

	//fire an event off:
	function fire(action, data){
		if(typeof action != "string") throw Error("IMSIframe:fire: 1st arg should be string.");
		otherWindow.postMessage(stringify({action:action, data:data}), "*");
		messageReceived({action:action, data:data});
	}

	//allow events to be attached:
	function on(action, callback){
		if(typeof action != "string") throw Error("IMSIframe:on: 1st arg should be string.");
		if(typeof callback != "function") throw Error("IMSIframe:on: 2nd arg should be a function.");
		var index = getLoc(action);
		subscribed[action][index] = callback;

		//allow one unsubscription to occur when the returned func is fired:
		var isUnsubscribed = false;
		return function(){
			if(!isUnsubscribed) removeItem(action,index);
			isUnsubscribed = true;
		}
	}

	//destroy everything on a destroy event:
	on("destroy", function(){
		if(window.removeEventListener) window.removeEventListener("message", messageReceivedExternal);
		else if(window.detatchEvent) window.detatchEvent("onmessage", messageReceivedExternal);
		//if something else didn't destory me, I will:
		if(iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
	});

	return {
		//observe an action from the iframe. Returns a function which,
		//when called, unsubscribes from the event.
		on: on,
		//fire off an event with action string and some data:
		fire: fire,
		//destroy the iframe and remove events. I only run once:
		destroy: function(){ fire("destroy"); }
	};
};