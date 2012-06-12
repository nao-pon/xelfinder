<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'xelfinder' ;
$constpref = '_MD_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED_MAIN' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED_MAIN' , 1 ) ;

define( $constpref.'_OPEN_MANAGER'       , 'ファイルマネージャを開く' ) ;
define( $constpref.'_OPEN_WINDOW'        , 'ポップアップ' ) ;
define( $constpref.'_OPEN_FULL'          , 'フルスクリーン' ) ;
define( $constpref.'_OPEN_WINDOW_ADMIN'  , 'ポップアップ(管理モード)' ) ;
define( $constpref.'_OPEN_FULL_ADMIN'    , 'フルスクリーン(管理モード)' ) ;
define( $constpref.'_ADMIN_PANEL'        , '管理画面を開く' ) ;

}
