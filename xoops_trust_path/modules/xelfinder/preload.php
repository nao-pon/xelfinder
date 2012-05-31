<?php

if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

if( ! preg_match( '/^[0-9a-zA-Z_-]+$/' , $mydirname ) ) exit ;

if( ! class_exists( 'xelfinderPreloadBase' ) ) {

class xelfinderPreloadBase extends XCube_ActionFilter {
	function preBlockFilter() {
		$root =& XCube_Root::getSingleton();
		$root->mDelegateManager->delete('Legacypage.Imagemanager.Access','Legacy_EventFunction::imageManager');
		$root->mDelegateManager->add('Legacypage.Imagemanager.Access',
									array($this, 'overRideDefaultImageManager'),
									XCUBE_DELEGATE_PRIORITY_FIRST);
		$this->mRoot->mDelegateManager->add('Legacy_TextFilter.MakeXCodeConvertTable',
									array($this, 'addXCodeConvertTable'),
									XCUBE_DELEGATE_PRIORITY_NORMAL - 1);
	}

	function overRideDefaultImageManager() {
		
		$mydirname = $this->mydirname;
		
		$root =& XCube_Root::getSingleton();
		$xoopsUser =& $root->mContext->mXoopsUser;
		
		// check module readable
		$module_handler =& xoops_gethandler('module');
		if ($XoopsModule = $module_handler->getByDirname($mydirname)) {
			$moduleperm_handler =& xoops_gethandler('groupperm');
			if ($moduleperm_handler->checkRight('module_read', $XoopsModule->getVar('mid'), (is_object($xoopsUser)? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS))) {
				$mydirpath = $this->mydirpath;
				$use_bbcode_siteimg = 1;
				require dirname(__FILE__).'/manager.php';
			}
		}
		
		// call legacy imageManager
		require_once XOOPS_MODULE_PATH.'/legacy/kernel/Legacy_EventFunctions.class.php';
		Legacy_EventFunction::imageManager();
	}
	
	function addXCodeConvertTable(&$patterns, &$replacements) {
		$patterns[] = '/\[siteimg align=([\'"]?)(left|center|right)\\1]([^"\(\)\'<>]*)\[\/siteimg\]/U';
		$rep = '<img src="'.XOOPS_URL.'/\\3" align="\\2" alt="" />';
		$replacements[0][] = $rep;
		$replacements[1][] = $rep;
		
		$patterns[] = '/\[siteimg]([^"\(\)\'<>]*)\[\/siteimg\]/U';
		$rep = '<img src="'.XOOPS_URL.'/\\1" alt="" />';
		$replacements[0][] = $rep;
		$replacements[1][] = $rep;
	}
}

}

eval( 'class '.ucfirst( $mydirname ).'_xelfinderPreload extends xelfinderPreloadBase { var $mydirname = \''.$mydirname.'\' ; var $mydirpath = \''.$mydirpath.'\' ; }' ) ;