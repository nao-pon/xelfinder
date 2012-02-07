var XOOPS_URL;
var XELFINDER_URL;

(function (){
	var scripts = document.getElementsByTagName("head")[0].getElementsByTagName("script");
	var i = scripts.length;
	while (i--) {
		var match = scripts[i].src.match(/^((.+)\/[^\/]+\/[^\/]+)\/include\/js\/openWithSelfMain_iframe\.js$/);
		if (match) {
			XELFINDER_URL = match[1];
			XOOPS_URL = match[2];
			break;
		}
	}
	if (typeof jQuery == 'undefined') {
		document.write (
			'<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>'
		);
	}
	document.write (
		'<link rel="stylesheet" href="'+XOOPS_URL+'/common/js/simplemodal/css/basic.css" type="text/css" media="screen" />'
		+'<script defer="defer" type="text/javascript" src="'+XOOPS_URL+'/common/js/simplemodal/js/jquery.simplemodal.js"></script>'
		+'<script defer="defer" type="text/javascript" src="'+XOOPS_URL+'/common/js/simplemodal/js/basic.js"></script>'
		+'<script defer="defer" type="text/javascript">jQuery.noConflict();</script>'
	);
})();

function openWithSelfMain(url, name, w, h, returnwindow) {
	var purl = url.match(/^()\/$/)
	jQuery.modal('<iframe name="'+name+'" id="xelf_window" src="' + url + '" height="'+h+'" width="'+w+'" style="border:0;overflow:hidden;" allowtransparency="true" scrolling="no">', {
	    containerCss:{
	        backgroundColor:    "transparent",
	        borderColor:        "transparent",
	        border:             "none",
	        backgroundImage:    "url('"+XELFINDER_URL+"/images/manager_loading.gif')",
	        backgroundRepeat:   "no-repeat",
	        backgroundPosition: "center center",
	        padding:            0,
	        height:             h,
	        width:              w
	    },
	    dataCss:{
	    	padding:            0
	    },
	    overlayClose:        true
	});

	var popW = document.getElementById('xelf_window');

	jQuery('#xelf_window').load(
		function(){
			popW.contentWindow.focus();
		}
	);

	if (returnwindow != null){
		return popW;
	}

}
