<?php
// for keep alive
if (! empty($_GET['keepalive'])) exit('0');

@ set_time_limit(120); // just in case it too long, not recommended for production

// needed for case insensitive search to work, due to broken UTF-8 support in PHP
ini_set('mbstring.internal_encoding', 'UTF-8');
ini_set('mbstring.http_input', 'pass');
ini_set('mbstring.http_output', 'pass');

//error_reporting(E_ALL | E_STRICT); // Set E_ALL for debuging

// Add PEAR Dirctory into include path
$incPath = get_include_path();
$addPath = XOOPS_TRUST_PATH . '/PEAR';
if (strpos($incPath, $addPath) === FALSE) {
	set_include_path( $incPath . PATH_SEPARATOR . $addPath );
}

// load compat functions
require_once dirname(__FILE__) . '/include/compat.php';

// Check cToken for protect from CSRF
if (! isset($_SESSION['XELFINDER_CTOKEN'])
		|| ! isset($_REQUEST['ctoken'])
		|| $_SESSION['XELFINDER_CTOKEN'] !== $_REQUEST['ctoken']) {
	exit(json_encode(array('error' => 'errAccess')));
}

define('_MD_ELFINDER_LIB_PATH', XOOPS_TRUST_PATH . '/libs/elfinder');

require _MD_ELFINDER_LIB_PATH . '/php/elFinderConnector.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinder.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeDriver.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeLocalFileSystem.class.php';

//////////////////////////////////////////////////////
// for XOOPS
define('_MD_XELFINDER_NETVOLUME_SESSION_KEY', 'xel_'.$mydirname.'_NetVolumes');

if (! defined('XOOPS_MODULE_PATH')) define('XOOPS_MODULE_PATH', XOOPS_ROOT_PATH . '/modules');
if (! defined('XOOPS_MODULE_URL')) define('XOOPS_MODULE_URL', XOOPS_URL . '/modules');

define('_MD_ELFINDER_MYDIRNAME', $mydirname);
if (empty($_REQUEST['xoopsUrl'])) {
	define('_MD_XELFINDER_SITEURL', XOOPS_URL);
	define('_MD_XELFINDER_MODULE_URL', XOOPS_MODULE_URL);
} else {
	define('_MD_XELFINDER_SITEURL', $_REQUEST['xoopsUrl']);
	define('_MD_XELFINDER_MODULE_URL', str_replace(XOOPS_URL, _MD_XELFINDER_SITEURL, XOOPS_MODULE_URL));
	header('Access-Control-Allow-Origin: ' . _MD_XELFINDER_SITEURL);
}

define('ELFINDER_IMG_PARENT_URL', XOOPS_URL . '/common/elfinder/');

require dirname(__FILE__) . '/class/xelFinder.class.php';
require dirname(__FILE__) . '/class/xelFinderVolumeFTP.class.php';

$extras = array();
$config = $xoopsModuleConfig;
$config_MD5 = md5(serialize($config));
if (strtoupper(_CHARSET) !== 'UTF-8') {
	mb_convert_variables('UTF-8', _CHARSET, $config);
}

// dropbox
if (!empty($config['dropbox_token']) && !empty($config['dropbox_seckey'])) {
	require dirname(__FILE__) . '/class/xelFinderVolumeDropbox.class.php';
	define('ELFINDER_DROPBOX_CONSUMERKEY',    $config['dropbox_token']);
	define('ELFINDER_DROPBOX_CONSUMERSECRET', $config['dropbox_seckey']);
}

$debug = (! empty($config['debug']));

// load xoops_elFinder
include_once dirname(__FILE__).'/class/xoops_elFinder.class.php';
$xoops_elFinder = new xoops_elFinder($mydirname);
$xoops_elFinder->setConfig($config);
$xoops_elFinder->setLogfile($debug? XOOPS_TRUST_PATH . '/cache/elfinder.log.txt' : '');

// Get volumes
if (isset($_SESSION['XELFINDER_RV_'.$mydirname]) && $_SESSION['XELFINDER_CFG_HASH_'.$mydirname] === $config_MD5) {
	$rootVolumes = unserialize(base64_decode($_SESSION['XELFINDER_RV_'.$mydirname]));
} else {
	$isAdmin = false;
	$memberUid = 0;
	$memberGroups = array(XOOPS_GROUP_ANONYMOUS);
	if (is_object($xoopsUser)) {
		if ($xoopsUser->isAdmin($xoopsModule->getVar('mid'))) {
			$isAdmin = true;
		}
		$memberUid = $xoopsUser->getVar('uid');
		$memberGroups = $xoopsUser->getGroups();
	}
	
	// set umask
	foreach(array('default', 'users_dir', 'guest_dir', 'group_dir') as $_key) {
		$config[$_key.'_umask'] = strval(dechex(0xfff - intval(strval($config[$_key.'_item_perm']), 16)));
	}
	
	$inSpecialGroup = (array_intersect($memberGroups, ( isset($config['special_groups'])? $config['special_groups'] : array() )));
	
	// set uploadAllow
	if ($isAdmin) {
		$config['uploadAllow'] = @$config['upload_allow_admin'];
		$config['autoResize'] = @$config['auto_resize_admin'];
	} elseif ($inSpecialGroup) {
		$config['uploadAllow'] = @$config['upload_allow_spgroups'];
		$config['auto_resize'] = @$config['auto_resize_spgroups'];
	} elseif ($memberUid) {
		$config['uploadAllow'] = @$config['upload_allow_user'];
		$config['autoResize'] = @$config['auto_resize_user'];
	} else {
		$config['uploadAllow'] = @$config['upload_allow_guest'];
		$config['autoResize'] = @$config['auto_resize_guest'];
	}
	
	$config['uploadAllow'] = trim($config['uploadAllow']);
	if (! $config['uploadAllow'] || $config['uploadAllow'] === 'none') {
		$config['uploadAllow'] = array();
	} else {
		$config['uploadAllow'] = explode(' ', $config['uploadAllow']);
		$config['uploadAllow'] = array_map('trim', $config['uploadAllow']);
	}
	$config['autoResize'] = (int)$config['autoResize'];
	
	if (! empty($xoopsConfig['cool_uri'])) {
		$config['URL'] = _MD_XELFINDER_SITEURL . '/' . $mydirname . '/view/';
	} else if (empty($config['disable_pathinfo'])) {
		$config['URL'] = _MD_XELFINDER_MODULE_URL . '/' . $mydirname . '/index.php/view/';
	} else {
		$config['URL'] = _MD_XELFINDER_MODULE_URL . '/' . $mydirname . '/index.php?page=view&file=';
	}
	
	if (! isset($extras[$mydirname.':xelfinder_db'])) {
		$extras[$mydirname.':xelfinder_db'] = array();
	}
	foreach (
			array('default_umask', 'use_users_dir', 'users_dir_perm', 'users_dir_umask', 'use_guest_dir', 'guest_dir_perm', 'guest_dir_umask',
					'use_group_dir', 'group_dir_parent', 'group_dir_perm', 'group_dir_umask', 'uploadAllow', 'URL', 'unzip_lang_value')
			as $_extra
	) {
		$extras[$mydirname.':xelfinder_db'][$_extra] = empty($config[$_extra])? '' : $config[$_extra];
	}
	if (! empty($config['autoResize'])) {
		$extras[$mydirname.':xelfinder_db']['plugin']['AutoResize'] = array(
			'enable' => true,
			'maxHeight' => $config['autoResize'],
			'maxWidth' => $config['autoResize']
		);
	}
	
	$rootVolumes = $xoops_elFinder->getRootVolumes($config['volume_setting'], $extras);
	
	// Add net(FTP) volume
	if ($isAdmin && !empty($config['ftp_host']) && !empty($config['ftp_port']) && !empty($config['ftp_user']) && !empty($config['ftp_pass'])) {
		$ftp = array(
			'driver'  => 'FTPx',
			'alias'   => $config['ftp_name'],
			'host'    => $config['ftp_host'],
			'port'    => $config['ftp_port'],
			'path'    => $config['ftp_path'],
			'user'    => $config['ftp_user'],
			'pass'    => $config['ftp_pass'],
			'enable_search' => !empty($config['ftp_search']),
			'is_local'=> true,
			'tmpPath' => XOOPS_MODULE_PATH . '/'.$mydirname.'/cache',
			'utf8fix' => true,
			'defaults' => array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false),
			'attributes' => array(
				array(
					'pattern' => '~/\.~',
					'read' => false,
					'write' => false,
					'hidden' => true,
					'locked' => false
				),
			)
		);
		$rootVolumes[] = $ftp;
	}
	$_SESSION['XELFINDER_RV_'.$mydirname] = base64_encode(serialize($rootVolumes));
	$_SESSION['XELFINDER_CFG_HASH_'.$mydirname] = $config_MD5;
}
foreach($rootVolumes as $rootVolume) {
	if (isset($rootVolume['driverSrc'])) {
		require_once $rootVolume['driverSrc'];
	}
}
//var_dump($rootVolumes);exit;

// End for XOOPS
//////////////////////////////////////////////////////

$opts = array(
	'locale' => 'ja_JP.UTF-8',
	'bind'   => array(
		'*'              => array($xoops_elFinder, 'log'),
		'mkdir.pre mkfile.pre rename.pre' => 'Plugin.Normalizer.cmdPreprocess',
		'upload.presave' => array(
			'Plugin.Normalizer.onUpLoadPreSave',
			'Plugin.AutoResize.onUpLoadPreSave',
			'Plugin.Watermark.onUpLoadPreSave'
		),
	),
	'plugin' => array(
		'AutoResize' => array(
			'enable' => false
		),
		'Watermark' => array(
			'enable' => false
		),
	),
	'debug' => $debug,
	'netVolumesSessionKey' => _MD_XELFINDER_NETVOLUME_SESSION_KEY,
	'roots' => $rootVolumes,
);

if ($debug) {
	error_reporting(E_ALL);
} else {
	error_reporting(0);
}

// clear output buffer
while( ob_get_level() ) {
	if (! @ ob_end_clean()) break;
}

$connector = new elFinderConnector(new xelFinder($opts), true);
$connector->run();


function debug($str) {
	ob_start();
	var_dump($str);
	$str = ob_get_clean();
	file_put_contents(dirname(__FILE__) . '/debug.txt', $str . "\n", FILE_APPEND);
}

