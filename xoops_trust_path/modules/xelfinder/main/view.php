<?php

list(,$file_id) = explode('/', $path_info);

$file_id = (int)$file_id;

$query = 'SELECT `mime`, `size`, `mtime`, `perm` FROM `' . $xoopsDB->prefix($mydirname) . '_file`' . ' WHERE file_id = ' . $file_id . ' LIMIT 1';

if ($res = $xoopsDB->query($query)) {
	list($mime, $size, $mtime, $perm) = $xoopsDB->fetchRow($res);

	// @TODO perm check

	header('Content-Length: '.$size);
	header('Content-Type: '.$mime);
	header('Last-Modified: '  . gmdate( "D, d M Y H:i:s", $mtime ) . " GMT" );

	readfile(XOOPS_TRUST_PATH . '/uploads/xelfinder/'. $mydirname . '_' . $file_id);
}
