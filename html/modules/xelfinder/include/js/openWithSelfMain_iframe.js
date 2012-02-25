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
			'<script src="'+XOOPS_URL+'/common/elfinder/jquery/jquery-1.7.1.min.js" type="text/javascript" charset="utf-8"></script>'
				+
			'<script src="'+XOOPS_URL+'/common/elfinder/jquery/jquery-ui-1.8.16.custom.min.js" type="text/javascript" charset="utf-8"></script>'
				+
			'<link rel="stylesheet" href="'+XOOPS_URL+'/common/elfinder/jquery/ui-themes/smoothness/jquery-ui-1.8.16.custom.css" type="text/css" media="screen" charset="utf-8">'
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
	//var purl = url.match(/^()\/$/);
	jQuery.modal('<iframe name="'+name+'" id="xelf_window" src="' + url + '" height="'+h+'" width="'+w+'" style="border:0;overflow:hidden;" allowtransparency="true" scrolling="no">', {
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
			padding:			0
		},
		overlayClose:		 true
	});

	var popW = document.getElementById('xelf_window');

	jQuery('#xelf_window').load(
		function($){
//			$('#simplemodal-container').draggable();
			$('#simplemodal-container').resizable({
				handles: 'e, s',
				alsoResize: '#xelf_window',
				start: function(event, ui)
				{
				  // Add frame helpers
				  $("iframe").each(function() {
					var offsetWidth = this.offsetWidth, offsetHeight = this.offsetHeight;

					$('<div class="ui-resizable-iframeFix" style="background: #fff;"></div>')
					  .css({
						width: offsetWidth+"px", height: offsetHeight+"px",
						position: "absolute", opacity: "0.001", zIndex: 10000
					  })
					  .css($(this).offset())
					  .appendTo("body")
					  .data("resizable", { width: offsetWidth, height: offsetHeight });
				  });
				},
				resize: function(event, ui)
				{
				  var self = $('#simplemodal-container').data("resizable"), o = self.options, os = self.originalSize, op = self.originalPosition;

				  var delta = {
					height: (self.size.height - os.height) || 0, width: (self.size.width - os.width) || 0,
					top: (self.position.top - op.top) || 0, left: (self.position.left - op.left) || 0
				  },

				  _alsoResize = function(exp, c) {
					$(exp).each(function() {
					  var el = $(this), start = $(this).data("resizable"), style = {}, css = c && c.length ? c : ['width', 'height', 'top', 'left'];

					  $.each(css || ['width', 'height', 'top', 'left'], function(i, prop) {
						// iframeより少し大きめに設定することで、iframe上でmouseupしてしまったときにmouseupイベント捕捉できない問題解消
						var sum = (start[prop]||0) + (delta[prop]||0) + 5;
						if (sum && sum >= 0)
						  style[prop] = sum || null;
					  });

					  //Opera fixing relative position
					  if (/relative/.test(el.css('position')) && $.browser.opera) {
						self._revertToRelativePosition = true;
						el.css({ position: 'absolute', top: 'auto', left: 'auto' });
					  }

					  el.css(style);
					});
				  };

				  _alsoResize('div.ui-resizable-iframeFix', ['width', 'height']);
				},
				stop: function(event, ui)
				{
				  // Remove frame helpers
				  $("div.ui-resizable-iframeFix")
					.removeData('resizable')
					.each(function() { this.parentNode.removeChild(this); });
				}
			});
			setTimeout(function(){ popW.contentWindow.focus(); }, 100);
		}(jQuery)
	);

//	jQuery('#modalContent').modal({onShow: function (dialog) {
//		dialog.container.resizable();
//	}});


	if (returnwindow != null){
		return popW;
	}

}
