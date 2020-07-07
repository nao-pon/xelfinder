<?php

if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

if( ! preg_match( '/^[0-9a-zA-Z_-]+$/' , $mydirname ) ) exit ;

if( ! class_exists( 'xelfinderPreloadBase' ) ) {
    class xelfinderPreloadBase extends XCube_ActionFilter
    {

    public function preBlockFilter()
        {
            $this->mRoot->mDelegateManager->delete('Legacypage.Imagemanager.Access', 'Legacy_EventFunction::imageManager');
            $this->mRoot->mDelegateManager->add(
                'Legacypage.Imagemanager.Access',
                [$this, 'overRideDefaultImageManager'],
                XCUBE_DELEGATE_PRIORITY_FIRST
            );

            $this->mRoot->mDelegateManager->add(
                'Legacy_TextFilter.MakeXCodeConvertTable',
                [$this, 'addXCodeConvertTable'],
                XCUBE_DELEGATE_PRIORITY_NORMAL - 1
            );

            $this->mRoot->mDelegateManager->add(
                'Legacy_ActionFrame.CreateAction',
                [$this, 'overRideImagecategoryList'],
                XCUBE_DELEGATE_PRIORITY_FIRST
            );
        }

    public function overRideDefaultImageManager()
        {
            $mydirname = $this->mydirname;

            $root      = XCube_Root::getSingleton();
            $xoopsUser = $root->mContext->mXoopsUser;

            // check module readable
            $module_handler = xoops_getHandler('module');
            if ($XoopsModule = $module_handler->getByDirname($mydirname)) {
                $moduleperm_handler = xoops_getHandler('groupperm');
                if ($moduleperm_handler->checkRight('module_read', $XoopsModule->getVar('mid'), (is_object($xoopsUser) ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS))) {
                    $mydirpath          = $this->mydirpath;
                    $use_bbcode_siteimg = 1;
                    if (!isset($_GET['cb']) && (!isset($_GET['getfile']) || 'ckeditor' !== $_GET['getfile'])) {
                        $_GET['cb'] = 'bbcode';
                    }
                    require __DIR__ . '/manager.php';
                }
            }

            // call legacy imageManager
            require_once XOOPS_MODULE_PATH . '/legacy/kernel/Legacy_EventFunctions.class.php';
            Legacy_EventFunction::imageManager();
        }

    public function addXCodeConvertTable(&$patterns, &$replacements)
        {
            $patterns[]        = '/\[siteimg align=([\'"]?)(left|center|right)\\1]([^"\(\)\'<>]*)\[\/siteimg\]/U';
            $rep               = '<img src="' . XOOPS_URL . '/\\3" align="\\2" alt="" />';
            $replacements[0][] = $rep;
            $replacements[1][] = $rep;

            $patterns[]        = '/\[siteimg]([^"\(\)\'<>]*)\[\/siteimg\]/U';
            $rep               = '<img src="' . XOOPS_URL . '/\\1" alt="" />';
            $replacements[0][] = $rep;
            $replacements[1][] = $rep;
        }

        public function overRideImagecategoryList(&$actionFrame)
        {
            if ('ImagecategoryList' === $actionFrame->mActionName) {
                $image_handler = xoops_getModuleHandler('image');
                if ($image_handler) {
                    $total_criteria = new CriteriaCompo();
                    if (!$image_handler->getCount($total_criteria)) {
                        $root = XCube_Root::getSingleton();
                        $root->mController->executeForward(XOOPS_MODULE_URL . '/' . $this->mydirname . '/');
                    }
                }
            }
        }
    }

}

eval( 'class '.ucfirst( $mydirname ).'_xelfinderPreload extends xelfinderPreloadBase { var $mydirname = \''.$mydirname.'\' ; var $mydirpath = \''.$mydirpath.'\' ; }' ) ;
