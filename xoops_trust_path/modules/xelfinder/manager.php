<?php
if (! defined('XOOPS_TRUST_PATH')) exit;

$target = isset($_GET['target'])? (preg_match('/^[a-zA-Z0-9_:.-]+$/', $_GET['target'])? $_GET['target'] : '') : '';

$callback = isset($_GET['cb'])? (preg_match('/^[a-zA-Z0-9_]+$/', $_GET['cb'])? $_GET['cb'] : '') : 'bbcode';
$callback = 'getFileCallback_' . $callback;

$myurl = XOOPS_URL . '/modules/' . $mydirname;

// $config & $extras for test
$elfurl = XOOPS_URL . '/common/elfinder';
include $mydirpath . '/test.conf.php';

$managerJs = '';
$_plugin_dir = dirname(__FILE__) . '/plugins/';
$_js_cache_files = array();
foreach(explode(',', $config['volume_setting']) as $_vol) {
	list(, $_plugin) = explode(':', $_vol);
	$_plugin = trim($_plugin);
	$_js = $_plugin_dir . $_plugin . '/manager.js';
	if (is_file($_js)) {
		$_js_cache_files[$_plugin] = filemtime($_js);
		$_js_cache_path[$_plugin] = $_js;
	}
}
if ($_js_cache_files) {
	ksort($_js_cache_files);
	$_plugins = array_keys($_js_cache_files);
	$_managerJs = '/cache/' . join(',', $_plugins) . '_manager.js';
	$_js_cacahe = $mydirpath . $_managerJs;
	if (! is_file($_js_cacahe) || filemtime($_js_cacahe) < max($_js_cache_files)) {
		$_src = '';
		foreach($_plugins as $_plugin) {
			$_src .= file_get_contents($_js_cache_path[$_plugin]);
		}
		file_put_contents($_js_cacahe, $_src);
	}
	$managerJs = '<script type="text/javascript" src="'.$myurl.$_managerJs.'" charset="utf-8"></script>' . "\n";
}

while(ob_get_level()) {
	if (! ob_end_clean()) break;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Image Manager - elFinder 2.0</title>

		<!-- jQuery and jQuery UI (REQUIRED) -->
		<link rel="stylesheet" type="text/css" media="screen" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/smoothness/jquery-ui.css">
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js"></script>

		<!-- elFinder CSS (REQUIRED) -->
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $elfurl ?>/css/elfinder.full.css">
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $elfurl ?>/css/theme.css">

		<!-- elFinder JS (REQUIRED) -->
		<script type="text/javascript" src="<?php echo $elfurl ?>/js/elfinder.min.js"></script>

		<!-- elFinder translation (OPTIONAL) -->
		<script type="text/javascript" src="<?php echo $elfurl ?>/js/i18n/elfinder.jp.js" charset="utf-8"></script>

		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $myurl ?>/include/toastmessage/css/jquery.toastmessage.css">
		<script type="text/javascript" src="<?php echo $myurl ?>/include/toastmessage/jquery.toastmessage.js" charset="utf-8"></script>

		<!-- elFinder initialization (REQUIRED) -->
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $myurl ?>/include/css/manager.css">
		<script type="text/javascript" charset="utf-8">
			var target = '<?php echo $target ?>';
			var rootUrl = '<?php echo XOOPS_URL ?>';
			var myUrl = rootUrl + '/modules/<?php echo $mydirname?>/';
			var imgUrl = myUrl + 'images/';
			var imgThumb = '';
			var imgPath = '';
		</script>
		<script type="text/javascript" src="<?php echo $myurl ?>/include/js/manager.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
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
