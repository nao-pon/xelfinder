<?php

eval( ' function xoops_module_update_'.$mydirname.'( $module ) { return xelfinder_onupdate_base( $module , "'.$mydirname.'" ) ; } ' ) ;


if( ! function_exists( 'xelfinder_onupdate_base' ) ) {

function xelfinder_onupdate_base( $module , $mydirname )
{
	// transations on module update

	global $msgs ; // TODO :-D

	// for Cube 2.1
	if( defined( 'XOOPS_CUBE_LEGACY' ) ) {
		$root =& XCube_Root::getSingleton();
		$root->mDelegateManager->add( 'Legacy.Admin.Event.ModuleUpdate.' . ucfirst($mydirname) . '.Success', 'xelfinder_message_append_onupdate' ) ;
		$msgs = array() ;
	} else {
		if( ! is_array( $msgs ) ) $msgs = array() ;
	}

	$db = Database::getInstance() ;
	$mid = $module->getVar('mid') ;


	// TABLES (write here ALTER TABLE etc. if necessary)
	$query = "SELECT `gids` FROM ".$db->prefix($mydirname."_file") ;
	if(! $db->query($query)) {
		$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` ADD `gids` VARCHAR( 255 ) NOT NULL');
	}

	// check last update version
	$cache_dir = (defined('XOOPS_MODULE_PATH')? XOOPS_MODULE_PATH : XOOPS_ROOT_PATH . '/modules') . '/' . $mydirname . '/cache';
	$lastupdate = 0;
	if (file_exists($cache_dir . '/lastupdate.dat')) {
		$lastupdate = @unserialize(file_get_contents($cache_dir . '/lastupdate.dat'));
	}
	if (! is_numeric($lastupdate)) $lastupdate = 0;
	file_put_contents($cache_dir . '/lastupdate.dat', serialize($module->getVar('version')));
	
	// from v 0.10
	if ($lastupdate < 10) {
		$query = "SELECT `mime_filter` FROM ".$db->prefix($mydirname."_file") ;
		if(! $db->query($query)) {
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` ADD `mime_filter` VARCHAR( 255 ) NOT NULL');
		}
	}
	
	// from v 0.13
	if ($lastupdate < 13) {
		$query = "SHOW COLUMNS FROM `".$db->prefix($mydirname."_file")."` LIKE 'mime'" ;
		$res = $db->query($query);
		$dat = $db->fetchArray($res);
		if ($dat['Type'] !== 'varchar(255)') {
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `mime` `mime` varchar(255) NOT NULL DEFAULT \'unknown\'');
		}
	}

	// from v 0.17
	if ($lastupdate < 17) {
		$query = "SELECT `id` FROM ".$db->prefix($mydirname."_userdat") ;
		if(! $db->query($query)) {
			$db->queryF(
				'CREATE TABLE `'.$db->prefix($mydirname.'_userdat').'`'.
				'(
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `uid` int(10) unsigned NOT NULL,
				  `key` varchar(255) NOT NULL,
				  `data` blob NOT NULL,
				  `mtime` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `uid_key` (`uid`,`key`)
				) ENGINE=MyISAM' );
		}
	}
	
	//from v0.22
	if ($lastupdate < 22) {
		$query = "SELECT `local_path` FROM ".$db->prefix($mydirname."_file") ;
		if(! $db->query($query)) {
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` ADD `local_path` VARCHAR( 255 ) NOT NULL');
		}
	}
	
	//from v0.66 add default value for strict mode
	if ($lastupdate < 66) {
		$query = "SHOW COLUMNS FROM `".$db->prefix($mydirname."_file")."` LIKE 'parent_id'" ;
		$res = $db->query($query);
		$dat = $db->fetchArray($res);
		if ($dat['Default'] === NULL) {
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `parent_id` `parent_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `name` `name` varchar(255) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `size` `size` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `ctime` `ctime` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `mtime` `mtime` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `perm` `perm` varchar(3) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `uid` `uid` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `gid` `gid` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `home_of` `home_of` int(10) DEFAULT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `width` `width` int(11) NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `height` `height` int(11) NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `gids` `gids` varchar(255) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `mime_filter` `mime_filter` varchar(255) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_file").'` CHANGE `local_path` `local_path` varchar(255) NOT NULL DEFAULT \'\'');
			// link
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_link").'` CHANGE `file_id` `file_id` int(11) NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_link").'` CHANGE `mid` `mid` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_link").'` CHANGE `param` `param` varchar(25) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_link").'` CHANGE `val` `val` varchar(25) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_link").'` CHANGE `title` `title` varchar(255) NOT NULL DEFAULT \'\'');
			// userdat
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_userdat").'` CHANGE `uid` `uid` int(10) unsigned NOT NULL DEFAULT \'0\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_userdat").'` CHANGE `key` `key` varchar(255) NOT NULL DEFAULT \'\'');
			$db->queryF('ALTER TABLE `'.$db->prefix($mydirname."_userdat").'` CHANGE `mtime` `mtime` int(10) unsigned NOT NULL DEFAULT \'0\'');
		}
	}
	
	// for version < 0.99 remove unless tmb file
	if ($lastupdate < 99) {
		$msgs[] = 'checking unless tmbs (Version < 0.99)';
		$tmbdir = $cache_dir . '/tmb';
		$_res = false;
		if ($handle = opendir($tmbdir)) {
			while (false !== ($entry = readdir($handle))) {
				if (preg_match('/^[a-zA-Z]{1,2}[0-9]{1,3}_.+\.png$/', $entry)) {
					//$msgs[] = $tmbdir.'/'.$entry;
					$_res = @unlink($tmbdir.'/'.$entry);
				}
			}
		}
		if ($_res) $msgs[] = 'removed unless tmbs';
	}
	
	// TEMPLATES (all templates have been already removed by modulesadmin)
	$tplfile_handler =& xoops_gethandler( 'tplfile' ) ;
	$tpl_path = dirname(__FILE__).'/templates' ;
	if( $handler = @opendir( $tpl_path . '/' ) ) {
		while( ( $file = readdir( $handler ) ) !== false ) {
			if( substr( $file , 0 , 1 ) == '.' ) continue ;
			$file_path = $tpl_path . '/' . $file ;
			if( is_file( $file_path ) ) {
				$mtime = intval( @filemtime( $file_path ) ) ;
				$tplfile =& $tplfile_handler->create() ;
				$tplfile->setVar( 'tpl_source' , file_get_contents( $file_path ) , true ) ;
				$tplfile->setVar( 'tpl_refid' , $mid ) ;
				$tplfile->setVar( 'tpl_tplset' , 'default' ) ;
				$tplfile->setVar( 'tpl_file' , $mydirname . '_' . $file ) ;
				$tplfile->setVar( 'tpl_desc' , '' , true ) ;
				$tplfile->setVar( 'tpl_module' , $mydirname ) ;
				$tplfile->setVar( 'tpl_lastmodified' , $mtime ) ;
				$tplfile->setVar( 'tpl_lastimported' , 0 ) ;
				$tplfile->setVar( 'tpl_type' , 'module' ) ;
				if( ! $tplfile_handler->insert( $tplfile ) ) {
					$msgs[] = '<span style="color:#ff0000;">ERROR: Could not insert template <b>'.htmlspecialchars($mydirname.'_'.$file, ENT_COMPAT, _CHARSET).'</b> to the database.</span>';
				} else {
					$tplid = $tplfile->getVar( 'tpl_id' ) ;
					$msgs[] = 'Template <b>'.htmlspecialchars($mydirname.'_'.$file, ENT_COMPAT, _CHARSET).'</b> added to the database. (ID: <b>'.$tplid.'</b>)';
					// generate compiled file
					include_once XOOPS_ROOT_PATH.'/class/xoopsblock.php' ;
					include_once XOOPS_ROOT_PATH.'/class/template.php' ;
					if( ! xoops_template_touch( $tplid ) ) {
						$msgs[] = '<span style="color:#ff0000;">ERROR: Failed compiling template <b>'.htmlspecialchars($mydirname.'_'.$file, ENT_COMPAT, _CHARSET).'</b>.</span>';
					} else {
						$msgs[] = 'Template <b>'.htmlspecialchars($mydirname.'_'.$file, ENT_COMPAT, _CHARSET).'</b> compiled.</span>';
					}
				}
			}
		}
		closedir( $handler ) ;
	}
	include_once XOOPS_ROOT_PATH.'/class/xoopsblock.php' ;
	include_once XOOPS_ROOT_PATH.'/class/template.php' ;
	xoops_template_clear_module_cache( $mid ) ;

	return true ;
}

function xelfinder_message_append_onupdate( &$module_obj , &$log )
{
	if( is_array( @$GLOBALS['msgs'] ) ) {
		foreach( $GLOBALS['msgs'] as $message ) {
			$log->add( strip_tags( $message ) ) ;
		}
	}

	// use mLog->addWarning() or mLog->addError() if necessary
}

}

