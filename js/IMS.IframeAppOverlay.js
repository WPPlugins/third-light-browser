window.IMS = window.IMS || {};
IMS.IframeAppOverlay = function(url, options){

	//some clever code to get the IE version from SO.
	//when we no longer support IE8, this can go:
	var ie = (function(){
		var v = 3,
	        div = document.createElement('div'),
		    all = div.getElementsByTagName('i');
		while(true){
			div.innerHTML = '<!--[if gt IE ' + (++v) + ']><i></i><![endif]-->';
			if(!all[0]) break;
		}
		return v > 4 ? v : undefined;
	}());

	//I attach styles that only IE8 and below will see:
	document.getElementsByTagName("head")[0]
	.appendChild(document.createComment("[if lt IE 9]>"+
		"#ims-iframe-container {"+
		"	top:5px !important;"+
		"	left:5px !important;"+
		"	z-index: 1000;"+
		"<![endif]"));

	// I return a string with each prefix attached:
	function prefix(str, prepend){
		var p = ["-moz-", "-webkit-", "-ms-", "-o-", ""];
		var out = "";
		for(var i = 0; i < p.length; i++){
			out += prepend+p[i]+str+";";
		}
		return out;
	}

	// I attach events to elements and return a func which detatches:
	function addEvent(el, evt, func){
		if(el.addEventListener) {
			el.addEventListener(evt, func, false);
			return function(){ el.removeEventListener(evt, func); }
		}
		else if(el.attachEvent) {
			evt = "on"+evt;
			el.attachEvent(evt, func);
			return function(){ el.detatchEvent(evt, func); }
		}
	}

	// I make sure that dimensions passed in are valid:
	function dim(val){
		if(typeof val == "number") return val+"px";
		if(typeof val == "string") {
			ndim = parseInt(val,10);
			if(isNaN(ndim)) throw Error("IframeAppOverlay: cannot parse val "+val);
			var suffix = val.match(/[0-9]+(.*)$/)[1];
			if(!suffix.length) suffix = "px";
			var allowed = ["px", "em", "%"];
			if(allowed.indexOf(suffix) == -1) throw Error("IframeAppOverlay: invalid val suffix "+suffix);
			return ndim+suffix;
		}
		throw Error("IframeAppOverlay: cannot parse dimension.");
	}
	var body = document.getElementsByTagName("body")[0];

	// I am the transparent overlay. In IE8, I
	// use an MS filter to get the opacity.
	var overlay = document.createElement("div");
	overlay.setAttribute("id", "ims-overlay");
	overlay.setAttribute("style",
		"position: fixed;"+
		"top: 0px;"+
		"right: 0px;"+
		"bottom: 0px;"+
		"left: 0px;"+
		"z-index: 100000;"+
		"background-color: rgb(0,0,0);"+
		(ie < 9? "filter: alpha(opacity = 70);" : "")+
		"background-color: rgba(0,0,0,0.7);"
		);

	// I am the centered box that will hold the iframe.
	// Set defaults for offsets and sanitise
	["left","right","top","bottom"].forEach(function(offset) {
	    if (!(offset in options)) options[offset] = 30;
	    options[offset] = dim(options[offset]);
	});
	// Sanitise width and height if present
    if (options.width) options.width = dim(options.width);
    if (options.height) options.height = dim(options.height);
	var container = document.createElement("div");
	container.setAttribute("id", "ims-iframe-container");
	container.setAttribute("style", 
		"position: fixed;"+
		(options.height ? prefix("calc((100% - "+options.height+") / 2)", "top:") : "top: "+options.top+";")+
		(options.height ? prefix("calc((100% - "+options.height+") / 2)", "bottom:") : "bottom: "+options.bottom+";")+
		(options.width ? prefix("calc((100% - "+options.width+") / 2)", "left:") : "left: "+options.left+";")+
		(options.width ? prefix("calc((100% - "+options.width+") / 2)", "right:") : "right: "+options.right+";")+
		"background-color: rgb(255,255,255);"
		);

	overlay.appendChild(container);
	body.appendChild(overlay);

	// I am the widget that sits inside the container:
	options.target = container;
	var App = IMS.IframeApp(url, options);

	// I turn off scrolling on body until the function I return is executed:
	reenableScrolling = (function(){
		var body = document.getElementsByTagName("body")[0];
		var oldoverflow = body.style.overflow;
		body.style.overflow = "hidden";
		var reenabled = false;
		return function(){
			if(!reenabled) body.style.overflow = oldoverflow;
			reenabled = true;
		}
	}());

	// I destroy everything if executed:
	function destroy(){
		reenableScrolling();
		destroyEvent();
		overlay.parentNode.removeChild(overlay);
	}

	// I destroy everything if a destroy event is sent from the app.
	// The app will destroy itself internally when this event is sent
	// out, so I don't have to worry about that.
	var destroyEvent = App.on("destroy", destroy);

	//return a reference to the Application, so that others
	//can subscribe to events and whatnot:
	return App;
};
