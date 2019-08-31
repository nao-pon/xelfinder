<?php

// fix IIS PATH_INFO
if (isset($_SERVER['SERVER_SOFTWARE']) && false !== mb_strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
    $_SERVER['PATH_INFO'] = preg_replace('/^' . preg_quote($_SERVER['SCRIPT_NAME']) . '/', '', $_SERVER['PATH_INFO']);
}

if ((isset($_GET['page']) && ('view' === $_GET['page'] || 'tmb' === $_GET['page']))
    || (isset($_SERVER['PATH_INFO']) && preg_match('#^/(?:view|tmb)/#', $_SERVER['PATH_INFO']))) {
    define('PROTECTOR_SKIP_DOS_CHECK', true);
    define('BIGUMBRELLA_DISABLED', true);
    define('HYP_COMMON_SKIP_POST_FILTER', true);
    define('PROTECTOR_SKIP_FILESCHECKER', 1);
}

require '../../mainfile.php';
if (!defined('XOOPS_TRUST_PATH')) {
    die('set XOOPS_TRUST_PATH in mainfile.php');
}

$mydirname = basename(__DIR__);
$mydirpath = __DIR__;
require $mydirpath . '/mytrustdirname.php'; // set $mytrustdirname

if ('admin' == @$_GET['mode']) {
    require XOOPS_TRUST_PATH . '/modules/' . $mytrustdirname . '/admin.php';
} else {
    require XOOPS_TRUST_PATH . '/modules/' . $mytrustdirname . '/main.php';
}
