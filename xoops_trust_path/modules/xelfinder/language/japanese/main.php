<?php

if (defined('FOR_XOOPS_LANG_CHECKER')) {
    $mydirname = 'xelfinder';
}
$constpref = '_MD_' . mb_strtoupper($mydirname);

if (defined('FOR_XOOPS_LANG_CHECKER') || !defined($constpref . '_LOADED_MAIN')) {
    // a flag for this language file has already been read or not.
    define($constpref . '_LOADED_MAIN', 1);

    define($constpref . '_OPEN_MANAGER', '�ե�����ޥ͡�����򳫤�');
    define($constpref . '_OPEN_WINDOW', '�ݥåץ��å�');
    define($constpref . '_OPEN_FULL', '�ե륦����ɥ�');
    define($constpref . '_OPEN_WINDOW_ADMIN', '�ݥåץ��å�(�����⡼��)');
    define($constpref . '_OPEN_FULL_ADMIN', '�ե륦����ɥ�(�����⡼��)');
    define($constpref . '_ADMIN_PANEL', '�������̤򳫤�');
}
