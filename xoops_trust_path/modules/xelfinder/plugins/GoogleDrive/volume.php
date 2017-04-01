<?php
/*
 * Created on 2017/04/01 by nao-pon http://xoops.hypweb.net/
 */

if (version_compare(PHP_VERSION, '5.4.0', '>=') && ! empty($extOptions['ext_token'])) {
	$_token = json_decode($extOptions['ext_token'], true);
	if (! empty($_token['refresh_token'])) {
		$volumeOptions = array(
			'driver'        => 'GoogleDrive',
			'alias'         => $title,
			'path'          => trim($path, ' /'),
			'defaults'      => array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false),
			'client_id'     => isset($_token['client_id'])? $_token['client_id'] : '',
			'client_secret' => isset($_token['client_secret'])? $_token['client_secret'] : '',
			'refresh_token' => $_token['refresh_token'],
			'tmpPath'       => XOOPS_MODULE_PATH.'/'._MD_ELFINDER_MYDIRNAME.'/cache',
		);
	}

}
