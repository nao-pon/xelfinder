<?php
/*
 * Created on 2012/01/20 by nao-pon http://xoops.hypweb.net/
 * $Id: volume.php,v 1.1 2012/01/20 13:32:02 nao-pon Exp $
 */

if (is_dir(XOOPS_TRUST_PATH . $path)) {

	require dirname(__FILE__) . '/driver.class.php';

	$volumeOptions = array(
		'driver'    => 'XoopsXelfinder_db',
		'mydirname' => $mydirname,
		'path'      => '1',
		'filePath'  => XOOPS_TRUST_PATH . $path . rawurlencode(substr(XOOPS_URL, 7)) . '_' . $mydirname . '_',
		'URL'       => XOOPS_URL . '/modules/' . $mydirname . '/index.php/view/',
		'alias'     => $title,
		'tmbURL'     => XOOPS_URL . '/modules/'.$mydirname.'/cache/tmb/',
		'tmbPath'    => XOOPS_ROOT_PATH . '/modules/'.$mydirname.'/cache/tmb',
		'quarantine' => XOOPS_ROOT_PATH . '/modules/'.$mydirname.'/cache/tmb/.quarantine',
		'tmbSize'    => 140,
		'tmbCrop'    => false,
		// 'startPath'  => '../files/test',
		// 'deep' => 3,
		// 'separator' => ':',
		// mimetypes allowed to upload
		'uploadAllow'     => array('image'),
		// regexp or function name to validate new file name
		'acceptedName'    => '/^(?:[\w\s]+|\w[\w\s\.\%\-\(\)\[\]]*\.(?:txt|gif|jpeg|jpg|png))$/ui',
		'defaults' => array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false)
	);

}
