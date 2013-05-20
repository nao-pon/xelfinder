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

if (!empty($config['ssl_connector_url']) && preg_match('/Firefox|Chrome|Safari/', $_SERVER['HTTP_USER_AGENT'])) {
	$conector_url = $config['ssl_connector_url'];
	$session_name = session_name();
} else {
	$session_name = $conector_url = '';
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

$viewport = (preg_match('/Mobile/i', $_SERVER['HTTP_USER_AGENT']))? '<meta name="viewport" content="width=device-width" />' : '';

$userLang = xelfinder_detect_lang();

$jQueryCDN = '//ajax.googleapis.com/ajax/libs/jquery/%s/jquery.min.js';
$jQueryUICDN = '//ajax.googleapis.com/ajax/libs/jqueryui/%s';
$jQueryVersion   = '1.9.1';
$jQueryUIVersion = '1.10.1';

if (! $jQueryUiTheme = @$config['jquery_ui_theme']) {
	$jQueryUiTheme = 'base';
}
if (! preg_match('#^(?:https?:)?//#i', $jQueryUiTheme)) {
	$jQueryUiTheme = sprintf($jQueryUICDN, $jQueryUIVersion) . '/themes/'.$jQueryUiTheme.'/jquery-ui.css';
}

while(ob_get_level() && @ob_end_clean()) {}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Image Manager - X-elFinder (elFinder 2.0 for XOOPS)</title>
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

		<script src="<?php echo sprintf($jQueryCDN, $jQueryVersion)?>"></script>
		<script src="<?php echo sprintf($jQueryUICDN, $jQueryUIVersion)?>/jquery-ui.min.js"></script>

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

		<link rel="stylesheet" href="<?php echo XOOPS_URL ?>/common/js/toastmessage/css/jquery.toastmessage.css" type="text/css">
		<script src="<?php echo XOOPS_URL ?>/common/js/toastmessage/jquery.toastmessage.js"></script>

		<!-- elFinder initialization (REQUIRED) -->
		<link rel="stylesheet" href="<?php echo $myurl ?>/include/css/manager.css" type="text/css">
		<script type="text/javascript">
			var target = '<?php echo $target ?>';
			var rootUrl = '<?php echo XOOPS_URL ?>';
			var moduleUrl = '<?php echo XOOPS_MODULE_URL ?>';
			var myUrl = moduleUrl + '/<?php echo $mydirname?>/';
			var imgUrl = myUrl + 'images/';
			var connectorUrl = '<?php echo $conector_url?>';
			var sessionName = '<?php echo $session_name?>';
			var useSiteImg = <?php echo $siteimg ?>;
			var imgThumb = '';
			var itemPath = '';
			var itemObject = [];
			var defaultTmbSize = <?php echo $default_tmbsize?>;
			var lang = '<?php echo $userLang?>';
			var adminMode = <?php echo $admin?>;
			var cToken = '<?php echo $cToken?>';
		</script>
		<script src="<?php echo $myurl ?>/include/js/commands/perm.js"></script>
		<script src="<?php echo $myurl ?>/include/js/manager.js" charset="UTF-8"></script>
		<script type="text/javascript" charset="UTF-8">
			var callbackFunc = <?php echo $callback ?>;
			setInterval(function(){
				jQuery.ajax({url:"<?php echo $myurl ?>/connector.php?keepalive=1",cache:false});
			}, 300000); // keep alive interval 5min
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
