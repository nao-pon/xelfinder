<?php
// compatibility for PHP < 5.2

if(!function_exists('error_get_last')) {
	function error_get_last() {
		return array(
				'type' => 0,
				'message' => $GLOBALS[php_errormsg],
				'file' => 'unknonw',
				'line' => 0,
		);
	}
}

// json support
if (! extension_loaded('json')) {
	require_once 'Services/JSON.php';
	if (!function_exists('json_decode')){
		function json_decode($content, $assoc=false) {
			$json = $assoc?new Services_JSON(SERVICES_JSON_LOOSE_TYPE):new Services_JSON;
			return $json->decode($content);
		}
	}
	if (!function_exists('json_encode')){
		function json_encode($content){
			$json = new Services_JSON;
			return $json->encode($content);
		}
	}
}

if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir()
	{
		if (!empty($_ENV['TMP'])) {
			return realpath($_ENV['TMP']);
		}
	
		if (!empty($_ENV['TMPDIR'])) {
			return realpath( $_ENV['TMPDIR']);
		}
	
		if (!empty($_ENV['TEMP'])) {
			return realpath( $_ENV['TEMP']);
		}
	
		$tempfile = tempnam(uniqid(rand(),TRUE),'');
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}