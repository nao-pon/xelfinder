<?php

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) ) $mydirname = 'xelfinder' ;
$constpref = '_MI_' . strtoupper( $mydirname ) ;

if( defined( 'FOR_XOOPS_LANG_CHECKER' ) || ! defined( $constpref.'_LOADED' ) ) {

// a flag for this language file has already been read or not.
define( $constpref.'_LOADED' , 1 ) ;

define( $constpref.'_DESC' , 'The module which uses the file manager elFinder of a Web base as an image manager.');

// admin menu
define( $constpref.'_ADMENU_GOTO_MODULE' , 'Goto Module' ) ;
define( $constpref.'_ADMENU_GOTO_MANAGER' ,'FileManager' ) ;
define( $constpref.'_ADMENU_DROPBOX' ,     'Get Dropbox App Token' ) ;
define( $constpref.'_ADMENU_VENDORUPDATE' ,'Update vendor' ) ;
define( $constpref.'_ADMENU_MYLANGADMIN',  'Languages');
define( $constpref.'_ADMENU_MYTPLSADMIN',  'Templates');
define( $constpref.'_ADMENU_MYBLOCKSADMIN','Blocks/Permissions');
define( $constpref.'_ADMENU_MYPREFERENCES','Preferences');

// configurations
define( $constpref.'_MANAGER_TITLE' ,           'Page title of manager' );
define( $constpref.'_MANAGER_TITLE_DESC' ,      '' );
define( $constpref.'_VOLUME_SETTING' ,          'Volume Drivers' );
define( $constpref.'_VOLUME_SETTING_DESC' ,     '[Module directory name]:[Plugin name]:[Saved files dirctory path]:[View name]:[Options]<br />Written line by line. Will be ignored and put a "#" at the beginning.<br />Options separates with "|".<br />Common option:<br />gid=[Group ID to validate is specified by comma separated values]<br />defaults=[What validates each permission of read, write, hidden, and lock as a default is specified in each initial "r" "w" "h" "l" (example:  defaults=rw)]<br />uploadMaxSize=[Maximum upload file size (ex.2M)]<br />id=[Arbitrary unique ID]<br />encoding=[character encoding(use iconv)]<br />locale=[Locale(Impotant: for encoding)]<br />chmod=1(Enable chmod on supported volume only)' );
define( $constpref.'_SHARE_HOLDER' ,            'Share holder' );
define( $constpref.'_DISABLED_CMDS_BY_GID' ,    'Disabled cmds of each group' );
define( $constpref.'_DISABLED_CMDS_BY_GID_DESC','Set [GroupID]=[Disabled cmds(comma-separated)] separated by ":".<br />Command list: archive, chmod, cut, duplicate, edit, empty, extract, mkdir, mkfile, paste, perm, put, rename, resize, rm, upload etc...' );
define( $constpref.'_DISABLE_WRITES_GUEST' ,    'Disable writing cmds to guest' );
define( $constpref.'_DISABLE_WRITES_GUEST_DESC','All writing commands are added to the disabled commands to guests.' );
define( $constpref.'_DISABLE_WRITES_USER' ,     'Disable writing cmds to user' );
define( $constpref.'_DISABLE_WRITES_USER_DESC', 'All writing commands are added to the disabled commands to registed users.' );
define( $constpref.'_USE_GOOGLE_PREVIEW' ,      'Enable Google Docs preview' );
define( $constpref.'_USE_GOOGLE_PREVIEW_DESC',  'Use Google Docs to expand previewable file types. When Google Docs Preview is used, it notifies the content URL to Google Docs.' );
define( $constpref.'_MAIL_NOTIFY_GUEST' ,       'E-Mail Notify (Guest)' );
define( $constpref.'_MAIL_NOTIFY_GUEST_DESC',   'Mailing notifies an administrator of file addition by a guest.' );
define( $constpref.'_MAIL_NOTIFY_GROUP' ,       'E-Mail Notify (Groups)' );
define( $constpref.'_MAIL_NOTIFY_GROUP_DESC',   'Mailing notifies an administrator of file addition by selected groups.' );
define( $constpref.'_FTP_NAME' ,                'Name of FTP net volume' );
define( $constpref.'_FTP_NAME_DESC' ,           'The display name of the FTP connection net volume for administrators.' );
define( $constpref.'_FTP_HOST' ,                'FTP Host name' );
define( $constpref.'_FTP_HOST_DESC' ,           '' );
define( $constpref.'_FTP_PORT' ,                'FTP port' );
define( $constpref.'_FTP_PORT_DESC' ,           'default: 21' );
define( $constpref.'_FTP_PATH' ,                'Directory as root' );
define( $constpref.'_FTP_PATH_DESC' ,           'FTP configuration is also used for "ftp" plug-volume driver. Please be blank if you set only for "ftp" plug-in.' );
define( $constpref.'_FTP_USER' ,                'FTP user name' );
define( $constpref.'_FTP_USER_DESC' ,           '' );
define( $constpref.'_FTP_PASS' ,                'FTP password' );
define( $constpref.'_FTP_PASS_DESC' ,           '' );
define( $constpref.'_FTP_SEARCH' ,              'FTP volume include in search results' );
define( $constpref.'_FTP_SEARCH_DESC' ,         'If in search results include FTP net volume, the search may time out.' );
define( $constpref.'_BOXAPI_ID' ,               'Box API OAuth2 client_id' );
define( $constpref.'_BOXAPI_ID_DESC' ,          'Box API Console [ https://app.box.com/developers/services ]' );
define( $constpref.'_BOXAPI_SECRET' ,           'Box API OAuth2 client_secret' );
define( $constpref.'_BOXAPI_SECRET_DESC' ,      'When using OneDrive as a network volume, please set "'.str_replace('http://','https://',XOOPS_URL).'/modules/'.$mydirname.'/connector.php" in the Box API application setting - redirect_url.(https: Required. Optional paths after domain can be omitted)' );
define( $constpref.'_GOOGLEAPI_ID' ,            'Google API Client ID' );
define( $constpref.'_GOOGLEAPI_ID_DESC' ,       'Google API Console [ https://console.developers.google.com ]' );
define( $constpref.'_GOOGLEAPI_SECRET' ,        'Google API Client Secret' );
define( $constpref.'_GOOGLEAPI_SECRET_DESC' ,   'If you use Google Drive as a network volume (PHP 5.4 or higher is required) Google API console credentials - approved redirect URL add to "'.XOOPS_URL.'/modules/'.$mydirname.'/connector.php?cmd=netmount&protocol=googledrive&host=1".' );
define( $constpref.'_ONEDRIVEAPI_ID' ,          'OneDrive API Application ID' );
define( $constpref.'_ONEDRIVEAPI_ID_DESC' ,     'OneDrive API Console [ https://apps.dev.microsoft.com/#/appList ]' );
define( $constpref.'_ONEDRIVEAPI_SECRET' ,      'OneDrive API Password' );
define( $constpref.'_ONEDRIVEAPI_SECRET_DESC' , 'When using OneDrive as a network volume, please set "'.XOOPS_URL.'/modules/'.$mydirname.'/connector.php/netmount/googledrive/1" in the OneDrive API application setting - redirect URL.' );
define( $constpref.'_DROPBOX_TOKEN_DESC' ,      'Developers - Dropbox [ https://www.dropbox.com/developers ]' );
define( $constpref.'_DROPBOX_SECKEY' ,          'Dropbox.com App secret' );
define( $constpref.'_DROPBOX_SECKEY_DESC' ,     '' );
define( $constpref.'_DROPBOX_ACC_TOKEN' ,       'Access token of shared Dropbox' );
define( $constpref.'_DROPBOX_ACC_TOKEN_DESC' ,  'The access token key for using it in shared Dropbox volume. You can get it at https://www.dropbox.com/developers/apps.' );
define( $constpref.'_DROPBOX_ACC_SECKEY' ,      'Access token secret key of shared Dropbox' );
define( $constpref.'_DROPBOX_ACC_SECKEY_DESC' , 'This is the setting for the old OAuth 1. To set a new OAuth 2 access token, you need to empty the value. If you are using OAuth 1 please move early to OAuth 2.' );
define( $constpref.'_DROPBOX_NAME' ,            'Shared Dropbox volume display name' );
define( $constpref.'_DROPBOX_NAME_DESC' ,       'Unlike mount of network volume, shared Dropbox volume is displayed on all the users.' );
define( $constpref.'_DROPBOX_PATH' ,            'Root pass of shared Dropbox' );
define( $constpref.'_DROPBOX_PATH_DESC' ,       'The path of the class which may generally be indicated in shared Dropbox volume is specified. (example:  "/Public")<br />A Dropbox setup is "dropbox" of a volume driver. It is used also for plugin.<br />"dropbox" When you set to plug-in, please make a root pass into a blank.' );
define( $constpref.'_DROPBOX_HIDDEN_EXT' ,      'Shared Dropbox hidden files' );
define( $constpref.'_DROPBOX_HIDDEN_EXT_DESC' , 'The file (backward match of a file name) displayed only on administrators is specified by comma separated values.<br />It is aimed at a folder when an end is "/".' );
define( $constpref.'_DROPBOX_WRITABLE_GROUPS' , 'Groups which permits full access to Share Dropbox' );
define( $constpref.'_DROPBOX_WRITABLE_GROUPS_DESC' , 'To the group set up here, all the accesses, such as creation, deletion, movement, etc. of file or directory, are permitted. Other groups can only be read.' );
define( $constpref.'_DROPBOX_UPLOAD_MIME' ,     'Shared Dropbox MIME type which can be uploaded') ;
define( $constpref.'_DROPBOX_UPLOAD_MIME_DESC' ,'The MIME type which the group which permits writing can upload. It sets up by comma separated values. Administrators do not receive this restriction.') ;
define( $constpref.'_DROPBOX_WRITE_EXT' ,       'Shared Dropbox Writable files') ;
define( $constpref.'_DROPBOX_WRITE_EXT_DESC' ,  'The backward match of the file name which permits writing to the group which permits writing. It specifies by comma separated values. <br />It is aimed at a folder when an end is "/".<br />Administrators are not restricted.') ;
define( $constpref.'_DROPBOX_UNLOCK_EXT' ,      'Shared Dropbox unlocked files') ;
define( $constpref.'_DROPBOX_UNLOCK_EXT_DESC' , 'Unlocking file can be deleted, moved and renamed.<br />The file (backward match of a file name) which does not lock is specified by comma separated values.<br />It is aimed at a folder when an end is "/".<br />All the files are unlocking at administrators.') ;
define( $constpref.'_JQUERY' ,                  'URL of jQuery' );
define( $constpref.'_JQUERY_DESC' ,             'When not using CDN of Google, URL of "js" of jQuery is specified.' );
define( $constpref.'_JQUERY_UI' ,               'URL of jQuery UI' );
define( $constpref.'_JQUERY_UI_DESC' ,          'When not using CDN of Google, URL of "js" of jQueryUI is specified.' );
define( $constpref.'_JQUERY_UI_CSS' ,           'URL of jQuery UI CSS' );
define( $constpref.'_JQUERY_UI_CSS_DESC' ,      'When not using CDN of Google, URL of "css" of jQueryUI is specified.' );
define( $constpref.'_JQUERY_UI_THEME' ,         'jQuery UI Theme' );
define( $constpref.'_JQUERY_UI_THEME_DESC' ,    'When CDN of Google is used, Theme name or URL to jQuery Theme CSS (Default: smoothness)' );
define( $constpref.'_CREATIVE_CLOUD_APIKEY' ,   'Creative SDK API Key' );
define( $constpref.'_CREATIVE_CLOUD_APIKEY_DESC','Specify the Creative Cloud API key when using Creative SDK image editor of Creative Cloud. <br /> API key can be obtained at https://console.adobe.io/.' );
define( $constpref.'_EDITORS_JS',               'URL of editors.js' );
define( $constpref.'_EDITORS_JS_DESC',          'Specify the URL of JavaScript when customizing "common/elfinder/js/extras/editors.default.js".' );
define( $constpref.'_THUMBNAIL_SIZE' ,          '[xelfinder_db] Thumbnail size of image insertion' );
define( $constpref.'_THUMBNAIL_SIZE_DESC' ,     'The default value (px) of the thumbnail size at picture insertion by BBcode.' );
define( $constpref.'_DEFAULT_ITEM_PERM' ,       '[xelfinder_db] Permission of new items' );
define( $constpref.'_DEFAULT_ITEM_PERM_DESC' ,  'Permission is three-digit hexadecimal.[File owner][group][Guest]<br />4bit binary number each digit is [Hide][Read][Write][Unlock]<br />744 Owner: 7 =-rwu, group 4 =-r--, Guest 4 =-r--' );
define( $constpref.'_USE_USERS_DIR' ,           '[xelfinder_db] Use of the holder for each user' );
define( $constpref.'_USE_USERS_DIR_DESC' ,      '' );
define( $constpref.'_USERS_DIR_PERM' ,          '[xelfinder_db] Permission of "holder for each user"' );
define( $constpref.'_USERS_DIR_PERM_DESC' ,     'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 7cc: Owner 7 = -rwu, Group c = hr--, Guest c = hr--' );
define( $constpref.'_USERS_DIR_ITEM_PERM' ,     '[xelfinder_db] Permission of the new items in "holder by user"' );
define( $constpref.'_USERS_DIR_ITEM_PERM_DESC' ,'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 7cc: Owner 7 = -rwu, Group c = hr--, Guest c = hr--' );
define( $constpref.'_USE_GUEST_DIR' ,           '[xelfinder_db] Use the holder for guest' );
define( $constpref.'_USE_GUEST_DIR_DESC' ,      '' );
define( $constpref.'_GUEST_DIR_PERM' ,          '[xelfinder_db] Permission of "holder for guest"' );
define( $constpref.'_GUEST_DIR_PERM_DESC' ,     'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 766: Owner 7 = -rwu, Group 6 = -rw-, Guest 6 = -rw-' );
define( $constpref.'_GUEST_DIR_ITEM_PERM' ,     '[xelfinder_db] Permission of the new items in "holder for guest"' );
define( $constpref.'_GUEST_DIR_ITEM_PERM_DESC' ,'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 744: Owner 7 = -rwu, Group 4 = -r--, Guest 4 = -r--' );
define( $constpref.'_USE_GROUP_DIR' ,           '[xelfinder_db] Use the holder for each group' );
define( $constpref.'_USE_GROUP_DIR_DESC' ,      '' );
define( $constpref.'_GROUP_DIR_PARENT' ,        '[xelfinder_db] Parent holder name for "holder for each group"' );
define( $constpref.'_GROUP_DIR_PARENT_DESC' ,   '' );
define( $constpref.'_GROUP_DIR_PARENT_NAME' ,   'For group');
define( $constpref.'_GROUP_DIR_PERM' ,          '[xelfinder_db] Permission of "holder for each group"' );
define( $constpref.'_GROUP_DIR_PERM_DESC' ,     'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 768: Owner 7 = -rwu, Group 6 = -rw-, Guest 8 = h---' );
define( $constpref.'_GROUP_DIR_ITEM_PERM' ,     '[xelfinder_db] Permission of the new items in "holder for each group"' );
define( $constpref.'_GROUP_DIR_ITEM_PERM_DESC' ,'The setting here is referred to only when it is created. Please change after it is created directly in the elFinder. <br />ex. 748: Owner 7 = -rwu, Group 4 = -r--, Guest 8 = h---' );

define( $constpref.'_UPLOAD_ALLOW_ADMIN' ,      '[xelfinder_db] Upload allow MIME types for Admin' );
define( $constpref.'_UPLOAD_ALLOW_ADMIN_DESC' , 'Specifies the MIME types, separated by a space.<br />all: Allow all, none: Nothing<br />ex. image text/plain' );
define( $constpref.'_AUTO_RESIZE_ADMIN' ,       '[xelfinder_db] Auto resize for Admin (px)' );
define( $constpref.'_AUTO_RESIZE_ADMIN_DESC' ,  'Value(px) which resizes a picture automatically so that it may fit in the specified rectangle size at the time of upload.<br />An input of nothing will not perform automatic resizing.' );
define( $constpref.'_UPLOAD_MAX_ADMIN' ,        '[xelfinder_db]  Allowed max filesize for Admin' );
define( $constpref.'_UPLOAD_MAX_ADMIN_DESC',    'The maximum file size which an administrator can upload is specified. It becomes unlimited with empty or "0". (ex. 10M)' );

define( $constpref.'_SPECIAL_GROUPS' ,          '[xelfinder_db] Special groups' );
define( $constpref.'_SPECIAL_GROUPS_DESC' ,     'Select groups you want to special group. (Multiple Select)' );
define( $constpref.'_UPLOAD_ALLOW_SPGROUPS' ,   '[xelfinder_db] Upload allow MIME types for Special groups' );
define( $constpref.'_UPLOAD_ALLOW_SPGROUPS_DESC','' );
define( $constpref.'_AUTO_RESIZE_SPGROUPS' ,    '[xelfinder_db] Auto resize for Special groups (px)' );
define( $constpref.'_AUTO_RESIZE_SPGROUPS_DESC','' );
define( $constpref.'_UPLOAD_MAX_SPGROUPS' ,     '[xelfinder_db] Allowed max filesize for Special groups' );
define( $constpref.'_UPLOAD_MAX_SPGROUPS_DESC', '' );

define( $constpref.'_UPLOAD_ALLOW_USER' ,       '[xelfinder_db] Upload allow MIME types for Registed user' );
define( $constpref.'_UPLOAD_ALLOW_USER_DESC' ,  '' );
define( $constpref.'_AUTO_RESIZE_USER' ,        '[xelfinder_db] Auto resize for Registed user (px)' );
define( $constpref.'_AUTO_RESIZE_USER_DESC',    '' );
define( $constpref.'_UPLOAD_MAX_USER' ,         '[xelfinder_db] Allowed max filesize for user' );
define( $constpref.'_UPLOAD_MAX_USER_DESC',     '' );

define( $constpref.'_UPLOAD_ALLOW_GUEST' ,      '[xelfinder_db] Upload allow MIME types for Guest' );
define( $constpref.'_UPLOAD_ALLOW_GUEST_DESC' , '' );
define( $constpref.'_AUTO_RESIZE_GUEST' ,       '[xelfinder_db] Auto resize for Guest (px)' );
define( $constpref.'_AUTO_RESIZE_GUEST_DESC',   '' );
define( $constpref.'_UPLOAD_MAX_GUEST' ,        '[xelfinder_db] Allowed max filesize for Guest' );
define( $constpref.'_UPLOAD_MAX_GUEST_DESC',    '' );

define( $constpref.'_DISABLE_PATHINFO' ,        '[xelfinder_db] Not use PathInfo of file reference URL' );
define( $constpref.'_DISABLE_PATHINFO_DESC' ,   'The server which cannot use environment variable "PATH_INFO" needs to choose "Yes".' );

define( $constpref.'_EDIT_DISABLE_LINKED' ,     '[xelfinder_db] Write-protect of linked file' );
define( $constpref.'_EDIT_DISABLE_LINKED_DESC' ,'Write-protect automatically of referenced linked files  for order to prevent inadvertent overwriting or broken links.' );

define( $constpref.'_CONNECTOR_URL' ,           'Connector URL of ext or secure （Optional）' );
define( $constpref.'_CONNECTOR_URL_DESC' ,      'URL of connector.php, when only communication with the case where it connects with the connector of an external site, or a back end uses secure environment.' );

define( $constpref.'_CONN_URL_IS_EXT',          'Is external connector URL' );
define( $constpref.'_CONN_URL_IS_EXT_DESC',     '"Yes", when connector URL which carried out arbitrary specification is an external site, in the case of URL to which connector URL makes SSL connection only of the back end communication, please choose "no".<br />When connecting it with the connector of an external site, the origin domain here needs to be permitted at the partner point site.' );

define( $constpref.'_ALLOW_ORIGINS',            'Domain origins to permit' );
define( $constpref.'_ALLOW_ORIGINS_DESC',       'The domain origin (example: "http://example.com" the last slash is unnecessary) of the external site which permits connection to the connector of this site is set up by line pause.<br />In the case of URL to which connector URL makes SSL connection only of the back end communication, it is necessary to specify " <strong>'.preg_replace('#^(https?://[^/]+).*$#', '$1', XOOPS_URL).'</strong> ".' );

define( $constpref.'_UNZIP_LANG_VALUE' ,        'Local for unzip' );
define( $constpref.'_UNZIP_LANG_VALUE_DESC' ,   'Local (LANG) for unzip exec' );

define( $constpref.'_AUTOSYNC_SEC_ADMIN',       'Auto sync interval(Admin) : sec' );
define( $constpref.'_AUTOSYNC_SEC_ADMIN_DESC',  'Specify the interval at which the update automatically check in seconds.' );

define( $constpref.'_AUTOSYNC_SEC_SPGROUPS',    'Auto sync interval(Special groups) : sec' );
define( $constpref.'_AUTOSYNC_SEC_SPGROUPS_DESC', '' );

define( $constpref.'_AUTOSYNC_SEC_USER',        'Auto sync interval(Registed user) : sec' );
define( $constpref.'_AUTOSYNC_SEC_USER_DESC',   '' );

define( $constpref.'_AUTOSYNC_SEC_GUEST',       'Auto sync interval(Guest) : sec' );
define( $constpref.'_AUTOSYNC_SEC_GUEST_DESC',  '' );

define( $constpref.'_AUTOSYNC_START',           'Start auto sync as soon' );
define( $constpref.'_AUTOSYNC_START_DESC',      'Can start-stop of the auto sync by "reload" in the context menu.' );

define( $constpref.'_FFMPEG_PATH',              'Path to ffmpeg command' );
define( $constpref.'_FFMPEG_PATH_DESC',         'Specify the path when the path to ffmpeg is required.' );

define( $constpref.'_DEBUG' ,                   'Enable Debug mode' );
define( $constpref.'_DEBUG_DESC' ,              'If it is set in a debug mode, an individual file will be read instead of "elfinder.min.css" and "elfinder.min.js" by elFinder.<br />Moreover, debugging information is included in the response of JavaScript.<br />We recommend "No debug" for performance improvement.' );

// admin/dropbox.php
define( $constpref.'_DROPBOX_STEP1' ,        'Step 1: Make App');
define( $constpref.'_DROPBOX_GOTO_APP' ,     'Please create App at the following link place (Dropbox.com), acquire App key and App secre, and set to "%s" and "%s" of Preferences.');
define( $constpref.'_DROPBOX_GET_TOKEN' ,    'Get "Dropbox App Token"');
define( $constpref.'_DROPBOX_STEP2' ,        'Step 2: Go to Dropbox and approves');
define( $constpref.'_DROPBOX_GOTO_CONFIRM' , 'Please move on to the following link place (Dropbox.com), and approve an application.');
define( $constpref.'_DROPBOX_CONFIRM_LINK' , 'Go to Dropbox.com and approves an application. ');
define( $constpref.'_DROPBOX_STEP3' ,        'Step 3: Completed. It sets to Preferences.');
define( $constpref.'_DROPBOX_SET_PREF' ,     'Please set the following value as each item of Preferences.');

// admin/googledrive.php
define( $constpref.'_GOOGLEDRIVE_GET_TOKEN', 'Get "Google Drive Token"');

// admin/composer_update.php
define( $constpref.'_COMPOSER_UPDATE' ,       'Update to plugins/vendor (composer update)');
define( $constpref.'_COMPOSER_DO_UPDATE' ,    'Do update now(It will take some time)');
define( $constpref.'_COMPOSER_UPDATE_STARTED','It was to begin the update. Please wait until "Update was completed." is displayed ...');
define( $constpref.'_COMPOSER_DONE_UPDATE' ,  'Update was completed.');

}
