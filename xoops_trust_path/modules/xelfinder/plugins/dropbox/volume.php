<?php
/*
 * Created on 2014/03/12 by nao-pon http://xoops.hypweb.net/
 */

if (defined('ELFINDER_DROPBOX_CONSUMERKEY') && $mConfig['dropbox_acc_token'] && $mConfig['dropbox_acc_seckey']) {

	$volumeOptions = array(
		'driverSrc' => dirname(dirname(dirname(__FILE__))) . '/class/xelFinderVolumeDropbox.class.php',
		'driver'    => 'DropboxX',
		'alias'     => $title,
		'path'      => '/'.trim($path, ' /'),
		'defaults'  => array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false),
		'consumerKey'       => ELFINDER_DROPBOX_CONSUMERKEY,
		'consumerSecret'    => ELFINDER_DROPBOX_CONSUMERSECRET,
		'accessToken'       => trim($mConfig['dropbox_acc_token']),
		'accessTokenSecret' => trim($mConfig['dropbox_acc_seckey'])
	);

}
