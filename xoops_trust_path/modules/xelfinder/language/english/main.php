<?php

if (defined('FOR_XOOPS_LANG_CHECKER')) {
    $mydirname = 'xelfinder';
}
$constpref = '_MD_' . mb_strtoupper($mydirname);

if (defined('FOR_XOOPS_LANG_CHECKER') || !defined($constpref . '_LOADED_MAIN')) {
    // a flag for this language file has already been read or not.
    define($constpref . '_LOADED_MAIN', 1);

    define($constpref . '_OPEN_MANAGER', 'Open the file manager');
    define($constpref . '_OPEN_WINDOW', 'Popup window');
    define($constpref . '_OPEN_FULL', 'New window');
    define($constpref . '_OPEN_WINDOW_ADMIN', 'Pop window (Admin mode)');
    define($constpref . '_OPEN_FULL_ADMIN', 'New window (Admin mode)');
    define($constpref . '_ADMIN_PANEL', 'Go to admin panel');
}
