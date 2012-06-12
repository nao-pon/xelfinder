<?php
/*
 * Created on 2012/01/22 by nao-pon http://hypweb.net/
 */

if (! defined('XOOPS_MODULE_PATH')) define('XOOPS_MODULE_PATH', XOOPS_ROOT_PATH . '/modules');
if (! defined('XOOPS_MODULE_URL')) define('XOOPS_MODULE_URL', XOOPS_URL . '/modules');

$xelfinderOpenJs = 'openWithSelfMain("'.XOOPS_MODULE_URL.'/'.$mydirname.'/manager.php", "elfinder", 750, 500);';
$xelfinderAdminOpenJs = 'openWithSelfMain("'.XOOPS_MODULE_URL.'/'.$mydirname.'/manager.php?admin=1", "elfinder", 750, 500);';


$isAdmin = false;
if (is_object($xoopsUser)) {
	if ($xoopsUser->isAdmin($xoopsModule->getVar('mid'))) {
		$isAdmin = true;
	}
}

$constpref = '_MD_' . strtoupper( $mydirname ) ;

include XOOPS_ROOT_PATH.'/header.php' ;

$_xoops_header = $xoopsTpl->get_template_vars('xoops_module_header');
$_xoops_header .= '<link rel="stylesheet" href="'.XOOPS_URL.'/modules/'.$mydirname.'/include/css/main.css" type="text/css" media="all" />';
$xoopsTpl->assign('xoops_module_header', $_xoops_header);


/*
echo '<script>
	(function(){
		var windowonload;
		if (typeof window.onload == "function") {
			windowonload = window.onload();
		}
		window.onload=function(){
			if (windowonload) {
				windowonload();
			}
			'.$xelfinderAdminOpenJs.'
		};
	}());
</script>';
*/

echo '<div class="xelfinder module_'.$mydirname.'">';
echo '<h2><span>'.$xoopsModule->getVar('name').'</span></h2>';
echo '<h3><span>'.constant($constpref . '_OPEN_MANAGER').'</span></h3>';
echo '<div class="xelfinder_main_menu"><a class="button button-blue" href="#" onclick="'.htmlspecialchars($xelfinderOpenJs).'return false;"><span>'.constant($constpref . '_OPEN_WINDOW').'</span></a></div>';
echo '<div class="xelfinder_main_menu"><a class="button button-blue" href="'.XOOPS_MODULE_URL.'/'.$mydirname.'/manager.php"><span>'.constant($constpref . '_OPEN_FULL').'</span></a></div>';

if ($isAdmin) {
	echo '<div class="xelfinder_main_menu"><a class="button button-green" href="#" onclick="'.htmlspecialchars($xelfinderAdminOpenJs).'return false;"><span>'.constant($constpref . '_OPEN_WINDOW_ADMIN').'</span></a></div>';
	echo '<div class="xelfinder_main_menu"><a class="button button-green" href="'.XOOPS_MODULE_URL.'/'.$mydirname.'/manager.php?admin=1"><span>'.constant($constpref . '_OPEN_FULL_ADMIN').'</span></a></div>';
	echo '<hr />';
	echo '<div class="xelfinder_main_menu"><a class="button button-orange" href="'.XOOPS_MODULE_URL.'/'.$mydirname.'/admin/index.php"><span>'.constant($constpref . '_ADMIN_PANEL').'</span></a></div>';
}

echo '</div>';

include XOOPS_ROOT_PATH.'/footer.php';

