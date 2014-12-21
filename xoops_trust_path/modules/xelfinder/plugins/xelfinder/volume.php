<?php
/*
 * Created on 2012/01/20 by nao-pon http://xoops.hypweb.net/
 */

if (is_dir(XOOPS_ROOT_PATH . $path)) {

	$volumeOptions = array(
		'driverSrc'  => dirname(__FILE__) . '/driver.class.php',
		'driver'     => 'XoopsXelfinder',
		'mydirname'  => $mydirname,
		'path'       => XOOPS_ROOT_PATH . $path,
		'URL'        => _MD_XELFINDER_SITEURL . $path,
		'alias'      => $title,
		'tmbURL'     => _MD_XELFINDER_MODULE_URL . '/'.$mydirname.'/cache/tmb/',
		'tmbPath'    => XOOPS_MODULE_PATH . '/'.$mydirname.'/cache/tmb',
		//'tmbSize'    => 140,
		//'tmbCrop'    => false,
		// 'startPath'  => '../files/test',
		// 'deep' => 3,
		// 'separator' => ':',
		'uploadAllow'     => ($isAdmin? array('all') : array('image')),
		// mimetypes not allowed to upload
		'uploadDeny'      => ($isAdmin? array('') : array('all')),
		// order to proccess uploadAllow and uploadDeny options
		'uploadOrder'     => array('deny', 'allow'),
		// regexp or function name to validate new file name
		'acceptedName'    => ($isAdmin? '/^[^\/\\?*:|"<>]*[^.\/\\?*:|"<>]$/' : '/^(?:\w+|\w[\w\s\.\%\-\(\)\[\]]*\.(?:txt|gif|jpeg|jpg|png))$/ui'),
		'defaults' => array('read' => true, 'write' => true)
	);

}
