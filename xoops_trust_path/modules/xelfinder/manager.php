<?php
if (! defined('XOOPS_TRUST_PATH')) exit;
if (! defined('XOOPS_MODULE_PATH')) define('XOOPS_MODULE_PATH', XOOPS_ROOT_PATH . '/modules');
if (! defined('XOOPS_MODULE_URL')) define('XOOPS_MODULE_URL', XOOPS_URL . '/modules');

$target = isset($_GET['target'])? (preg_match('/^[a-zA-Z0-9_:.-]+$/', $_GET['target'])? $_GET['target'] : '') : '';

$callback = isset($_GET['cb'])? (preg_match('/^[a-zA-Z0-9_]+$/', $_GET['cb'])? $_GET['cb'] : '') : 'bbcode';
$callback = 'getFileCallback_' . $callback;

$siteimg = (empty($_GET['si']) && empty($use_bbcode_siteimg))? 0 : 1;

$admin = (isset($_GET['admin']))? 1 : 0;

$myurl = XOOPS_MODULE_URL . '/' . $mydirname;
$elfurl = XOOPS_URL . '/common/elfinder';
$modules_basename = trim(str_replace(XOOPS_URL, '', XOOPS_MODULE_URL), '/');

$module_handler =& xoops_gethandler('module');
$xelfinderModule = $module_handler->getByDirname($mydirname);
$config_handler =& xoops_gethandler('config');
$config = $config_handler->getConfigsByCat(0, $xelfinderModule->getVar('mid'));

// load xoops_elFinder
include_once dirname(__FILE__).'/class/xoops_elFinder.class.php';
$xoops_elFinder = new xoops_elFinder($mydirname);
$xoops_elFinder->setConfig($config);

// make cmds array as json
$disabledCmds = $xoops_elFinder->getDisablesCmds($admin);
$cmds = array('open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
			'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
			'cut', 'paste', 'edit', 'extract', 'archive', 'search', 'info', 'view', 'help',
			'resize', 'sort', 'netmount', 'netunmount', 'pixlr', 'perm');
$cmds = array_values(array_diff($cmds, $disabledCmds));
$cmds = json_encode($cmds);

$conector_url = $conn_is_ext = '';
if (!empty($config['connector_url'])) {
	$conector_url = $config['connector_url'];
	!$config['conn_url_is_ext'] || $conn_is_ext = 1;
}
$managerJs = '';
$_plugin_dir = dirname(__FILE__) . '/plugins/';
$_js_cache_path = $_js_cache_times = array();
foreach(explode("\n", $config['volume_setting']) as $_vol) {
	$_vol = trim($_vol);
	if (! $_vol || $_vol[0] === '#') continue;
	list(, $_plugin, $_dirname) = explode(':', $_vol);
	$_plugin = trim($_plugin);
	if (preg_match('#(?:uploads|'.$modules_basename.')/([^/]+)#i', trim($_dirname), $_match)) {
		$_dirname = $_match[1];
	} else {
		$_dirname = $_plugin;
	}
	$_key = ($_dirname !== $_plugin)? ($_dirname.'!'.$_plugin) : $_dirname;
	$_js = $_plugin_dir . $_plugin . '/manager.js';
	if (is_file($_js)) {
		$_js_cache_times[$_key] = filemtime($_js);
		$_js_cache_path[$_key] = $_js;
	}
}
if ($_js_cache_path) {
	ksort($_js_cache_path);
	$_keys = array_keys($_js_cache_path);
	$_managerJs = '/cache/' . join(',', $_keys) . '_manager.js';
	$_js_cacahe = $mydirpath . $_managerJs;
	if (! is_file($_js_cacahe) || filemtime($_js_cacahe) < max($_js_cache_times)) {
		$_src = '';
		foreach($_keys as $_key) {
			list($_dirname) = explode('!', $_key);
			$_src .= str_replace('$dirname', $_dirname, file_get_contents($_js_cache_path[$_key]));
		}
		file_put_contents($_js_cacahe, $_src);
	}
	$managerJs = '<script src="'.$myurl.$_managerJs.'" charset="UTF-8"></script>' . "\n";
}

$default_tmbsize = isset($config['thumbnail_size'])? (int)$config['thumbnail_size'] : '160';
$debug = (! empty($config['debug']));
// cToken uses for CSRF protection
$cToken = md5(session_id() . XOOPS_ROOT_PATH . (defined(XOOPS_SALT)? XOOPS_SALT : XOOPS_DB_PASS));
$_SESSION['XELFINDER_CTOKEN'] = $cToken;

$viewport = (preg_match('/Mobile/i', $_SERVER['HTTP_USER_AGENT']))? '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2" />' : '';

$userLang = xelfinder_detect_lang();

if (empty($config['jquery'])) {
	$jQueryVersion   = '1.11.0';
	$jQueryCDN = '//ajax.googleapis.com/ajax/libs/jquery/%s/jquery.min.js';
	$jQueryUrl = sprintf($jQueryCDN, $jQueryVersion);
} else {
	$jQueryUrl = trim($config['jquery']);
}

if (empty($config['jquery_ui'])) {
	$jQueryUIVersion = '1.10.3';
	$jQueryUICDN = '//ajax.googleapis.com/ajax/libs/jqueryui/%s';
	$jQueryUIUrl = sprintf($jQueryUICDN, $jQueryUIVersion).'/jquery-ui.min.js';
} else {
	$jQueryUIUrl = trim($config['jquery_ui']);
}

if (empty($config['jquery_ui_css'])) {
	if (! $jQueryUiTheme = @$config['jquery_ui_theme']) {
		$jQueryUiTheme = 'smoothness';
	} else {
		if ($jQueryUiTheme === 'base' && version_compare($jQueryUIVersion, '1.10.1', '>')) {
			$jQueryUiTheme = 'smoothness';
		}
	}
	if (! preg_match('#^(?:https?:)?//#i', $jQueryUiTheme)) {
		$jQueryUiTheme = sprintf($jQueryUICDN, $jQueryUIVersion) . '/themes/'.$jQueryUiTheme.'/jquery-ui.min.css';
	}
} else {
	$jQueryUiTheme = trim($config['jquery_ui_css']);
}

$title = mb_convert_encoding($config['manager_title'], 'UTF-8', _CHARSET);

$useCKEditor = (is_file(XOOPS_ROOT_PATH.'/modules/ckeditor4/ckeditor/ckeditor.js'));

while(ob_get_level() && @ob_end_clean()) {}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?php echo $title?></title>
		<?php echo $viewport ?>

		<link rel="stylesheet" href="<?php echo $jQueryUiTheme?>" type="text/css">

<?php if ($debug) {?>
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/common.css"      type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/dialog.css"      type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/toolbar.css"     type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/navbar.css"      type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/statusbar.css"   type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/contextmenu.css" type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/cwd.css"         type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/quicklook.css"   type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/commands.css"    type="text/css" >
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/fonts.css"       type="text/css" >
<?php } else {?>
		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/elfinder.min.css" type="text/css" >
<?php }?>

		<link rel="stylesheet" href="<?php echo $elfurl ?>/css/theme.css"       type="text/css" >

		<script src="<?php echo $jQueryUrl?>"></script>
		<script src="<?php echo $jQueryUIUrl?>"></script>

		<?php if ($debug) {?>
		<!-- elfinder core -->
		<script src="<?php echo $elfurl ?>/js/elFinder.js"></script>
		<script src="<?php echo $elfurl ?>/js/elFinder.version.js"></script>
		<script src="<?php echo $elfurl ?>/js/jquery.elfinder.js"></script>
		<script src="<?php echo $elfurl ?>/js/elFinder.resources.js"></script>
		<script src="<?php echo $elfurl ?>/js/elFinder.options.js"></script>
		<script src="<?php echo $elfurl ?>/js/elFinder.history.js"></script>
		<script src="<?php echo $elfurl ?>/js/elFinder.command.js"></script>
	
		<!-- elfinder ui -->
		<script src="<?php echo $elfurl ?>/js/ui/overlay.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/workzone.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/navbar.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/dialog.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/tree.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/cwd.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/toolbar.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/button.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/uploadButton.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/viewbutton.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/searchbutton.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/sortbutton.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/panel.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/contextmenu.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/path.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/stat.js"></script>
		<script src="<?php echo $elfurl ?>/js/ui/places.js"></script>
	
		<!-- elfinder commands -->
		<script src="<?php echo $elfurl ?>/js/commands/back.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/forward.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/reload.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/up.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/home.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/copy.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/cut.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/paste.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/open.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/rm.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/info.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/duplicate.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/rename.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/help.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/getfile.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/mkdir.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/mkfile.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/upload.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/download.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/edit.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/quicklook.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/quicklook.plugins.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/extract.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/archive.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/search.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/view.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/resize.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/sort.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/netmount.js"></script>
		<script src="<?php echo $elfurl ?>/js/commands/pixlr.js"></script>
	
		<!-- elfinder languages -->
		<script src="<?php echo $elfurl ?>/js/i18n/elfinder.en.js" charset="UTF-8"></script>
	
		<!-- elfinder dialog -->
		<script src="<?php echo $elfurl ?>/js/jquery.dialogelfinder.js"></script>
<?php } else {?>
		<script src="<?php echo $elfurl ?>/js/elfinder.min.js"></script>
<?php }?>
		<script src="<?php echo $elfurl ?>/js/i18n/elfinder.<?php echo $userLang?>.js" charset="UTF-8"></script>
<?php if ($useCKEditor) { ?>
		<script src="<?php echo XOOPS_URL ?>/modules/ckeditor4/ckeditor/ckeditor.js" type="text/javascript"></script>
<?php }?>
		
		<!-- elFinder initialization (REQUIRED) -->
		<link rel="stylesheet" href="<?php echo $myurl ?>/include/css/manager.css" type="text/css">
		<script type="text/javascript">
			var target = '<?php echo $target ?>';
			var rootUrl = '<?php echo XOOPS_URL ?>';
			var moduleUrl = '<?php echo XOOPS_MODULE_URL ?>';
			var myUrl = moduleUrl + '/<?php echo $mydirname?>/';
			var imgUrl = myUrl + 'images/';
			var connectorUrl = '<?php echo $conector_url?>';
			var connIsExt = <?php echo (int)$conn_is_ext?>;
			var useSiteImg = <?php echo $siteimg ?>;
			var imgThumb = '';
			var itemPath = '';
			var itemObject = [];
			var defaultTmbSize = <?php echo $default_tmbsize?>;
			var lang = '<?php echo $userLang?>';
			var adminMode = <?php echo $admin?>;
			var cToken = '<?php echo $cToken?>';
<?php if ($useCKEditor) {?>
			var editorTextHtml = {
					mimes : ['text/html'],
					load : function(textarea) {
						CKEDITOR.replace( textarea.id, {
							fullPage: true,
							allowedContent: true
						});
					},
					close : function(textarea, instance) {
						CKEDITOR.instances[textarea.id].destroy();
					},
					save : function(textarea, editor) {
						textarea.value = CKEDITOR.instances[textarea.id].getData();
					}
				};
<?php } else {?>
			var editorTextHtml = {};
<?php }?>
			var elfinderCmds = <?php echo $cmds?>;
		</script>
		<script src="<?php echo $myurl ?>/include/js/commands/perm.js"></script>
		<script src="<?php echo $myurl ?>/include/js/manager.js" charset="UTF-8"></script>
		<script type="text/javascript" charset="UTF-8">
			var callbackFunc = <?php echo $callback ?>;
		</script>
		<?php echo $managerJs ?>
	</head>
	<body>
		<!-- Element where elFinder will be created (REQUIRED) -->
		<div id="elfinder"></div>
	</body>
</html>
<?php exit();

function xelfinder_detect_lang() {
	$replaser = array(
		'ja'    => 'jp',
		'ja_JP' => 'jp'
	);
	if ($accept = @ $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
		if (preg_match_all("/([\w_-]+)/i",$accept,$match,PREG_PATTERN_ORDER)) {
			foreach($match[1] as $lang) {
				list($l, $c) = array_pad(preg_split('/[_-]/', $lang), 2, '');
				$lang = strtolower($l);
				if ($c) {
					$lang .= '_' . strtoupper($c);
				}
				if (isset($replaser[$lang])) {
					$lang = $replaser[$lang];
				}
				if (is_file( XOOPS_ROOT_PATH.'/common/elfinder/js/i18n/elfinder.'.$lang.'.js')) {
					return $lang;
				} else if (is_file( XOOPS_ROOT_PATH.'/common/elfinder/js/i18n/elfinder.'.substr($lang, 0, 2).'.js')) {
					return substr($lang, 0, 2);
				}
			}
		}
	}
	return 'en';
}
