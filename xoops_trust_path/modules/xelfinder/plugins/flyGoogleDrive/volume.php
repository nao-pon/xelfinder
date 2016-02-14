<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;
use League\Flysystem\Cached\Storage\Memcached as MCache;
use League\Flysystem\Cached\Storage\Adapter as ACache;
use Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter;
use Google_Client;
use Google_Service_Drive;

if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
	$_err = false;
	foreach(array('ext_token') as $_key) {
		if (! isset($extOptions[$_key])) {
			$_err = true;
		}
	}
	
	if (! $_err) {
		$_token = @json_decode($extOptions['ext_token'], true);
		if ($_token && !empty($_token['client_id']) && !empty($_token['client_secret']) && !empty($_token['refresh_token'])) {
			$path = '/' . trim($path, '/');
			
			$_expire = isset($extOptions['ext_cache_expire'])?: 0;
			if ($_expire) {
				$_cacheKey = $mDirname.'_'.md5(XOOPS_URL . $_token . $path);
				
				$_cache = null;
				if (class_exists('Memcached')) {
					$memcached = new Memcached();
					if ($memcached->addServer(! empty($extOptions['ext_mcache_host'])?: 'localhost', ! empty($extOptions['ext_mcache_port'])?: 11211)) {
						$_cache = new MCache($memcached, $_cacheKey, $_expire);
					}
				}
				
				if (! $_cache && is_writable(XOOPS_TRUST_PATH.'/cache')) {
					$_cache = new ACache(new Local(XOOPS_TRUST_PATH.'/cache'), $_cacheKey, $_expire);
				}
			}

			$client = new Google_Client();
			$client->setClientId($_token['client_id']);
			$client->setClientSecret($_token['client_secret']);
			$client->refreshToken($_token['refresh_token']);

			$service = new Google_Service_Drive($client);
			$_gdrive = new GoogleDriveAdapter($service, $path, [ 'setHasDirOnGetItems' => true ]);
			
			if ($_cache) {
				// use storage cache with `ext_cache_expire`
				$_fly = new Filesystem(new CachedAdapter($_gdrive, $_cache));
			} else {
				// use memory cache
				$_fly = new Filesystem(new CachedAdapter($_gdrive, new CacheStore()));
			}
			
			$volumeOptions = array (
				'driver' => 'Flysystem',
				'filesystem' => $_fly,
				'alias' => $title,
				'icon' => XOOPS_MODULE_URL . '/' . $mDirname . '/images/volume_icon_googledrive.png',
				'tmbPath' => XOOPS_MODULE_PATH . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/',
				'tmbURL' => _MD_XELFINDER_MODULE_URL . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/'
			);
		}
	}
}

