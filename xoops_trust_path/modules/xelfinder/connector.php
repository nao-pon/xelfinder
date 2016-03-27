<?php

try {

	// for keep alive
	if (! empty($_GET['keepalive']) && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') exit(0);

	@ set_time_limit(120); // just in case it too long, not recommended for production

	// needed for case insensitive search to work, due to broken UTF-8 support in PHP
	ini_set('default_charset', 'UTF-8');
	if (version_compare(PHP_VERSION, '5.6', '<')) {
		ini_set('mbstring.internal_encoding', 'UTF-8');
		ini_set('mbstring.http_input', 'pass');
		ini_set('mbstring.http_output', 'pass');
	} else {
		@ini_set('mbstring.internal_encoding', '');
		@ini_set('mbstring.http_input', '');
		@ini_set('mbstring.http_output', '');
	}

	//error_reporting(E_ALL | E_STRICT); // Set E_ALL for debuging

	// Add PEAR Dirctory into include path
	$incPath = get_include_path();
	$addPath = XOOPS_TRUST_PATH . '/PEAR';
	if (strpos($incPath, $addPath) === FALSE) {
		//set_include_path( $incPath . PATH_SEPARATOR . $addPath );
		set_include_path( $addPath . PATH_SEPARATOR . $incPath );
	}
	define('ELFINDER_DROPBOX_USE_CURL_PUT', true);

	// load compat functions
	require_once dirname(__FILE__) . '/include/compat.php';

	$php54 = version_compare(PHP_VERSION, '5.4', '>=');
	
	// load composer auto loader
	if ($php54) {
		require_once __DIR__ . '/plugins/vendor/autoload.php';
	}

	// HTTP request header origin
	$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';

	// Check cToken for protect from CSRF
	if (! isset($_SESSION['XELFINDER_CTOKEN'])
			|| ! isset($_REQUEST['ctoken'])
			|| $_SESSION['XELFINDER_CTOKEN'] !== $_REQUEST['ctoken']) {
		$origin || (isset($_GET['cmd']) && ($_GET['cmd'] === 'callback' || $_GET['cmd'] === 'netmount')) || (isset($_REQUEST['cmd']) && $_REQUEST['cmd'] === 'file') || exit(json_encode(array('error' => 'errPleaseReload')));
		if ($origin && $_REQUEST['ctoken']) {
			$_SESSION['XELFINDER_CTOKEN'] = $_REQUEST['ctoken'];
		}
	}

	define('_MD_ELFINDER_LIB_PATH', XOOPS_TRUST_PATH . '/libs/elfinder');

	require _MD_ELFINDER_LIB_PATH . '/php/elFinderConnector.class.php';
	require _MD_ELFINDER_LIB_PATH . '/php/elFinder.class.php';
	require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeDriver.class.php';
	require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeLocalFileSystem.class.php';

	//////////////////////////////////////////////////////
	// for XOOPS
	$config = $xoopsModuleConfig;

	$debug = (! empty($config['debug']));
	if ($debug) {
		if (defined('E_STRICT')) {
			error_reporting(E_ALL ^ E_STRICT);
		} else {
			error_reporting(E_ALL);
		}
	} else {
		error_reporting(0);
	}

	$allowOrigins = array_map('trim', preg_split('/\s+/', $config['allow_origins']));
	$allowOrigins[] = preg_replace('#(^https?://[^/]+).*#i', '$1', XOOPS_URL);
	$allowOrigins = array_flip($allowOrigins);

	define('_MD_XELFINDER_NETVOLUME_SESSION_KEY', 'xel_'.$mydirname.'_NetVolumes');

	if (! defined('XOOPS_MODULE_PATH')) define('XOOPS_MODULE_PATH', XOOPS_ROOT_PATH . '/modules');
	if (! defined('XOOPS_MODULE_URL')) define('XOOPS_MODULE_URL', XOOPS_URL . '/modules');

	define('_MD_ELFINDER_MYDIRNAME', $mydirname);

	if (empty($_REQUEST['xoopsUrl']) && !$origin) {
		define('_MD_XELFINDER_SITEURL', XOOPS_URL);
		define('_MD_XELFINDER_MODULE_URL', XOOPS_MODULE_URL);
	} else {
		if (!$origin
		 || !isset($allowOrigins[$origin])
		 || (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])
		 		 && !in_array(strtoupper($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']), array('POST', 'GET', 'OPTIOINS')))
		) {
			exit(json_encode(array('error' => 'errAccess')));
		}
		define('_MD_XELFINDER_SITEURL', empty($_REQUEST['xoopsUrl'])? XOOPS_URL : $_REQUEST['xoopsUrl']);
		define('_MD_XELFINDER_MODULE_URL', str_replace(XOOPS_URL, _MD_XELFINDER_SITEURL, XOOPS_MODULE_URL));
		header('Access-Control-Allow-Origin: ' . $origin);
		!isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])
		 || header('Access-Control-Allow-Methods: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 1000');
		if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
			header('Access-Control-Allow-Headers: '
					. $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
		} else {
			header('Access-Control-Allow-Headers: *');
		}

		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' || ! empty($_GET['keepalive'])) exit(0);
	}

	define('ELFINDER_IMG_PARENT_URL', XOOPS_URL . '/common/elfinder/');

	require dirname(__FILE__) . '/class/xelFinder.class.php';
	require dirname(__FILE__) . '/class/xelFinderVolumeFTP.class.php';

	$extras = array();
	if (strtoupper(_CHARSET) !== 'UTF-8') {
		mb_convert_variables('UTF-8', _CHARSET, $config);
	}
	$config_MD5 = md5(json_encode($config));

	// google drive
	if ($php54 && !empty($config['googleapi_id']) && !empty($config['googleapi_secret'])) {
		require dirname(__FILE__) . '/class/xelFinderFlysystemGoogleDriveNetmount.php';
		define('ELFINDER_GOOGLEDRIVE_CLIENTID',     $config['googleapi_id']);
		define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', $config['googleapi_secret']);
	}

	// dropbox
	if (!empty($config['dropbox_token']) && !empty($config['dropbox_seckey'])) {
		require dirname(__FILE__) . '/class/xelFinderVolumeDropbox.class.php';
		define('ELFINDER_DROPBOX_CONSUMERKEY',    $config['dropbox_token']);
		define('ELFINDER_DROPBOX_CONSUMERSECRET', $config['dropbox_seckey']);
	}

	// load xoops_elFinder
	include_once dirname(__FILE__).'/class/xoops_elFinder.class.php';
	$xoops_elFinder = new xoops_elFinder($mydirname);
	$xoops_elFinder->setConfig($config);
	$xoops_elFinder->setLogfile($debug? XOOPS_TRUST_PATH . '/cache/elfinder.log.txt' : '');

	// Access control
	include_once dirname(__FILE__).'/class/xelFinderAccess.class.php';

	// get user roll
	$userRoll = $xoops_elFinder->getUserRoll();
	$isAdmin = $userRoll['isAdmin'];

	// Get volumes
	if (isset($_SESSION['XELFINDER_RF_'.$mydirname]) && $_SESSION['XELFINDER_CFG_HASH_'.$mydirname] === $config_MD5) {
		$rootConfig = unserialize(base64_decode($_SESSION['XELFINDER_RF_'.$mydirname]));
	} else {
		$memberUid = $userRoll['uid'];
		$memberGroups = $userRoll['mygids'];
		$inSpecialGroup = $userRoll['inSpecialGroup'];
		
		// set umask
		foreach(array('default', 'users_dir', 'guest_dir', 'group_dir') as $_key) {
			$config[$_key.'_umask'] = strval(dechex(0xfff - intval(strval($config[$_key.'_item_perm']), 16)));
		}
		
		// set uploadAllow
		if ($isAdmin) {
			$config['uploadAllow'] = @$config['upload_allow_admin'];
			$config['autoResize'] = @$config['auto_resize_admin'];
			$config['uploadMaxSize'] = @$config['upload_max_admin'];
		} elseif ($inSpecialGroup) {
			$config['uploadAllow'] = @$config['upload_allow_spgroups'];
			$config['auto_resize'] = @$config['auto_resize_spgroups'];
			$config['uploadMaxSize'] = @$config['upload_max_spgroups'];
		} elseif ($memberUid) {
			$config['uploadAllow'] = @$config['upload_allow_user'];
			$config['autoResize'] = @$config['auto_resize_user'];
			$config['uploadMaxSize'] = @$config['upload_max_user'];
		} else {
			$config['uploadAllow'] = @$config['upload_allow_guest'];
			$config['autoResize'] = @$config['auto_resize_guest'];
			$config['uploadMaxSize'] = @$config['upload_max_guest'];
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
						'use_group_dir', 'group_dir_parent', 'group_dir_perm', 'group_dir_umask', 'uploadAllow', 'uploadMaxSize', 'URL', 'unzip_lang_value')
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
		
		$rootConfig = $xoops_elFinder->getRootVolumeConfigs($config['volume_setting'], $extras);
		
		// Add net(FTP) volume
		if ($isAdmin && !empty($config['ftp_host']) && !empty($config['ftp_port']) && !empty($config['ftp_user']) && !empty($config['ftp_pass'])) {
			$ftp = array(
				'driver'  => 'FTPx',
				'id'      => 'ad',
				'alias'   => $config['ftp_name'],
				'host'    => $config['ftp_host'],
				'port'    => $config['ftp_port'],
				'path'    => $config['ftp_path'],
				'user'    => $config['ftp_user'],
				'pass'    => $config['ftp_pass'],
				'disabled'=> !empty($config['ftp_search'])? array() : array('search'),
				'statOwner' => true,
				'allowChmodReadOnly' => true,
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
			$rootConfig[] = array('raw' => $ftp);
		}
		if (defined('ELFINDER_DROPBOX_CONSUMERKEY') && $config['dropbox_path'] && $config['dropbox_acc_token'] && $config['dropbox_acc_seckey']) {
			$dropbox_access = null;
			$dropboxIsInGroup = (array_intersect($memberGroups, ( isset($config['dropbox_writable_groups'])? $config['dropbox_writable_groups'] : array() )));
			if (!$isAdmin) {
				$dropbox_access = new xelFinderAccess();
				if (isset($config['dropbox_hidden_ext']))
					$dropbox_access->setHiddenExtention($config['dropbox_hidden_ext']);
				if (isset($config['dropbox_write_ext']))
					$dropbox_access->setWriteExtention($dropboxIsInGroup? $config['dropbox_write_ext'] : '');
				if (isset($config['dropbox_unlock_ext']))
					$dropbox_access->setUnlockExtention($dropboxIsInGroup? $config['dropbox_unlock_ext'] : '');
			}
			$dropbox = array(
				'driver'            => 'DropboxX',
				'id'                => 'sh',
				'consumerKey'       => ELFINDER_DROPBOX_CONSUMERKEY,
				'consumerSecret'    => ELFINDER_DROPBOX_CONSUMERSECRET,
				'alias'             => trim($config['dropbox_name']),
				'accessToken'       => trim($config['dropbox_acc_token']),
				'accessTokenSecret' => trim($config['dropbox_acc_seckey']),
				'path'              => '/'.trim($config['dropbox_path'], ' /'),
				'defaults' => array('read' => true, 'write' => ($dropboxIsInGroup? true : false), 'hidden' => false, 'locked' => false),
				'accessControl'     => is_object($dropbox_access)? array($dropbox_access, 'access') : null,
				'uploadDeny'        => (!$isAdmin && !empty($config['dropbox_upload_mime']))? array('all') : array(),
				'uploadAllow'       => (!$isAdmin && !empty($config['dropbox_upload_mime']))? array_map('trim', explode(',', $config['dropbox_upload_mime'])) : array(),
				'uploadOrder'       => array('deny', 'allow'),
			);
			$rootConfig[] = array('raw' => $dropbox);
		}
		
		try {
			if ($serVar = @serialize($rootConfig)) {
				$_SESSION['XELFINDER_RF_'.$mydirname] = base64_encode($serVar);
				$_SESSION['XELFINDER_CFG_HASH_'.$mydirname] = $config_MD5;
			}
		} catch (Exception $e) {}
	}

	$rootVolumes = $xoops_elFinder->buildRootVolumes($rootConfig);
	foreach($rootVolumes as $rootVolume) {
		if (isset($rootVolume['driverSrc'])) {
			require_once $rootVolume['driverSrc'];
		}
	}
	//var_dump($rootVolumes);exit;

	// custom session handler
	require dirname(__FILE__) . '/class/xelFinderSession.class.php';

	// End for XOOPS
	//////////////////////////////////////////////////////

	$opts = array(
		'isAdmin' => $isAdmin, // for class xelFinder
		'locale' => 'ja_JP.UTF-8',
		'session' => new xelFinderSession(array(
			'base64encode' => $xoops_elFinder->base64encodeSessionData,
			'keys' => array(
				'default'   => 'xel_'.$mydirname.'_Caches',
				'netvolume' => _MD_XELFINDER_NETVOLUME_SESSION_KEY
			)
		)),
		'bind'   => array(
			//'*' => array($xoops_elFinder, 'log'),
			'netmount.pre' => array($xoops_elFinder, 'netmountPreCallback'),
			'netmount' => array($xoops_elFinder, 'netmountCallback'),
			'mkdir mkfile put upload extract' => array($xoops_elFinder, 'notifyMail'),
			'mkdir mkfile put paste upload extract resize' => array($xoops_elFinder, 'changeAddParent'),
			'upload.pre mkdir.pre mkfile.pre rename.pre archive.pre' => array(
				'Plugin.Sanitizer.cmdPreprocess',
				'Plugin.Normalizer.cmdPreprocess'
			),
			'upload.presave' => array(
				'Plugin.Sanitizer.onUpLoadPreSave',
				'Plugin.Normalizer.onUpLoadPreSave',
				array($xoops_elFinder, 'autoRotateOnUpLoadPreSave'),
				'Plugin.AutoResize.onUpLoadPreSave',
				'Plugin.Watermark.onUpLoadPreSave'
			),
		),
		'plugin' => array(
			//'Sanitizer' => array(
			//	'enable' => true,
			//),
			'AutoResize' => array(
				'enable' => false
			),
			'Watermark' => array(
				'enable' => false
			),
		),
		'debug' => $debug,
		'uploadTempPath' => XOOPS_TRUST_PATH . '/cache',
		'roots' => $rootVolumes,
		'callbackWindowURL' => !empty($_REQUEST['myUrl'])? ($_REQUEST['myUrl'] . 'connector.php?cmd=callback') : ''
	);

	// clear output buffer
	while( ob_get_level() ) {
		if (! @ ob_end_clean()) break;
	}

	$connector = new elFinderConnector(new xelFinder($opts), $debug);
	$connector->run();
} catch (Exception $e) {
	exit(json_encode(array('error' => $e->getMessage())));
}


function debug() {
	$args = func_get_args();
	ob_start();
	foreach($args as $arg) {
		//debug_print_backtrace();
		var_dump($arg);
	}
	$str = ob_get_clean();
	file_put_contents(dirname(__FILE__) . '/debug.txt', $str . "\n", FILE_APPEND);
}

