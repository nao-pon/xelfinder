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
			'<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js" type="text/javascript" charset="utf-8"></script>'
				+
			'<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>'
				+
			'<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/smoothness/jquery-ui.min.css" type="text/css" media="screen" charset="utf-8">'
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
	var $ = jQuery;
	var margin = $.mobile? 0 : 60;
	w = $(window).width() - margin;
	h = $(window).height() - margin;
	$.modal('<iframe name="'+name+'" id="xelf_window" src="' + url + '" height="100%" width="100%" style="border:0;overflow:hidden;" allowtransparency="true" scrolling="no" frameborder="0" allowfullscreen="allowfullscreen">', {
		containerCss:{
			backgroundColor:	"transparent",
			borderColor:		"transparent",
			border:				"none",
			backgroundImage:	"url('"+XELFINDER_URL+"/images/manager_loading.gif')",
			backgroundRepeat:	"no-repeat",
			backgroundPosition: "center center",
			padding:			0,
			height:				h,
			width:				w
		},
		dataCss:{
			overflow:			"hidden",
			padding:			0,
			height:				"100%",
			width:				"100%"
		},
		overlayClose:			true,
		zIndex:					100000
	});

	$('#xelf_window').load(
		function(e){
			$(this).css({overflow: 'auto'});
			$.mobile && $('#simplemodal-container a.modalCloseImg').css({
				top:0,
				right:0});
			setTimeout(function(){ e.target.contentWindow.focus(); }, 100);
		}
	);

	var resizeTimer = null;
	$(window).resize(function() {
		resizeTimer && clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function() {
			$("#simplemodal-container").css({
				height: $(window).height() - margin,
				width: $(window).width() - margin,
				top: margin/2,
				left: margin/2});
		}, 200);
	});

	if (returnwindow != null){
		return $('#xelf_window');
	}
}
