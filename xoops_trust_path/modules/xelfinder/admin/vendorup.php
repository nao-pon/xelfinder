<?php
if (! empty ( $_POST ['doupdate'] )) {
	global $xoopsConfig;
	
?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="<?php echo _CHARSET ?>"> 
	</head>
	<body>
<?php
	echo '<p>'.xelfinderAdminLang ( 'COMPOSER_UPDATE_STARTED' ).'</p>';
	
	while ( @ob_end_flush () );
	flush ();
	$pluginsDir = dirname ( dirname ( __FILE__ ) ) . '/plugins';
	$cwd = getcwd ();
	chdir ( $pluginsDir );
	
	$locale = '';
	switch($xoopsConfig['language']) {
		case 'ja_utf8' :
			$locale = 'ja_JP.utf8';
			break;
		case 'japanese' :
			$locale = 'ja_JP.eucjp';
			break;
		case 'english' :
			$locale = 'en_US.iso88591';
			break;
	}
	if ($locale) {
		setlocale(LC_ALL, $locale);
		putenv('LC_ALL='.$locale);
	}
	putenv ( 'COMPOSER_HOME=' . $pluginsDir . '/.composer' );
	
	$handle = popen('./composer_update', 'r');
	while (!feof($handle)) {
		if ($res = fgets($handle, 80)) {
			echo $res . '<br />';
			flush ();
		}
	}
	pclose($handle);
	
	chdir ( $cwd );
	
	echo '<p>'.xelfinderAdminLang ( 'COMPOSER_DONE_UPDATE' ).'</p>';
	echo '</body></html>';
	
	exit ();
}
xoops_cp_header ();
include dirname ( __FILE__ ) . '/mymenu.php';

echo '<h3>' . xelfinderAdminLang ( 'COMPOSER_UPDATE' ) . '</h3>';
?>
<div>
	<form action="./index.php?page=vendorup" method="post"
		target="composer_update">
		<input type="submit" name="doupdate"
			value="<?php echo xelfinderAdminLang('COMPOSER_DO_UPDATE'); ?>" />
	</form>
</div>
<hr>
<iframe id="ifm-xelfinder-vendorup" name="composer_update" scrolling="no"
	style="border: none; width: 100%; height: 300px;"></iframe>

<script>
(function($){
	var autoHeight = function() {
		jQuery("#ifm-xelfinder-vendorup").height(jQuery("#ifm-xelfinder-vendorup").contents().find('body').outerHeight(true)+50);
		setTimeout(autoHeight, 500);
	};
	autoHeight();
})(jQuery);
</script>

<?php
xoops_cp_footer ();
