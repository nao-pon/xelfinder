<?php

define('ELFINDER_PHP_ROOT_PATH', dirname(__FILE__));

function elFinderAutoloader($name)
{
    $map = [
        'elFinder' => 'elFinder.class.php',
        'elFinderConnector' => 'elFinderConnector.class.php',
        'elFinderEditor' => 'editors/editor.php',
        'elFinderLibGdBmp' => 'libs/GdBmp.php',
        'elFinderPlugin' => 'elFinderPlugin.php',
        'elFinderPluginAutoResize' => 'plugins/AutoResize/plugin.php',
        'elFinderPluginAutoRotate' => 'plugins/AutoRotate/plugin.php',
        'elFinderPluginNormalizer' => 'plugins/Normalizer/plugin.php',
        'elFinderPluginSanitizer' => 'plugins/Sanitizer/plugin.php',
        'elFinderPluginWatermark' => 'plugins/Watermark/plugin.php',
        'elFinderSession' => 'elFinderSession.php',
        'elFinderSessionInterface' => 'elFinderSessionInterface.php',
        'elFinderVolumeDriver' => 'elFinderVolumeDriver.class.php',
        'elFinderVolumeDropbox2' => 'elFinderVolumeDropbox2.class.php',
        'elFinderVolumeFTP' => 'elFinderVolumeFTP.class.php',
        'elFinderVolumeFlysystemGoogleDriveCache' => 'elFinderFlysystemGoogleDriveNetmount.php',
        'elFinderVolumeFlysystemGoogleDriveNetmount' => 'elFinderFlysystemGoogleDriveNetmount.php',
        'elFinderVolumeGoogleDrive' => 'elFinderVolumeGoogleDrive.class.php',
        'elFinderVolumeGroup' => 'elFinderVolumeGroup.class.php',
        'elFinderVolumeLocalFileSystem' => 'elFinderVolumeLocalFileSystem.class.php',
        'elFinderVolumeMySQL' => 'elFinderVolumeMySQL.class.php',
        'elFinderVolumeTrash' => 'elFinderVolumeTrash.class.php',
    ];
    if (isset($map[$name])) {
        return include_once(ELFINDER_PHP_ROOT_PATH . '/' . $map[$name]);
    }
    $prefix = mb_substr($name, 0, 14);
    if ('elFinder' === mb_substr($prefix, 0, 8)) {
        if ('elFinderVolume' === $prefix) {
            $file = ELFINDER_PHP_ROOT_PATH . '/' . $name . '.class.php';

            return (is_file($file) && include_once($file));
        } elseif ('elFinderPlugin' === $prefix) {
            $file = ELFINDER_PHP_ROOT_PATH . '/plugins/' . mb_substr($name, 14) . '/plugin.php';

            return (is_file($file) && include_once($file));
        } elseif ('elFinderEditor' === $prefix) {
            $file = ELFINDER_PHP_ROOT_PATH . '/editors/' . mb_substr($name, 14) . '/editor.php';

            return (is_file($file) && include_once($file));
        }
    }

    return false;
}

if (version_compare(PHP_VERSION, '5.3', '<')) {
    spl_autoload_register('elFinderAutoloader');
} else {
    spl_autoload_register('elFinderAutoloader', true, true);
}
