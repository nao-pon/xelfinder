<?php
if (! empty ( $_POST ['doupdate'] )) {
	global $xoopsConfig;
	while( ob_get_level() && @ ob_end_clean() );
	header('X-Accel-Buffering: no');
?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="<?php echo _CHARSET ?>"> 
	</head>
	<body>
<?php
	echo '<p>'.xelfinderAdminLang ( 'COMPOSER_UPDATE_STARTED' ).'</p>';
	
	while ( @ob_end_flush() );
	flush ();
	$pluginsDir = dirname (__DIR__) . '/plugins';
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
	
	$phpcli = !empty($_POST['phpcli'])? trim($_POST['phpcli']) : 'php';
	$php54 = !empty($_POST['php54']);
	$cmds = [];
	$cmds[] = $phpcli.' -d curl.cainfo=cacert.pem -d openssl.cafile=cacert.pem composer.phar self-update --no-ansi --no-interaction 2>&1';
	if ($php54) {
	    $cmds[] = $phpcli.' -d curl.cainfo=cacert.pem -d openssl.cafile=cacert.pem composer.phar remove --no-update kunalvarma05/dropbox-php-sdk';
	} else {
	    $cmds[] = $phpcli.' -d curl.cainfo=cacert.pem -d openssl.cafile=cacert.pem composer.phar require --no-update kunalvarma05/dropbox-php-sdk';
	}
	$cmds[] = $phpcli.' -d curl.cainfo=cacert.pem -d openssl.cafile=cacert.pem composer.phar update  --no-ansi --no-interaction --prefer-dist --no-dev 2>&1';
	//$cmds = array(
	//	$phpcli.' composer.phar info --no-ansi --no-interaction 2>&1',
	//);
	foreach($cmds as $cmd) {
		$res = '';
		$handle = popen($cmd, 'r');
		while (false !== $res && $handle && !feof($handle)) {
			if ($res = fgets($handle, 80)) {
				echo $res . '<br>';
				flush ();
			}
		}
		pclose($handle);
	}
	
	chdir ( $cwd );
	
	echo '<p>'.xelfinderAdminLang ( 'COMPOSER_DONE_UPDATE' ).'</p>';
	echo '</body></html>';
	
	exit ();
}
xoops_cp_header ();
include __DIR__ . '/mymenu.php';

echo '<h3>' . xelfinderAdminLang ( 'COMPOSER_UPDATE' ) . '</h3>';

$php54up = false;

if ($php54up = version_compare(PHP_VERSION, '5.4.0', '>=')) {
	if (preg_match('/^(\d\.\d)/', PHP_VERSION, $m)) {
		$curver = $m[1];
	} else {
		$curver = '5.4';
	}
	$curverDig = str_replace('.', '', $curver);
?>
<div>
	<form action="./index.php?page=vendorup" method="post" id="xelfinder_vendorup_f"
		target="composer_update">
		<table><tr>
			<td>
				<p>PHP CLI Command<br><label><input value="php" type="radio" name="cli" checked="checked">Default is "php"</label></p>
				<p><input type="text" name="phpcli" value="php" /></p>
			</td>
			<td>
				<dl>
					<dt>Customized example</dt>
					<dd><label><input value="/usr/local/php<?php echo $curver; ?>/bin/php" type="radio" name="cli">lolipop - "/usr/local/php<?php echo $curver; ?>/bin/php"</label></dd>
					<dd><label><input value="/usr/local/bin/php<?php echo $curverDig; ?>cli" type="radio" name="cli">XREA/CoreServer/ValueServer - "/usr/local/bin/php<?php echo $curverDig; ?>cli"</label></dd>
					<dd><label><input value="/opt/php-<?php echo PHP_VERSION; ?>/bin/php" type="radio" name="cli">XSERVER - "/opt/php-<?php echo PHP_VERSION; ?>/bin/php"</label></dd>
				</dl>
			</td>
		</tr></table>
		<p>
		<input type="submit" name="doupdate" id="xelfinder_vendorup_s"
			value="<?php echo xelfinderAdminLang('COMPOSER_DO_UPDATE'); ?>" />
		<input type="hidden" name="php54" value="<?php echo '5.4' === $curver ? '1' : '0'; ?>" />
		</p>
	</form>
</div>
<hr>
<iframe id="ifm-xelfinder-vendorup" name="composer_update" scrolling="no"
	style="border: none; width: 100%; height: 300px;"></iframe>

<script>
(function($){
	var autoHeight = function() {
		var innH = jQuery("#ifm-xelfinder-vendorup").contents().find('body').outerHeight(true);
		var boxH = jQuery("#ifm-xelfinder-vendorup").height();
		if (boxH < innH) {
			jQuery("#ifm-xelfinder-vendorup").height(innH + 50);
		}
		setTimeout(autoHeight, 500);
	};
	autoHeight();
	$('#xelfinder_vendorup_f').on('submit', function(e) {
		setTimeout(function() {
			$('#xelfinder_vendorup_s').replaceWith($('<p>').html("<?php echo xelfinderAdminLang('COMPOSER_UPDATE_STARTED'); ?>"));
		}, 100);
	})
	.find('input[type=radio]').on('change', function(e) {
		$('#xelfinder_vendorup_f').find('input[type=text]').val($(this).val());
	});
})(jQuery);
</script>

<?php
} else {
?>
<p>vendor update needs for PHP >= 5.4 . Your PHP version is <?php echo PHP_VERSION; ?> .</p>
<?php
}
xoops_cp_footer ();
