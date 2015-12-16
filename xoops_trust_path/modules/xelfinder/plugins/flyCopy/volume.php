<?php
use Barracuda\Copy\API;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memcached as MCache;
use League\Flysystem\Cached\Storage\Adapter as ACache;
use League\Flysystem\Copy\CopyAdapter;

if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
	$_err = false;
	foreach(array('ext_consumerKey', 'ext_consumerSecret', 'ext_accessToken', 'ext_tokenSecret') as $_key) {
		if (! isset($extOptions[$_key])) {
			$_err = true;
		}
	}
	
	if (! $_err) {
		$path = '/' . trim($path, '/');
		$_expire = isset($extOptions['ext_cache_expire'])?: 600;
		$_cacheKey = $mDirname.'_'.md5(XOOPS_URL . $extOptions['ext_consumerKey'].$extOptions['ext_consumerSecret'].$extOptions['ext_accessToken'].$extOptions['ext_tokenSecret'].$path);
		
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
		
		$_copy = new CopyAdapter(new API($extOptions['ext_consumerKey'], $extOptions['ext_consumerSecret'], $extOptions['ext_accessToken'], $extOptions['ext_tokenSecret']), $path);
		if ($_cache) {
			$_fly = new Filesystem(new CachedAdapter($_copy, $_cache));
		} else {
			$_fly = new Filesystem($_copy);
		}
		
		$volumeOptions = array (
			'driver' => 'Flysystem',
			//'autoload' => true,
			'filesystem' => $_fly,
			'alias' => $title,
			'icon' => XOOPS_MODULE_URL . '/' . $mDirname . '/images/volume_icon_copy.png',
			'tmbPath' => XOOPS_MODULE_PATH . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/',
			'tmbURL' => _MD_XELFINDER_MODULE_URL . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/'
		);
	}
}
