<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'xelfinder' ;
$constpref = '_MI_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED' , 1 ) ;

// admin menu
define($constpref.'_ADMENU_MYLANGADMIN' ,   '言語定数管理' ) ;
define($constpref.'_ADMENU_MYTPLSADMIN' ,   'テンプレート管理' ) ;
define($constpref.'_ADMENU_MYBLOCKSADMIN' , 'ブロック管理/アクセス権限' ) ;
define($constpref.'_ADMENU_MYPREFERENCES' , '一般設定' ) ;

// configurations
define( $constpref.'_VOLUME_SETTING' ,          'ボリュームドライバ' );
define( $constpref.'_VOLUME_SETTING_DESC' ,     '[モジュールディレクトリ名]:[プラグイン名]:[ファイル格納ディレクトリ]:[表示名]' );
define( $constpref.'_SHARE_HOLDER' ,            '共有ホルダ' );
define( $constpref.'_DEFAULT_ITEM_PERM' ,       '作成されるアイテムのパーミッション' );
define( $constpref.'_DEFAULT_ITEM_PERM_DESC' ,  'パーミッションは3桁で[ファイルオーナー][グループ][ゲスト]<br />各桁 2進数4bitで [非表示(h)][読み込み(r)][書き込み(w)][ロック解除(u)]<br />744: オーナー 7 = -rwu, グループ 4 = -r--, ゲスト 4 = -r--' );
define( $constpref.'_USE_USERS_DIR' ,           'ユーザー別ホルダの使用' );
define( $constpref.'_USE_USERS_DIR_DESC' ,      '' );
define( $constpref.'_USERS_DIR_PERM' ,          'ユーザー別ホルダのパーミッション' );
define( $constpref.'_USERS_DIR_PERM_DESC' ,     '例: 7cc: オーナー 7 = -rwu, グループ c = hr--, ゲスト c = hr--' );
define( $constpref.'_USERS_DIR_ITEM_PERM' ,     'ユーザー別ホルダに作成されるアイテムのパーミッション' );
define( $constpref.'_USERS_DIR_ITEM_PERM_DESC' ,'例: 7cc: オーナー 7 = -rwu, グループ c = hr--, ゲスト c = hr--' );
define( $constpref.'_USE_GUEST_DIR' ,           'ゲスト用ホルダの使用' );
define( $constpref.'_USE_GUEST_DIR_DESC' ,      '' );
define( $constpref.'_GUEST_DIR_PERM' ,          'ゲスト用ホルダのパーミッション' );
define( $constpref.'_GUEST_DIR_PERM_DESC' ,     '例: 766: オーナー 7 = -rwu, グループ 6 = -rw-, ゲスト 6 = -rw-' );
define( $constpref.'_GUEST_DIR_ITEM_PERM' ,     'ゲスト用ホルダに作成されるアイテムのパーミッション' );
define( $constpref.'_GUEST_DIR_ITEM_PERM_DESC' ,'例: 744: オーナー 7 = -rwu, グループ 4 = -r--, ゲスト 4 = -r--' );
define( $constpref.'_USE_GROUP_DIR' ,           'グループ別ホルダの使用' );
define( $constpref.'_USE_GROUP_DIR_DESC' ,      '' );
define( $constpref.'_GROUP_DIR_PARENT' ,        'グループ別ホルダの親ホルダ名' );
define( $constpref.'_GROUP_DIR_PARENT_DESC' ,   '' );
define( $constpref.'_GROUP_DIR_PARENT_NAME' ,   'グループ毎閲覧');
define( $constpref.'_GROUP_DIR_PERM' ,          'グループ別ホルダのパーミッション' );
define( $constpref.'_GROUP_DIR_PERM_DESC' ,     '例: 768: オーナー 7 = -rwu, グループ 6 = -rw-, ゲスト 8 = h---' );
define( $constpref.'_GROUP_DIR_ITEM_PERM' ,     'グループ別ホルダに作成されるアイテムのパーミッション' );
define( $constpref.'_GROUP_DIR_ITEM_PERM_DESC' ,'例: 748: オーナー 7 = -rwu, グループ 4 = -r--, ゲスト 8 = h---' );

}
