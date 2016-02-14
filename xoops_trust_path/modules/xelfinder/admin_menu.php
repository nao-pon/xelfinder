<?php

$constpref = '_MI_' . strtoupper( $mydirname ) ;

$adminmenu = array(
	array(
		'title' => constant( $constpref.'_ADMENU_GOTO_MODULE' ) ,
		'link' => 'index.php' ,
	) ,
	array(
		'title' => constant( $constpref.'_ADMENU_GOTO_MANAGER' ) ,
		'link' => 'manager.php?admin=1' ,
	) ,
	array(
		'title' => constant( $constpref.'_ADMENU_DROPBOX' ) ,
		'link' => 'admin/index.php?page=dropbox' ,
	),
	array(
		'title' => constant( $constpref.'_ADMENU_GOOGLEDRIVE' ) ,
		'link' => 'admin/index.php?page=googledrive' ,
	),
	array(
		'title' => constant( $constpref.'_ADMENU_VENDORUPDATE' ) ,
		'link' => 'admin/index.php?page=vendorup' ,
	)
) ;

$adminmenu4altsys = array(
	array(
		'title' => constant( $constpref.'_ADMENU_MYLANGADMIN' ) ,
		'link' => 'admin/index.php?mode=admin&lib=altsys&page=mylangadmin' ,
	) ,
// 	array(
// 		'title' => constant( $constpref.'_ADMENU_MYTPLSADMIN' ) ,
// 		'link' => 'admin/index.php?mode=admin&lib=altsys&page=mytplsadmin' ,
// 	) ,
	array(
		'title' => constant( $constpref.'_ADMENU_MYBLOCKSADMIN' ) ,
		'link' => 'admin/index.php?mode=admin&lib=altsys&page=myblocksadmin' ,
	) ,
	array(
		'title' => constant( $constpref.'_ADMENU_MYPREFERENCES' ) ,
		'link' => 'admin/index.php?mode=admin&lib=altsys&page=mypreferences' ,
	) ,
) ;
