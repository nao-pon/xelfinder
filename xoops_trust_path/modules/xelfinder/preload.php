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
	}

	function overRideDefaultImageManager() {
		$mydirname = $this->mydirname;
		$mydirpath = $this->mydirpath;
		require dirname(__FILE__).'/manager.php';
	}
}

}

eval( 'class '.ucfirst( $mydirname ).'_xelfinderPreload extends xelfinderPreloadBase { var $mydirname = "'.$mydirname.'" ; var $mydirpath = "'.$mydirpath.'" ; }' ) ;