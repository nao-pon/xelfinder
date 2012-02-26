<?php

$file_id = 0;
if (isset($path_info)) {
	list(,$file_id) = explode('/', $path_info);
} elseif (isset($_GET['file'])) {
	list($file_id) = explode('/', $_GET['file']);
}
$file_id = (int)$file_id;

while( ob_get_level() ) {
	if (! @ ob_end_clean()) {
		break;
	}
}

$query = 'SELECT `mime`, `size`, `mtime`, `perm`, `uid` FROM `' . $xoopsDB->prefix($mydirname) . '_file`' . ' WHERE file_id = ' . $file_id . ' LIMIT 1';
if ($file_id && ($res = $xoopsDB->query($query)) && $xoopsDB->getRowsNum($res)) {
	list($mime, $size, $mtime, $perm, $uid) = $xoopsDB->fetchRow($res);
	if (xelfinder_readAuth($perm, $uid)) {
 		header('Content-Length: '.$size);
 		header('Content-Type: '.$mime);
 		header('Last-Modified: '  . gmdate( "D, d M Y H:i:s", $mtime ) . " GMT" );

 		$file = XOOPS_TRUST_PATH . '/uploads/xelfinder/'. rawurlencode(substr(XOOPS_URL, 7)) . '_' . $mydirname . '_' . $file_id;
 		if (function_exists('XC_CLASS_EXISTS') && XC_CLASS_EXISTS('HypCommonFunc')) {
 			HypCommonFunc::readfile($file);
 		} else {
 			readfile($file);
 		}
 	} else {
		header('HTTP/1.0 403 Forbidden');
		exit('403 Forbidden');
	}
} else {
	header('HTTP/1.0 404 Not Found');
	exit($file_id  . ' 404 Not Found');
}

function xelfinder_readAuth($perm, $f_uid) {
	global $xoopsUser, $xoopsModule;
	if (is_object($xoopsUser)) {
		$uid = $xoopsUser->getVar('uid');
		$groups = $xoopsUser->getGroups();
		$isAdmin = $xoopsUser->isAdmin($xoopsModule->getVar('mid'));
	} else {
		$uid = 0;
		$groups = array(XOOPS_GROUP_ANONYMOUS);
		$isAdmin = false;
	}

	$isOwner = ($isAdmin || ($f_uid && $f_uid == $uid));
	$inGroup = (array_intersect(xelfinderGetGroupsByUid($f_uid), $groups));

	$perm = strval($perm);
	$own = intval($perm[0], 16);
	$grp = intval($perm[1], 16);
	$gus = intval($perm[2], 16);

	return (($isOwner && (4 & $own) === 4) || ($inGroup && (4 & $grp) === 4) || (4 & $gus) === 4);

}

function xelfinderGetGroupsByUid($uid) {
	if ($uid) {
		$user_handler =& xoops_gethandler('user');
		$user =& $user_handler->get( $uid );
		$groups = $user->getGroups();
	} else {
		$groups = array( XOOPS_GROUP_ANONYMOUS );
	}
	return $groups;
}
