if (window.parent) {
	$('.simplemodal-wrap', window.parent.document).css({overflow:'hidden'});
}
$(document).ready(function() {
	var mes_en = elFinder.prototype.i18.en.messages;
	mes_en.ntfperm = 'Changing permission';
	mes_en.cmdperm = 'Chage permission';
	mes_en.newitem = 'New item';
	mes_en.owner   = 'Owner';
	mes_en.group   = 'Group';
	mes_en.guest   = 'Guest';
	mes_en.perm    = 'Permission';
	mes_en.unlock  = 'Unlock';
	mes_en.hidden  = 'Hidden';
	mes_en.targetgroups  = 'Target groups';
	mes_en.mimeserach    = 'MIME type Serach';
	mes_en.nowrap        = 'No wrap';
	mes_en.wraparound    = 'Wrap around';
	mes_en.inline        = 'Inline';
	mes_en.fullsize      = 'Full Size';
	mes_en.thumbnail     = 'Thumbnail';
	mes_en.continues     = 'Continue more';
	mes_en.imageinsert   = 'Image insert options';
	mes_en.CannotUploadOldIE = '<p>Your browser "IE" cannot upload by this manager.</p><p>Please use the newest browser, when you upload files.</p>';
	mes_en.errPleaseReload = 'Not found access token.<br />Please reload on browser, or re-open popup window.';

	if (typeof elFinder.prototype.i18.jp != "undefined") {
		mes_jp = elFinder.prototype.i18.jp.messages;
		mes_jp.ntfperm = 'アイテム属性を変更';
		mes_jp.cmdperm = '属性変更';
		mes_jp.newitem = '新規アイテム';
		mes_jp.owner   = 'オーナー';
		mes_jp.group   = 'グループ';
		mes_jp.guest   = 'ゲスト';
		mes_jp.perm    = 'パーミッション';
		mes_jp.unlock  = 'ロック解除';
		mes_jp.hidden  = '非表示';
		mes_jp.targetgroups  = '対象グループ';
		mes_jp.mimeserach    = 'MIMEタイプで検索';
		mes_jp.nowrap        = '回り込みなし';
		mes_jp.wraparound    = '回り込みあり';
		mes_jp.inline        = 'インライン';
		mes_jp.fullsize      = 'フルサイズ';
		mes_jp.thumbnail     = 'サムネイル';
		mes_jp.continues     = 'さらに続ける';
		mes_jp.imageinsert   = '画像挿入オプション';
		mes_jp.CannotUploadOldIE = '<p>あなたがお使いの IE ブラウザでは、このマネージャーではファイルをアップロードすることができません。</p><p>ファイルをアップロードする場合は、最新のブラウザをご利用下さい。</p>';
		mes_jp.errPleaseReload = '接続に必要なトークンが見つかりません。<br />ブラウザでリロードするかポップアップウィンドウを開きなおしてください。';
		mes_jp.errAccessPleaseReload = '接続に必要なトークンが見つかりません。<br />ブラウザでリロードするかポップアップウィンドウを開きなおしてください。';

		elFinder.prototype.i18.ja = elFinder.prototype.i18.jp;
	}
	
	// keep alive
	var extCheck = connectorUrl;
	setInterval(function(){
		jQuery.ajax({url:myUrl+"/connector.php?keepalive=1",cache:false});
		if (extCheck) {
			jQuery.ajax({url:extCheck+"?keepalive=1",cache:false,xhrFields:{withCredentials:true}});
		}
	}, 300000); // keep alive interval 5min
	
	var customData = { admin : adminMode, ctoken : cToken };
	var cors = false;
	var IElt10;
	if (! connectorUrl) {
		connectorUrl = myUrl + 'connector.php';
	} else {
		cors = true;
		customData.myUrl = myUrl;
		if (! connIsExt) {
			customData.xoopsUrl = rootUrl;
		}
		if (typeof document.uniqueID != 'undefined') {
			(function(){
				var xhr = new XMLHttpRequest();
				if (!('withCredentials' in xhr)) {
					jQuery('<script>').attr('src', myUrl+'/include/js/xdr/jquery.xdr.js').appendTo('head');
					IElt10 = true;
				}
				xhr = null;
			})();
		}
	}
	
	var elfinderInstance = $('#elfinder').elfinder({
		lang: lang,
		url : connectorUrl,
		urlUpload : (cors && connIsExt)? connectorUrl : myUrl + 'connector.php',
		customData : customData,
		customHeaders: cors? {'X-Requested-With' : 'XMLHttpRequest'} : {},
		xhrFields: cors? {withCredentials: true} : {},
		requestType : 'POST',
		height: $(window).height() - 20,
		getFileCallback : callbackFunc,
		ui : ['toolbar', 'places', 'tree', 'path', 'stat'],
		uiOptions : {
			// toolbar configuration
			toolbar : [
				['back', 'forward'],
				['netmount'],
				// ['reload'],
				// ['home', 'up'],
				['mkdir', 'mkfile', 'upload'],
				['open', 'download', 'getfile'],
				['info', 'perm'],
				['quicklook'],
				['copy', 'cut', 'paste'],
				['rm'],
				['duplicate', 'rename', 'edit', 'resize', 'pixlr'],
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
		commands : elfinderCmds,
		commandsOptions : {
			  getfile : {
			    onlyURL : false,
			    multiple : false,
			    folders : false
			  },
			  edit : {
			      mimes : ['text/plain', 'text/html', 'text/javascript'],
			      editors : [ editorTextHtml ],
			      dialogWidth: '80%'
			  }
		},
		contextmenu : {
			// navbarfolder menu
			navbar : ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'places', 'info', 'perm', 'netunmount'],
			// current directory menu
			cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'sort', '|', 'info', 'perm'],
			// current directory file menu
			files  : ['getfile', '|','open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename', 'resize', 'pixlr',
			          '|', 'archive', 'extract',
			          '|', 'places', 'info', 'perm']
		}
	}).elfinder('instance');
	
	// set document.title dynamically etc.
	var title = document.title;
	elfinderInstance.bind('open', function(event) {
		var data = event.data || null;
		var path = '';
		
		if (data) {
			if (data.init && IElt10) {
				var dialog = $('<div class="elfinder-dialog-resize"/>');
				dialog.append(elfinderInstance.i18n('CannotUploadOldIE'));
				var buttons = {};
				buttons[elfinderInstance.i18n('btnYes')] = function() { dialog.elfinderdialog('close'); };
				elfinderInstance.dialog(dialog, {
						title : elfinderInstance.i18n('cmdupload'),
						width : '400px',
						buttons: buttons,
						destroyOnClose : true,
						modal : true
					});
			}
			
			if (data.cwd) {
				path = elfinderInstance.path(data.cwd.hash) || null;
			}
			document.title =  path? path + ':' + title : title;
		}
	});

	// fit to window.height on window.resize
	var resizeTimer = null;
	$(window).resize(function() {
		resizeTimer && clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function() {
			var h = parseInt($(window).height()) - 20;
			if (h != parseInt($('#elfinder').height())) {
				elfinderInstance.resize('100%', h);
			}
		}, 200);
	});

});

$.extend({
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
			if (!!document.uniqueID) { // IE
				var r;
				if (typeof o.caretPos == 'object') {
					r = o.caretPos;
				} else {
					r = document.selection.createRange();
				}
				r.text = v;
				r.select();
			} else {
				var s = o.value;
				var p = o.selectionStart;
				var np = p + v.length;
				o.value = s.substr(0, p) + v + s.substr(p);
				o.setSelectionRange(np, np);
			}
			if (! $.insertAtCaret.continue_finder) {
				try {
					pa.jQuery.modal.close();
				} catch(e) {
					window.close();
				}
			}
		}
	},
	openImgInsertDialog: function(buttons, img, fm) {
		var opts  = {
			title : fm.i18n('imageinsert'),
			width : 'auto',
			destroyOnClose : true,
			modal : true
		};
		$.openImgInsertDialog.dialog = fm.dialog('<div class="image-inserter-item" style="background-image:url(\''+img+'\')">'+buttons+'</div>', opts);
		$.openImgInsertDialog.dialog.id = 'ImgInsertDialog';
		$.openImgInsertDialog.parent = $.openImgInsertDialog.dialog.parent();
	}
});

function insertCode(align, thumb) {
	var code = '';
	var size = '';
	var isImg = (itemObject.mime.match(/^image/));
	var urlTag = 'siteurl';
	var imgTag = useSiteImg? 'siteimg' : 'img';
	var format = insertCode.format;
	if (isImg && $('#resize_px')) {
		size = $('#resize_px').val();
		if (size && (! size.match(/[\d]{1,4}/) || (!!insertCode.iSize && insertCode.iSize <= size))) {
			size = '';
		}
	}
	$.insertAtCaret.continue_finder = $("#continue_finder:checked").val()? true : false;

	try {
		if ($.openImgInsertDialog.dialog) {
			$.openImgInsertDialog.dialog.elfinderdialog('close');
			$.openImgInsertDialog.dialog = null;
		}
	} catch(e) {}

	insertCode.iSize = null;
	insertCode.format = null;
	if (! format) {
		if (itemPath.match(/^http/)) {
			urlTag = 'url';
		}
		if (isImg) {
			if (imgThumb.match(/_tmbsize_/)) {
				if (size) {
					imgThumb = imgThumb.replace('_tmbsize_', size);
				} else {
					imgThumb = '';
				}
			}
			if (thumb && imgThumb) {
				code = '['+urlTag+'='+itemPath+']['+imgTag+' align='+align+']'+ (useSiteImg? '' : rootUrl+'/') + imgThumb + '[/'+imgTag+'][/'+urlTag+']';
			} else {
				if (itemPath.match(/^http/)) {
					imgTag = 'img';
					code = '['+imgTag+' align='+align+']' + itemPath + '[/'+imgTag+']';
				} else {
					code = '['+imgTag+' align='+align+']' + (useSiteImg? '' : rootUrl+'/') + itemPath + '[/'+imgTag+']';
				}
			}
		} else {
			code = '['+urlTag+'='+itemPath+']'+itemObject.name+'[/'+urlTag+']';
		}
	} else if (format == 'xpwiki') {
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
		
		if (! itemPath.match(/^http/)) {
			itemPath = 'site://' + itemPath;
		}
		
		if (isImg) {
			if (size) {
				size = ',mw:'+size+',mh:'+size;
			}
			var orgAlign = align;
			if (align) {
				align = ',' + align;
			}
			if (thumb || o.tagName != 'TEXTAREA' || o.className.match(/\bnorich\b/)) {
				code = '&ref('+itemPath+align+size+');';
				if (!thumb) {
					code += '&clear';
					if (orgAlign == 'left' || orgAlign == 'right') {
						code += '('+orgAlign+')';
					}
					code += ';';
				}
			} else {
				code = '\n#ref('+itemPath+align+size+')\n';
			}
		} else {
			code = '[['+itemObject.name+':'+itemPath+']]';
		}
	}
	$.insertAtCaret(code);
}

function encodeDecodeURI(str) {
	var ret;
	try {
		ret = encodeURI(decodeURI(str));
	} catch (e) {
		ret = str;
	}
	return ret;
}

function getModuleName(file) {
	var modules_basename = moduleUrl.replace(rootUrl, '').replace(/\//g, '');
	var reg = new RegExp('^'+rootUrl.replace(/([.*+?^=!:${}()|[\]\/\\])/g, "\\$1")+'\/(?:(?:'+modules_basename+'|uploads)\/)?([^\/]+)\/.*$');
	var module = file.url.replace(reg, '$1');
	return module;
}

var getFileCallback_bbcode = function (file, fm) {
	if (!target || !file.read) {
		fm.exec('open');
		return;
	}
	var path = file.url.replace(rootUrl+'/', '');
	var basename = path.replace( /^.*\//, '' );
	var module =getModuleName(file);
	var thumb = '';
	var isImg = (file.mime.match(/^image/))? true : false;
	if (isImg && module.match(/^[a-zA-Z0-9_-]+$/)) {
		eval('if (typeof get_thumb_'+module+' == "function" ){' +
			'thumb = get_thumb_'+module+'(basename, file);}' );
	}
	imgThumb = encodeDecodeURI(thumb);
	itemPath = encodeDecodeURI(path);
	itemObject = file;

	if (isImg) {
		var buttons = '<span onclick="insertCode(\'left\',1);"><img src="'+imgUrl+'alignleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',1)"><img src="'+imgUrl+'aligncenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',1)"><img src="'+imgUrl+'alignright.gif" alt="" /></span>'
					+ '<br>'
					+ '<span onclick="insertCode(\'left\',0);"><img src="'+imgUrl+'alignbigleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',0)"><img src="'+imgUrl+'alignbigcenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',0)"><img src="'+imgUrl+'alignbigright.gif" alt="" /></span>'
					+ '<br>'
					+ '<span class="file_info">'+fm.i18n('size')+': ' + file.width + 'x' + file.height+'</span>';
		if (file.url.match(/\bview\b/)) {
			insertCode.iSize = Math.max(file.width, file.height);
			var tsize = Math.min(insertCode.iSize, defaultTmbSize);
			buttons += '<br>'
					+ '<span class="file_info">'+fm.i18n('resize')+':<input id="resize_px" style="width: 2.5em" class="button_input" value="'+tsize+'">px</span>';
		}
		var continue_checked = (! $.insertAtCaret.continue_finder)? '' : ' checked="checked"';
		buttons += '<br>'
				+ '<span class="file_info"><input id="continue_finder" class="button_input" type="checkbox" value="1"'+continue_checked+'><label for="continue_finder">'+fm.i18n('continues')+'</label></span>';

		$.openImgInsertDialog(buttons, file.url, fm);
	} else {
		insertCode('',0);
	}
};

var getFileCallback_xpwiki = function (file, fm) {
	if (!target || !file.read) {
		fm.exec('open');
		return;
	}
	var path = file.url.replace(rootUrl+'/', '');
	if (file._localalias && file.alias.charAt(0) == 'R') {
		path = file.alias.replace('R/', '');
	}
	var basename = path.replace( /^.*\//, '' );
	var module =getModuleName(file);
	var thumb = '';
	var isImg = (file.mime.match(/^image/))? true : false;
	if (isImg && module.match(/^[a-zA-Z0-9_-]+$/)) {
		eval('if (typeof get_thumb_'+module+' == "function" ){' +
			'thumb = get_thumb_'+module+'(basename, file);}' );
	}
	imgThumb = encodeDecodeURI(thumb);
	itemPath = encodeDecodeURI(path);
	itemObject = file;
	
	if (itemPath.match(/\?/) && ! itemPath.match(/\.[^.?]+$/)) {
		itemPath += '&' + encodeURI(file.name);
	}
	
	insertCode.format = 'xpwiki';
	if (isImg) {
		var nowrap = ' title="' + fm.i18n('nowrap') + '"';
		var wraparound = ' title="' + fm.i18n('wraparound') + '"';
		var inline = ' title="' + fm.i18n('inline') + '"';
		insertCode.iSize = Math.max(file.width, file.height);
		var tsize = Math.min(insertCode.iSize, defaultTmbSize);
		var buttons = '<span onclick="insertCode(\'left\',1);"'+wraparound+'><img src="'+imgUrl+'alignleft.gif" alt="" /></span> <span onclick="insertCode(\'\',1)"'+inline+'><img src="'+imgUrl+'aligncenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',1)"'+wraparound+'><img src="'+imgUrl+'alignright.gif" alt="" /></span>'
					+ '<br>'
					+ '<span onclick="insertCode(\'left\',0);"'+nowrap+'><img src="'+imgUrl+'alignbigleft.gif" alt="" /></span> <span onclick="insertCode(\'center\',0)"'+nowrap+'><img src="'+imgUrl+'alignbigcenter.gif" alt="" /></span> <span onclick="insertCode(\'right\',0)"'+nowrap+'><img src="'+imgUrl+'alignbigright.gif" alt="" /></span>'
					+ '<br>'
					+ '<span class="file_info">'+fm.i18n('size')+': ' + file.width + 'x' + file.height+'</span>'
					+ '<br>'
					+ '<span class="file_info">'+fm.i18n('resize')+':<input id="resize_px" style="width: 2.5em" class="button_input" value="'+tsize+'">px</span>';
		var continue_checked = (! $.insertAtCaret.continue_finder)? '' : ' checked="checked"';
		buttons += '<br>'
				+ '<span class="file_info"><input id="continue_finder" class="button_input" type="checkbox" value="1"'+continue_checked+'><label for="continue_finder">'+fm.i18n('continues')+'</label></span>';
		$.openImgInsertDialog(buttons, file.url, fm);
	} else {
		insertCode('',0);
	}
};

var getFileCallback_xpwikifck = function (file, fm) {
	var pa = null;
	var x = null;
	try {
		pa = window.opener;
		x = pa.XpWiki;
	} catch(e) {
		try {
			pa = window.parent;
			x = pa.XpWiki;
		} catch(e) {}
	}
	if (x) {
		var path = file.url.replace(rootUrl+'/', '');
		path = encodeDecodeURI(path);
		if (! path.match(/^http/)) {
			path = 'site://' + path;
		}
		x.FCKrefInsert(path);
	}
	try {
		pa.jQuery.modal.close();
	} catch(e) {
		window.close();
	}
};

// for FCKEditor
// Url: '[XOOPS_URL]/modules/xelfinder/manager.php?cb=fckeditor'
var getFileCallback_fckeditor = function (file, fm) {
	window.opener.SetUrl(file.url) ;
	window.close();
};

// for CKEditor
// Url: '[XOOPS_URL]/modules/xelfinder/manager.php?cb=ckeditor'
function tmbFunc_ckeditor(tmb){
	if ($('#resize_px')) {
		var size = $('#resize_px').val();
		if (size && ! size.match(/[\d]{1,4}/)) {
			size = '';
		}
		if (tmb.match(/_tmbsize_/)) {
			if (size) {
				tmb = tmb.replace('_tmbsize_', size);
			} else {
				tmb = false;
			}
		}
	}
	return tmb;
}
var getFileCallback_ckeditor = function (file, fm) {
	var path = encodeDecodeURI(file.url);
	var basename = path.replace( /^.*\//, '' );
	var module = getModuleName(file);
	var thumb = '';
	var isImg = (file.mime.match(/^image/))? true : false;
	if (isImg && module.match(/^[a-zA-Z0-9_-]+$/)) {
		eval('if (typeof get_thumb_'+module+' == "function" ){' +
			'thumb = get_thumb_'+module+'(basename, file);}' );
	}
	var funcNum = window.location.search.replace(/^.*CKEditorFuncNum=(\d+).*$/, "$1");
	var localHostReg = new RegExp('^' + window.location.protocol + '//' + window.location.host);
	path = path.replace(localHostReg, '');
	if (thumb) {
		thumb = rootUrl+'/'+encodeDecodeURI(thumb);
		thumb = thumb.replace(localHostReg, '');
		var fullsize = ' title="' + fm.i18n('fullsize') + '"';
		var thumbnail = ' title="' + fm.i18n('thumbnail') + '"';
		var buttons = '<span'+thumbnail+' onclick="var tmb=(tmbFunc_ckeditor(\''+thumb.replace("'", "%27")+'\')||\''+path.replace("'", "%27")+'\');window.opener.CKEDITOR.tools.callFunction(\''+funcNum+'\',tmb);var dialog=window.opener.CKEDITOR.dialog.getCurrent();dialog.setValueOf(\'Link\',\'txtUrl\',\''+path.replace("'", "%27")+'\');window.close();"><img src="'+imgUrl+'alignleft.gif" alt="" /></span>'
		+ ' &nbsp; '
		+ '<span'+fullsize+' onclick="window.opener.CKEDITOR.tools.callFunction(\''+funcNum+'\', \''+path.replace("'", "%27")+'\');window.close();"><img src="'+imgUrl+'alignbigleft.gif" alt="" /></span>'
		+ '<br><span class="file_info">'+fm.i18n('size')+': ' + file.width + 'x' + file.height+'</span>';
		if (file.url.match(/\bview\b/)) {
			insertCode.iSize = Math.max(file.width, file.height);
			var tsize = Math.min(insertCode.iSize, defaultTmbSize);
			buttons += '<br>'
					+ '<span class="file_info">'+fm.i18n('resize')+':<input id="resize_px" style="width: 2.5em" class="button_input" value="'+tsize+'">px</span>';
		}
		$.openImgInsertDialog(buttons, path, fm);
		
	} else {
		window.opener.CKEDITOR.tools.callFunction(funcNum, path);
		window.close();
	}
};

// for tinyMCE
// Url: '[XOOPS_URL]/modules/xelfinder/manager.php?cb=tinymce'
var getFileCallback_tinymce = function (file, fm) {
	window.tinymceFileWin.document.forms[0].elements[window.tinymceFileField].value = file.url;
	window.tinymceFileWin.focus();
	window.close();
};

