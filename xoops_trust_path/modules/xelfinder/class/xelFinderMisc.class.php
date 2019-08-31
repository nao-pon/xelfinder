<?php

class xelFinderMisc
{
    public $myConfig;
    public $db;
    public $mydirname;
    public $mode;

    public function __construct($mydirname)
    {
        $this->db = XoopsDatabaseFactory::getDatabaseConnection();
        $this->mydirname = $mydirname;
    }

    private function authPrepare($perm, $f_uid)
    {
        global $xoopsUser, $xoopsModule;

        if (is_object($xoopsUser)) {
            if (empty($xoopsModule) || !is_object($xoopsModule)) {
                $module_handler = xoops_getHandler('module');
                $xModule = $module_handler->getByDirname($this->mydirname);
            } else {
                $xModule = $xoopsModule;
            }
            $uid = $xoopsUser->getVar('uid');
            $groups = $xoopsUser->getGroups();
            $isAdmin = $xoopsUser->isAdmin($xModule->getVar('mid'));
        } else {
            $uid = 0;
            $groups = [XOOPS_GROUP_ANONYMOUS];
            $isAdmin = false;
        }

        $isOwner = ($isAdmin || ($f_uid && $f_uid == $uid));
        $inGroup = (array_intersect($this->getGroupsByUid($f_uid), $groups)) ? true : false;

        $perm = strval($perm);
        $own = intval($perm[0], 16);
        $grp = intval($perm[1], 16);
        $gus = intval($perm[2], 16);

        return [$isOwner, $inGroup, $own, $grp, $gus, $perm];
    }

    private function checkAuth($auth, $perm, $f_uid)
    {
        list($isOwner, $inGroup, $own, $grp, $gus, $perm) = $this->authPrepare($perm, $f_uid);
        //exit(var_dump(array($isOwner, $inGroup, $own, $grp, $gus, $perm)));
        $ret = false;
        if (false !== mb_strpos($auth, 'r')) {
            $ret = (($isOwner && 4 === (4 & $own)) || ($inGroup && 4 === (4 & $grp)) || 4 === (4 & $gus));
        }
        if ($ret && false !== mb_strpos($auth, 'w')) {
            $ret = (($isOwner && 2 === (2 & $own)) || ($inGroup && 2 === (2 & $grp)) || 2 === (2 & $gus));
        }

        return $ret;
    }

    public function dbSetCharset($charset = 'utf8')
    {
        if (!$this->db) {
            return false;
        }
        $db = $this->db;
        $link = (is_object($db->conn) && 'mysqli' === get_class($db->conn)) ? $db->conn : false;
        if ($link) {
            return mysqli_set_charset($link, $charset);
        }

        return mysql_set_charset($charset);
    }

    public function readAuth($perm, $f_uid, $file_id = null)
    {
        list($isOwner, $inGroup, $own, $grp, $gus, $perm) = $this->authPrepare($perm, $f_uid);

        if ($readable = (($isOwner && 4 === (4 & $own)) || ($inGroup && 4 === (4 & $grp)) || 4 === (4 & $gus))) {
            if ($file_id && 'view' === $this->mode && !empty($this->myConfig['edit_disable_linked'])) {
                if (2 === (2 & $own) || 2 === (2 & $grp) || 2 === (2 & $gus) || 1 === (1 & $own) || 1 === (1 & $grp) || 1 === (1 & $gus)) {
                    $refer = @$_SERVER['HTTP_REFERER'];
                    if (0 === mb_strpos($refer, 'http') && !preg_match('#^' . preg_quote(XOOPS_URL) . '/[^?]+manager\.php#', $refer)) {
                        $perm = dechex($own & ~3) . dechex($grp & ~3) . dechex($gus & ~3);
                        $tbf = $this->db->prefix($this->mydirname) . '_file';
                        $sql = sprintf('UPDATE %s SET `perm`="%s" WHERE `file_id` = "%d" LIMIT 1', $tbf, $perm, $file_id);
                        $this->db->queryF($sql);
                    }
                }
            }
        }

        return $readable;
    }

    public function getGroupsByUid($uid)
    {
        if ($uid) {
            $user_handler = xoops_getHandler('user');
            $user = $user_handler->get($uid);
            $groups = $user->getGroups();
        } else {
            $groups = [ XOOPS_GROUP_ANONYMOUS ];
        }

        return $groups;
    }

    public function getUserHome($auth = 'rw', $uid = null)
    {
        if (null === $uid) {
            global $xoopsUser;
            $uid = is_object($xoopsUser) ? $xoopsUser->uid() : 0;
        }
        $tbf = $this->db->prefix($this->mydirname) . '_file';
        $sql = sprintf('SELECT file_id, perm, uid from %s WHERE home_of="%s" LIMIT 1', $tbf, $uid);
        //exit($sql);
        $ret = false;
        //$res = $this->db->query($sql);exit(var_dump($this->db->getRowsNum($res)));
        if (($res = $this->db->query($sql)) && $this->db->getRowsNum($res)) {
            list($id, $perm, $f_uid) = $this->db->fetchRow($res);
            //exit(var_dump(array($id, $perm, $f_uid)));
            if ($this->checkAuth($auth, $perm, $f_uid)) {
                $ret = $id;
            }
        }

        return $ret;
    }

    public function getGroupHome($auth = 'rw', $uid = null)
    {
        if (null === $uid) {
            global $xoopsUser;
            $user = $xoopsUser;
        } elseif ($uid) {
            $user_handler = xoops_getHandler('user');
            $user = $user_handler->get($uid);
        } else {
            return false;
        }
        $ret = false;
        $groups = $user->getGroups();
        sort($groups);
        //exit(var_dump($groups));
        if (XOOPS_GROUP_ANONYMOUS == $groups[0]) {
            return isset($groups[1]) ? $this->getUserHome($auth, '-' . $groups[1]) : false;
        }

        return $this->getUserHome($auth, '-' . $groups[0]);
    }

    public function getHash($id, $prefix = null)
    {
        if (null === $prefix) {
            $prefix = 'xe_' . $this->mydirname . '_';
        }
        $hash = strtr(base64_encode($id), '+/=', '-_.');
        $hash = rtrim($hash, '.');

        return $prefix . $hash;
    }

    public function output($file, $mime, $size, $mtime, $name = '')
    {
        $this->check_304($mtime);

        $disp = (isset($_GET['dl'])) ? 'attachment' : 'inline';

        if ('' === $name) {
            $filename = '';
        } else {
            $filenameEncoded = rawurlencode($name);
            if (false === mb_strpos($filenameEncoded, '%')) { // ASCII only
                $filename = 'filename="' . $name . '"';
            } else {
                $ua = $_SERVER['HTTP_USER_AGENT'];
                if (preg_match('/MSIE [4-8]/', $ua)) { // IE < 9 do not support RFC 6266 (RFC 2231/RFC 5987)
                    $filename = 'filename="' . $filenameEncoded . '"';
                } elseif (false === mb_strpos($ua, 'Chrome') && false !== mb_strpos($ua, 'Safari') && preg_match('#Version/[3-5]#', $ua)) { // Safari < 6
                    $filename = 'filename="' . str_replace('"', '', $file['name']) . '"';
                } else { // RFC 6266 (RFC 2231/RFC 5987)
                    $filename = 'filename*=UTF-8\'\'' . $filenameEncoded;
                }
            }
        }

        header('Content-Length: ' . $size);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disp . '; ' . $filename);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('Etag: ' . $mtime);
        header('Cache-Control: private, max-age=' . XELFINDER_CACHE_TTL);
        header('Expires: ' . gmdate('D, d M Y H:i:s', XELFINDER_UNIX_TIME + XELFINDER_CACHE_TTL) . ' GMT');
        header('Pragma:');

        if (function_exists('XC_CLASS_EXISTS') && XC_CLASS_EXISTS('HypCommonFunc')) {
            HypCommonFunc::readfile($file);
        } else {
            readfile($file);
        }
    }

    public function check_304($time)
    {
        if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $time == $_SERVER['HTTP_IF_NONE_MATCH'])
             || $time <= $this->if_modified_since()) {
            header('HTTP/1.1 304 Not Modified');
            header('Etag: ' . $time);
            header('Cache-Control: public, max-age=' . XELFINDER_CACHE_TTL);
            header('Expires: ' . gmdate('D, d M Y H:i:s', XELFINDER_UNIX_TIME + XELFINDER_CACHE_TTL) . ' GMT');
            header('Pragma:');
            exit;
        }
    }

    public function if_modified_since()
    {
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $str = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            if (false !== ($pos = mb_strpos($str, ';'))) {
                $str = mb_substr($str, 0, $pos);
            }
            if (false === mb_strpos($str, ',')) {
                $str .= ' GMT';
            }
            $time = strtotime($str);
        }

        if (isset($time) && is_int($time)) {
            return $time;
        }

        return -1;
    }

    public function exitOut($code)
    {
        switch ($code) {
            case 403:
                header('HTTP/1.0 403 Forbidden');
                exit('403 Forbidden');
            case 404:
                header('HTTP/1.0 404 Not Found');
                exit('404 Not Found');
            default:
                exit;
        }
    }
}
