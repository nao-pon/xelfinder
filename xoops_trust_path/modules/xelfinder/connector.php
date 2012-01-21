<?php

set_time_limit(120); // just in case it too long, not recommended for production
ini_set('max_file_uploads', 50);   // allow uploading up to 50 files at once

// needed for case insensitive search to work, due to broken UTF-8 support in PHP
ini_set('mbstring.internal_encoding', 'UTF-8');
ini_set('mbstring.func_overload', 2);

//if (function_exists('date_default_timezone_set')) {
//	date_default_timezone_set('Europe/Moscow');
//}

error_reporting(E_ALL | E_STRICT); // Set E_ALL for debuging

define('_MD_ELFINDER_LIB_PATH', XOOPS_TRUST_PATH . '/libs/elfinder');

require _MD_ELFINDER_LIB_PATH . '/php/elFinderConnector.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinder.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeDriver.class.php';
require _MD_ELFINDER_LIB_PATH . '/php/elFinderVolumeLocalFileSystem.class.php';

//////////////////////////////////////////////////////
// for XOOPS

define('_MD_ELFINDER_MYDIRNAME', $mydirname);

$isAdmin = false;
if (is_object($xoopsUser)) {
	if ($xoopsUser->isAdmin()) {
		$isAdmin = true;
	}
}


// $config & $extras for test
include $mydirpath . '/test.conf.php';


// load xoops_elFinder
include_once dirname(__FILE__).'/class/xoops_elFinder.class.php';
$xoops_elFinder = new xoops_elFinder();

// Get volumes
$rootVolumes = $xoops_elFinder->getRootVolumes($config['volume_setting'], $extras);

// End for XOOPS
//////////////////////////////////////////////////////


function debug($o) {
	echo '<pre>';
	print_r($o);
}

/**
 * Simple logger function.
 * Demonstrate how to work with elFinder event api.
 *
 * @package elFinder
 * @author Dmitry (dio) Levashov
 **/
class elFinderSimpleLogger {

	/**
	 * Log file path
	 *
	 * @var string
	 **/
	protected $file = '';

	/**
	 * constructor
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct($path) {
		$this->file = $path;
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir);
		}
	}

	/**
	 * Create log record
	 *
	 * @param  string   $cmd       command name
	 * @param  array    $result    command result
	 * @param  array    $args      command arguments from client
	 * @param  elFinder $elfinder  elFinder instance
	 * @return void|true
	 * @author Dmitry (dio) Levashov
	 **/
	public function log($cmd, $result, $args, $elfinder) {
		$log = $cmd.' ['.date('d.m H:s')."]\n";

		if (!empty($result['error'])) {
			$log .= "\tERROR: ".implode(' ', $result['error'])."\n";
		}

		if (!empty($result['warning'])) {
			$log .= "\tWARNING: ".implode(' ', $result['warning'])."\n";
		}

		if (!empty($result['removed'])) {
			foreach ($result['removed'] as $file) {
				// removed file contain additional field "realpath"
				$log .= "\tREMOVED: ".$file['realpath']."\n";
			}
		}

		if (!empty($result['added'])) {
			foreach ($result['added'] as $file) {
				$log .= "\tADDED: ".$elfinder->realpath($file['hash'])."\n";
			}
		}

		if (!empty($result['changed'])) {
			foreach ($result['changed'] as $file) {
				$log .= "\tCHANGED: ".$elfinder->realpath($file['hash'])."\n";
			}
		}

		$this->write($log);
	}

	/**
	 * Write log into file
	 *
	 * @param  string  $log  log record
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function write($log) {

		if (($fp = @fopen($this->file, 'a'))) {
			fwrite($fp, $log."\n");
			fclose($fp);
		}
	}


} // END class

$logger = new elFinderSimpleLogger(XOOPS_TRUST_PATH . '/cache/elfinder.log.txt');


$opts = array(
	'locale' => 'ja_JP.UTF-8',
	'bind' => array(
		'mkdir mkfile rename duplicate upload rm paste' => array($logger, 'log'),
	),
	'debug' => true,

	'roots' => $rootVolumes,
);


header('Access-Control-Allow-Origin: *');
$connector = new elFinderConnector(new elFinder($opts), true);
$connector->run();
