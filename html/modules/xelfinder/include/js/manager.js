$().ready(function() {

	$().toastmessage( { sticky : true } );

	var elf = $('#elfinder').elfinder({
		lang: 'jp',
		url : myUrl + 'connector.php',
		height: '400',
		getFileCallback : callbackFunc,
		commandsOptions : {
			  getfile : {
			    onlyURL : false,
			    multiple : false,
			    folders : false
			  }
		},
		contextmenu : {
			// navbarfolder menu
			navbar : ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],
			// current directory menu
			cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'sort', '|', 'info'],
			// current directory file menu
			files  : ['getfile', '|','open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info']
		}
	}).elfinder('instance');

});

$.fn.extend({
	insertAtCaret: function(v) {
		var pa = null;
		var o = null;
		try {
			pa = window.opener;
			o = pa.document.getElementById(target);
		} catch(e) {
			try {
				pa = window.parent;
				o = pa.document.getElementById(target);
			} catch(e) {}
		}
		if (o) {
			o.focus();
			if (jQuery.browser.msie) {
				var r = document.selection.createRange();
				r.text = v;
				r.select();
			} else {
				var s = o.value;
				var p = o.selectionStart;
				var np = p + v.length;
				o.value = s.substr(0, p) + v + s.substr(p);
				o.setSelectionRange(np, np);
			}
			try {
				pa.jQuery.modal.close();
			} catch(e) {
				window.close();
			}
		}
	}
});

var getFileCallback_bbcode = function (file) {
	var buttons = '<span onclick="insertCode(\'left\',1);"><img src="'+imgUrl+'alignleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',1)"><img src="'+imgUrl+'aligncenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',1)"><img src="'+imgUrl+'alignright.gif" alt="" /></span>'
				+ '<br>'
				+ '<span onclick="insertCode(\'left\',0);"><img src="'+imgUrl+'alignbigleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',0)"><img src="'+imgUrl+'alignbigcenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',0)"><img src="'+imgUrl+'alignbigright.gif" alt="" /></span>'
				+ '<br>'
				+ '<span class="file_info">__FILEINFO__</span>';

	var path = file.url.replace(rootUrl+'/', '');
	var basename = path.replace( /^.*\//, '' );
	var module = path.replace( /^.*?(?:modules|uploads)\/([^\/]+)\/.*$/, '$1' );
	thumb = ''
	if (module.match(/^[a-zA-Z0-9_-]+$/)) {
		eval('if (typeof get_thumb_'+module+' == "function" ){' +
			'thumb = get_thumb_'+module+'(basename, file);}' );
	}
	imgThumb = encodeURI(thumb);
	imgPath = encodeURI(path);

	var fileinfo = 'Size: ' + file.width + 'x' + file.height;

	$().toastmessage( 'removeToast', $('.toast-item'));
	$().toastmessage( 'showSuccessToast', buttons.replace('__FILEINFO__', fileinfo) );
	$('.toast-item').css('background-image','url("'+file.url+'")');
}

function insertCode(align, thumb, format) {
	$('.toast-item-close').click();
	$('.toast-item').css('background-image','');
	if (! format) {
		if (thumb && imgThumb) {
			var code = '[siteurl='+imgPath+'][siteimg align='+align+']'+imgThumb+'[/siteimg][/siteurl]';
		} else {
			var code = '[siteimg align='+align+']'+imgPath+'[/siteimg]';
		}
	} else if (format == 'xpwiki') {
		var size = $('#resize_px').val();
		if (size.match(/[\d]{1,4}/)) {
			size = size.replace(/([\d]{1,4})/, ",mw:$1,mh:$1");
		} else {
			size = '';
		}

		var pa = null;
		var o = null;
		if (target) {
			try {
				pa = window.opener;
				o = pa.document.getElementById(target);
			} catch(e) {
				try {
					pa = window.parent;
					o = pa.document.getElementById(target);
				} catch(e) {}
			}
		}

		if (thumb || o.tagName != 'TEXTAREA') {
			var code = '&ref(site://'+imgPath+','+align+size+');';
		} else {
			var code = '#ref(site://'+imgPath+','+align+size+')\n\n';
		}
	}
	if (target) {
		$().insertAtCaret(code);
		window.close();
	} else {
		// for debug
		$().toastmessage( 'showSuccessToast', code );
	}
}

var getFileCallback_xpwiki = function (file, fm) {
	var buttons = '<span onclick="insertCode(\'left\',1,\'xpwiki\');"><img src="'+imgUrl+'alignleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',1,\'xpwiki\')"><img src="'+imgUrl+'aligncenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',1,\'xpwiki\')"><img src="'+imgUrl+'alignright.gif" alt="" /></span>'
				+ '<br>'
				+ '<span onclick="insertCode(\'left\',0,\'xpwiki\');"><img src="'+imgUrl+'alignbigleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',0,\'xpwiki\')"><img src="'+imgUrl+'alignbigcenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',0,\'xpwiki\')"><img src="'+imgUrl+'alignbigright.gif" alt="" /></span>'
				+ '<br>'
				+ '<span class="file_info">__FILEINFO__</span>'
				+ '<br>'
				+ '<span class="file_info">Resize:<input id="resize_px" style="width: 3em"class="button_input">px</span>';

	var path = file.url.replace(rootUrl+'/', '');
	var basename = path.replace( /^.*\//, '' );
	var module = path.replace( /^.*?(?:modules|uploads)\/([^\/]+)\/.*$/, '$1' );
	thumb = ''
	if (module.match(/^[a-zA-Z0-9_-]+$/)) {
		eval('if (typeof get_thumb_'+module+' == "function" ){' +
			'thumb = get_thumb_'+module+'(basename, file);}' );
	}
	imgThumb = encodeURI(thumb);
	imgPath = encodeURI(path);

	var fileinfo = 'Size: ' + file.width + 'x' + file.height;

	$('.toast-item-close').click();
	$().toastmessage( 'showSuccessToast', buttons.replace('__FILEINFO__', fileinfo) );
	$('.toast-item').css('background-image','url("'+file.url+'")');
}
