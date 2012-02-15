<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'xelfinder' ;
$constpref = '_MI_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED' , 1 ) ;

// admin menu
define( $constpref.'_ADMENU_MYLANGADMIN',  'Languages');
define( $constpref.'_ADMENU_MYTPLSADMIN',  'Templates');
define( $constpref.'_ADMENU_MYBLOCKSADMIN','Blocks/Permissions');
define( $constpref.'_ADMENU_MYPREFERENCES','Preferences');

// configurations
define( $constpref.'_VOLUME_SETTING' ,          'Volume Drivers' );
define( $constpref.'_VOLUME_SETTING_DESC' ,     '[Module directory name]:[Plugin name]:[Saved files dirctory path]:[View name]' );
define( $constpref.'_SHARE_HOLDER' ,            'Share holder' );
define( $constpref.'_DEFAULT_ITEM_PERM' ,       'Permission of new items' );
define( $constpref.'_DEFAULT_ITEM_PERM_DESC' ,  'Permission is three-digit hexadecimal.[File owner][group][Guest]<br />4bit binary number each digit is [Hide][Read][Write][Unlock]<br />744 Owner: 7 =-rwu, group 4 =-r--, Guest 4 =-r--' );
define( $constpref.'_USE_USERS_DIR' ,           'Use of the holder for each user' );
define( $constpref.'_USE_USERS_DIR_DESC' ,      '' );
define( $constpref.'_USERS_DIR_PERM' ,          'Permission of "holder for each user"' );
define( $constpref.'_USERS_DIR_PERM_DESC' ,     'ex. 7cc: Owner 7 = -rwu, Group c = hr--, Guest c = hr--' );
define( $constpref.'_USERS_DIR_ITEM_PERM' ,     'Permission of the new items in "holder by user"' );
define( $constpref.'_USERS_DIR_ITEM_PERM_DESC' ,'ex. 7cc: Owner 7 = -rwu, Group c = hr--, Guest c = hr--' );
define( $constpref.'_USE_GUEST_DIR' ,           'Use the holder for guest' );
define( $constpref.'_USE_GUEST_DIR_DESC' ,      '' );
define( $constpref.'_GUEST_DIR_PERM' ,          'Permission of "holder for guest"' );
define( $constpref.'_GUEST_DIR_PERM_DESC' ,     'ex. 766: Owner 7 = -rwu, Group 6 = -rw-, Guest 6 = -rw-' );
define( $constpref.'_GUEST_DIR_ITEM_PERM' ,     'Permission of the new items in "holder for guest"' );
define( $constpref.'_GUEST_DIR_ITEM_PERM_DESC' ,'ex. 744: Owner 7 = -rwu, Group 4 = -r--, Guest 4 = -r--' );
define( $constpref.'_USE_GROUP_DIR' ,           'Use the holder for each group' );
define( $constpref.'_USE_GROUP_DIR_DESC' ,      '' );
define( $constpref.'_GROUP_DIR_PARENT' ,        'Parent holder name for "holder for each group"' );
define( $constpref.'_GROUP_DIR_PARENT_DESC' ,   '' );
define( $constpref.'_GROUP_DIR_PARENT_NAME' ,   'Read by group');
define( $constpref.'_GROUP_DIR_PERM' ,          'Permission of "holder for each group"' );
define( $constpref.'_GROUP_DIR_PERM_DESC' ,     'ex. 768: Owner 7 = -rwu, Group 6 = -rw-, Guest 8 = h---' );
define( $constpref.'_GROUP_DIR_ITEM_PERM' ,     'Permission of the new items in "holder for each group"' );
define( $constpref.'_GROUP_DIR_ITEM_PERM_DESC' ,'ex. 748: Owner 7 = -rwu, Group 4 = -r--, Guest 8 = h---' );

}
