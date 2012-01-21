$().ready(function() {

	$().toastmessage( { sticky : true } );

	var elf = $('#elfinder').elfinder({
		lang: 'jp',
		url : myUrl + 'connector.php',
		height: '400',
		getFileCallback : callbackFunc
	}).elfinder('instance');

});

$.fn.extend({
	insertAtCaret: function(v) {
		var o = null;
		try {
			o = window.opener.document.getElementById(target);
		} catch(e) {
			try {
				o = window.parent.document.getElementById(target);
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

	$('.toast-item-close').click();
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
		if (size.match(/[\d]{1,3}/)) {
			size = size.replace(/([\d]{1,3})/, ",mw:$1,mh:$1");
		} else {
			size = '';
		}
		if (thumb) {
			var code = '&ref('+imgPath+','+align+size+');';
		} else {
			var code = '#ref('+imgPath+','+align+size+')\n\n';
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

var getFileCallback_xpwiki = function (file) {
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
