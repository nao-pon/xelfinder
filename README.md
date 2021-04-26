# X-elFinder (File manager and editor)

X-elFinder (X-elFinder)
elFinder 2.x, web-based file manager running on JavaScript + PHP module for XOOPS Cube.


* [nao-pon/xelfinder - GitHub](https://github.com/nao-pon/xelfinder)

You can download it from "[ZIP](https://github.com/nao-pon/xelfinder/zipball/master)" on the above page.

For topics, questions, and requests about X-elFinder, please visit the forum.

* [X-elFinder - フォーラム - XOOPS マニア](http://xoops.hypweb.net/modules/forum/index.php?forum_id=25)

## Environment

* XOOPS platforms
 * Verified platforms
  * XOOPS Cube Legacy 2.2.0, 2.2.1
  * XOOPS 2.1.16-JP
  XOOPS 2.1.16-JP * XOOPS 2.5.5
* PHP 5.2 or higher

## Notes on installation

The following directories require write (file creation) permission (e.g. 777 or 707).

* html/modules/xelfinder/cache
* html/modules/xelfinder/cache/tmb
* xoops_trust_path/uploads/xelfinder

PathInfo is used for image referencing, but depending on the server environment, PathInfo may not be available and the image may not be displayed correctly.

In this case, please set "Disable PathInfo for file reference URLs" to "Yes" in the general settings of the administration page.

### Change the popup to IFRAME

The default popup openWithSelfMain() will open a new window so,  
to change this to a popup using IFRAME, load `<{xoops_js}>` in theme.html,   
and then load openWithSelfMain_iframe.js.

From the HypConf (HypCommon settings) module, select "Other settings" - "Tags to insert at the end of &lt;head&gt;".

    <script type="text/javascript" src="<{$xoops_url}>/modules/xelfinder/include/js/openWithSelfMain_iframe.js"></script>

or edit theme.html as follows

Example (theme.html):

    <script type="text/javascript">
    <!--
    <{$xoops_js}>
    //-->
    </script>
    <script type="text/javascript" src="<{$xoops_url}>/modules/xelfinder/include/js/openWithSelfMain_iframe.js"></script>

### About libraries

HypCommonFunc is required in order to enable this feature.

* [HypCommonFunc について](http://xoops.hypweb.net/modules/xpwiki/156.html)

## X-elFinder Specific Features

In addition to the functions of elFinder, it has the following features

* Drag and drop file uploads between browser windows. (Firefox, Chrome, Safari)
* Image editing using Pixlr.com 
* [Dropbox.com](http://db.tt/w0gZJglT) Direct manipulation of data storage (http://db.tt/w0gZJglT) 500MB bonus for new registration & installation)
* Disabled commands can be specified for each group (limitation of specified functions)
* Adding a volume (like a drive) in plug-in form
    * You can specify the group ID to be enabled for each volume.
    * xelfinder_db Fine-tuned support with plug-ins
        * Folders by user ー
        * Folders by group ー
        * Guest folder ー
        * Permission settings for folders and files (read, write, unlock, and hide can be set for owner, group, and guest respectively))
        * Permissions for new items can be set per folder.
    * xelfinder Using plug-ins to specify an arbitrary directory in the server and manipulate image files in that directory
    * XOOPS の d3diary, GNAVI, MailBBS, MyAlbum Module plug-ins included
        * You can use the images stored in each module.

## imagemanager.php   

Except for XOOPS Cube Legacy, this can be done by inserting in mainfile.php  
immediately after the line that reads XOOPS_ROOT_PATH/imagemanager.php :

    include 'modules/xelfinder/manager.php';



## Notes on Uninstallation

When uninstalling, the uploaded files will remain, but all information such as folders, permissions, owners, etc. will be lost.

If you want to save that information, please save your data with a backup of your database.

X-elFinder table name will start with "[XOOPS DB prefix]_[X-elFinder module directory name]_".

If you want to uninstall and remove the files, you can find them in "XOOPS_TRUST_PATH/uploads/xelfinder" directory.

* file : "[after domain part of XOOPS_URL]_[X-elFinder module directory name]_[file ID(number)]"
* thumb: "[after domain part of XOOPS_URL]_[X-elFinder module directory name]_[file ID(number)]_[reduced ratio(number)].tmb"

Please refer to  [elFinder](https://github.com/Studio-42/elFinder) project documentation for details.
