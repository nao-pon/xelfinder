$().ready(function() {

	$().toastmessage( { sticky : true } );

	elFinder.prototype.i18.jp.messages.cmdresize = 'リサイズ';
	elFinder.prototype.i18.jp.messages.btnApply  = '適用';

	elFinder.prototype.i18.jp.messages.ntfperm = 'Changing permission';
	elFinder.prototype.i18.en.messages.cmdperm = 'Chage permission';
	elFinder.prototype.i18.en.messages.newitem = 'New item';
	elFinder.prototype.i18.en.messages.owner   = 'Owner';
	elFinder.prototype.i18.en.messages.group   = 'Group';
	elFinder.prototype.i18.en.messages.guest   = 'Guest';
	elFinder.prototype.i18.en.messages.perm    = 'Permission';
	elFinder.prototype.i18.en.messages.unlock  = 'Unlock';
	elFinder.prototype.i18.en.messages.hidden  = 'Hidden';

	elFinder.prototype.i18.jp.messages.ntfperm = 'アイテム属性を変更';
	elFinder.prototype.i18.jp.messages.cmdperm = '属性変更';
	elFinder.prototype.i18.jp.messages.newitem = '新規アイテム';
	elFinder.prototype.i18.jp.messages.owner   = 'オーナー';
	elFinder.prototype.i18.jp.messages.group   = 'グループ';
	elFinder.prototype.i18.jp.messages.guest   = 'ゲスト';
	elFinder.prototype.i18.jp.messages.perm    = 'パーミッション';
	elFinder.prototype.i18.jp.messages.unlock  = 'ロック解除';
	elFinder.prototype.i18.jp.messages.hidden  = '非表示';

	elFinder.prototype.i18.ja = elFinder.prototype.i18.jp;
	
	$('#elfinder').elfinder({
		lang: 'ja',
		url : myUrl + 'connector.php',
		height: '400',
		getFileCallback : callbackFunc,
		uiOptions : {
			// toolbar configuration
			toolbar : [
				['back', 'forward'],
				// ['reload'],
				// ['home', 'up'],
				['mkdir', 'mkfile', 'upload'],
				['open', 'download', 'getfile'],
				['info', 'perm'],
				['quicklook'],
				['copy', 'cut', 'paste'],
				['rm'],
				['duplicate', 'rename', 'edit', 'resize'],
				['extract', 'archive'],
				['search'],
				['view', 'sort'],
				['help']
			],
			// directories tree options
			tree : {
				// expand current root on init
				openRootOnLoad : true,
				// auto load current dir parents
				syncTree : true
			},
			// navbar options
			navbar : {
				minWidth : 150,
				maxWidth : 500
			}
		},
		commands : [
    		'open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
    		'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
    		'cut', 'paste', 'edit', 'extract', 'archive', 'search', 'info', 'view', 'help', 'resize', 'sort',
    		'perm'
    	],
		commandsOptions : {
			  getfile : {
			    onlyURL : false,
			    multiple : false,
			    folders : false
			  }
		},
		contextmenu : {
			// navbarfolder menu
			navbar : ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info', 'perm'],
			// current directory menu
			cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'sort', '|', 'info', 'perm'],
			// current directory file menu
			files  : ['getfile', '|','open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info', 'perm']
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
	var thumb = '';
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
};

function insertCode(align, thumb, format) {
	$('.toast-item-close').click();
	$('.toast-item').css('background-image','');
	var code = '';
	if (! format) {
		if (thumb && imgThumb) {
			code = '[siteurl='+imgPath+'][siteimg align='+align+']'+imgThumb+'[/siteimg][/siteurl]';
		} else {
			code = '[siteimg align='+align+']'+imgPath+'[/siteimg]';
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
			code = '&ref(site://'+imgPath+','+align+size+');';
		} else {
			code = '#ref(site://'+imgPath+','+align+size+')\n\n';
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
	var thumb = '';
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
};
