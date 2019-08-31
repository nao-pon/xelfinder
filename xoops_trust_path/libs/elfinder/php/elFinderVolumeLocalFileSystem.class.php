<?php

// Implement similar functionality in PHP 5.2 or 5.3
// http://php.net/manual/class.recursivecallbackfilteriterator.php#110974
if (!class_exists('RecursiveCallbackFilterIterator', false)) {
    class RecursiveCallbackFilterIterator extends RecursiveFilterIterator
    {
        private $callback;

        public function __construct(RecursiveIterator $iterator, $callback)
        {
            $this->callback = $callback;
            parent::__construct($iterator);
        }

        public function accept()
        {
            return call_user_func($this->callback, parent::current(), parent::key(), parent::getInnerIterator());
        }

        public function getChildren()
        {
            return new self($this->getInnerIterator()->getChildren(), $this->callback);
        }
    }
}

/**
 * elFinder driver for local filesystem.
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 **/
class elFinderVolumeLocalFileSystem extends elFinderVolumeDriver
{
    /**
     * Driver id
     * Must be started from letter and contains [a-z0-9]
     * Used as part of volume id
     *
     * @var string
     **/
    protected $driverId = 'l';

    /**
     * Required to count total archive files size
     *
     * @var int
     **/
    protected $archiveSize = 0;

    /**
     * Is checking stat owner
     *
     * @var        boolean
     */
    protected $statOwner = false;

    /**
     * Path to quarantine directory
     *
     * @var string
     */
    private $quarantine;

    /**
     * Constructor
     * Extend options with required fields
     *
     * @author Dmitry (dio) Levashov
     */
    public function __construct()
    {
        $this->options['alias'] = '';              // alias to replace root dir name
        $this->options['dirMode'] = 0755;            // new dirs mode
        $this->options['fileMode'] = 0644;            // new files mode
        $this->options['quarantine'] = '.quarantine'; // quarantine folder name - required to check archive (must be hidden)
        $this->options['rootCssClass'] = 'elfinder-navbar-root-local';
        $this->options['followSymLinks'] = true;
        $this->options['detectDirIcon'] = '';         // file name that is detected as a folder icon e.g. '.diricon.png'
        $this->options['keepTimestamp'] = ['copy', 'move']; // keep timestamp at inner filesystem allowed 'copy', 'move' and 'upload'
        $this->options['substituteImg'] = true;       // support substitute image with dim command
        $this->options['statCorrector'] = null;       // callable to correct stat data `function(&$stat, $path, $statOwner, $volumeDriveInstance){}`
    }

    /*********************************************************************/
    /*                        INIT AND CONFIGURE                         */
    /*********************************************************************/

    /**
     * Prepare driver before mount volume.
     * Return true if volume is ready.
     *
     * @return bool
     **/
    protected function init()
    {
        // Normalize directory separator for windows
        if (DIRECTORY_SEPARATOR !== '/') {
            foreach (['path', 'tmbPath', 'tmpPath', 'quarantine'] as $key) {
                if (!empty($this->options[$key])) {
                    $this->options[$key] = str_replace('/', DIRECTORY_SEPARATOR, $this->options[$key]);
                }
            }
            // PHP >= 7.1 Supports UTF-8 path on Windows
            if (version_compare(PHP_VERSION, '7.1', '>=')) {
                $this->options['encoding'] = '';
                $this->options['locale'] = '';
            }
        }
        if (!$cwd = getcwd()) {
            return $this->setError('elFinder LocalVolumeDriver requires a result of getcwd().');
        }
        // detect systemRoot
        if (!isset($this->options['systemRoot'])) {
            if ($cwd[0] === $this->separator || $this->root[0] === $this->separator) {
                $this->systemRoot = $this->separator;
            } elseif (preg_match('/^([a-zA-Z]:' . preg_quote($this->separator, '/') . ')/', $this->root, $m)) {
                $this->systemRoot = $m[1];
            } elseif (preg_match('/^([a-zA-Z]:' . preg_quote($this->separator, '/') . ')/', $cwd, $m)) {
                $this->systemRoot = $m[1];
            }
        }
        $this->root = $this->getFullPath($this->root, $cwd);
        if (!empty($this->options['startPath'])) {
            $this->options['startPath'] = $this->getFullPath($this->options['startPath'], $this->root);
        }

        if (null === $this->options['syncChkAsTs']) {
            $this->options['syncChkAsTs'] = true;
        }
        if (null === $this->options['syncCheckFunc']) {
            $this->options['syncCheckFunc'] = [$this, 'localFileSystemInotify'];
        }
        // check 'statCorrector'
        if (empty($this->options['statCorrector']) || !is_callable($this->options['statCorrector'])) {
            $this->options['statCorrector'] = null;
        }

        return true;
    }

    /**
     * Configure after successfull mount.
     *
     * @throws elFinderAbortException
     * @return void
     * @author Dmitry (dio) Levashov
     */
    protected function configure()
    {
        $root = $this->stat($this->root);

        // chek thumbnails path
        if ($this->options['tmbPath']) {
            $this->options['tmbPath'] = false === mb_strpos($this->options['tmbPath'], DIRECTORY_SEPARATOR)
                // tmb path set as dirname under root dir
                ? $this->_abspath($this->options['tmbPath'])
                // tmb path as full path
                : $this->_normpath($this->options['tmbPath']);
        }

        parent::configure();

        // set $this->tmp by options['tmpPath']
        $this->tmp = '';
        if (!empty($this->options['tmpPath'])) {
            if ((is_dir($this->options['tmpPath']) || mkdir($this->options['tmpPath'], 0755, true)) && is_writable($this->options['tmpPath'])) {
                $this->tmp = $this->options['tmpPath'];
            }
        }
        if (!$this->tmp && ($tmp = elFinder::getStaticVar('commonTempPath'))) {
            $this->tmp = $tmp;
        }

        // if no thumbnails url - try detect it
        if ($root['read'] && !$this->tmbURL && $this->URL) {
            if (0 === mb_strpos($this->tmbPath, $this->root)) {
                $this->tmbURL = $this->URL . str_replace(DIRECTORY_SEPARATOR, '/', mb_substr($this->tmbPath, mb_strlen($this->root) + 1));
                if (preg_match('|[^/?&=]$|', $this->tmbURL)) {
                    $this->tmbURL .= '/';
                }
            }
        }

        // check quarantine dir
        $this->quarantine = '';
        if (!empty($this->options['quarantine'])) {
            if (is_dir($this->options['quarantine'])) {
                if (is_writable($this->options['quarantine'])) {
                    $this->quarantine = $this->options['quarantine'];
                }
                $this->options['quarantine'] = '';
            } else {
                $this->quarantine = $this->_abspath($this->options['quarantine']);
                if ((!is_dir($this->quarantine) && !mkdir($this->quarantine)) || !is_writable($this->quarantine)) {
                    $this->options['quarantine'] = $this->quarantine = '';
                }
            }
        }

        if (!$this->quarantine) {
            if (!$this->tmp) {
                $this->archivers['extract'] = [];
                $this->disabled[] = 'extract';
            } else {
                $this->quarantine = $this->tmp;
            }
        }

        if ($this->options['quarantine']) {
            $this->attributes[] = [
                'pattern' => '~^' . preg_quote(DIRECTORY_SEPARATOR . $this->options['quarantine']) . '$~',
                'read' => false,
                'write' => false,
                'locked' => true,
                'hidden' => true,
            ];
        }

        if (!empty($this->options['keepTimestamp'])) {
            $this->options['keepTimestamp'] = array_flip($this->options['keepTimestamp']);
        }

        $this->statOwner = (!empty($this->options['statOwner']));
    }

    /**
     * Long pooling sync checker
     * This function require server command `inotifywait`
     * If `inotifywait` need full path, Please add `define('ELFINER_INOTIFYWAIT_PATH', '/PATH_TO/inotifywait');` into connector.php
     *
     * @param string $path
     * @param int    $standby
     * @param number $compare
     *
     * @throws elFinderAbortException
     * @return number|bool
     */
    public function localFileSystemInotify($path, $standby, $compare)
    {
        if (isset($this->sessionCache['localFileSystemInotify_disable'])) {
            return false;
        }
        $path = realpath($path);
        $mtime = filemtime($path);
        if (!$mtime) {
            return false;
        }
        if ($mtime != $compare) {
            return $mtime;
        }
        $inotifywait = defined('ELFINER_INOTIFYWAIT_PATH') ? ELFINER_INOTIFYWAIT_PATH : 'inotifywait';
        $standby = max(1, intval($standby));
        $cmd = $inotifywait . ' ' . escapeshellarg($path) . ' -t ' . $standby . ' -e moved_to,moved_from,move,close_write,delete,delete_self';
        $this->procExec($cmd, $o, $r);
        if (0 === $r) {
            // changed
            clearstatcache();
            if (file_exists($path)) {
                $mtime = filemtime($path); // error on busy?
                return $mtime ? $mtime : time();
            }
            // target was removed
            return 0;
        } elseif (2 === $r) {
            // not changed (timeout)
            return $compare;
        }
        // error
        // cache to $_SESSION
        $this->sessionCache['localFileSystemInotify_disable'] = true;
        $this->session->set($this->id, $this->sessionCache);

        return false;
    }

    /*********************************************************************/
    /*                               FS API                              */
    /*********************************************************************/

    /*********************** paths/urls *************************/

    /**
     * Return parent directory path
     *
     * @param  string $path file path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _dirname($path)
    {
        return dirname($path);
    }

    /**
     * Return file name
     *
     * @param  string $path file path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _basename($path)
    {
        return basename($path);
    }

    /**
     * Join dir name and file name and retur full path
     *
     * @param  string $dir
     * @param  string $name
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _joinPath($dir, $name)
    {
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Return normalized path, this works the same as os.path.normpath() in Python
     *
     * @param  string $path path
     *
     * @return string
     * @author Troex Nevelin
     **/
    protected function _normpath($path)
    {
        if (empty($path)) {
            return '.';
        }

        $changeSep = (DIRECTORY_SEPARATOR !== '/');
        if ($changeSep) {
            $drive = '';
            if (preg_match('/^([a-zA-Z]:)(.*)/', $path, $m)) {
                $drive = $m[1];
                $path = $m[2] ? $m[2] : '/';
            }
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }

        if (0 === mb_strpos($path, '/')) {
            $initial_slashes = true;
        } else {
            $initial_slashes = false;
        }

        if (($initial_slashes)
            && (0 === mb_strpos($path, '//'))
            && (false === mb_strpos($path, '///'))) {
            $initial_slashes = 2;
        }

        $initial_slashes = (int)$initial_slashes;

        $comps = explode('/', $path);
        $new_comps = [];
        foreach ($comps as $comp) {
            if (in_array($comp, ['', '.'])) {
                continue;
            }

            if (('..' != $comp)
                || (!$initial_slashes && !$new_comps)
                || ($new_comps && ('..' == end($new_comps)))) {
                array_push($new_comps, $comp);
            } elseif ($new_comps) {
                array_pop($new_comps);
            }
        }
        $comps = $new_comps;
        $path = implode('/', $comps);
        if ($initial_slashes) {
            $path = str_repeat('/', $initial_slashes) . $path;
        }

        if ($changeSep) {
            $path = $drive . str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $path ? $path : '.';
    }

    /**
     * Return file path related to root dir
     *
     * @param  string $path file path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _relpath($path)
    {
        if ($path === $this->root) {
            return '';
        }
        if (0 === mb_strpos($path, $this->root)) {
            return ltrim(mb_substr($path, mb_strlen($this->root)), DIRECTORY_SEPARATOR);
        }
        // for link
        return $path;
    }

    /**
     * Convert path related to root dir into real path
     *
     * @param  string $path file path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _abspath($path)
    {
        if (DIRECTORY_SEPARATOR === $path) {
            return $this->root;
        }
        if (DIRECTORY_SEPARATOR === $path[0]) {
            // for link
            return $path;
        }

        return $this->_joinPath($this->root, $path);
    }

    /**
     * Return fake path started from root dir
     *
     * @param  string $path file path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _path($path)
    {
        return $this->rootName . ($path == $this->root ? '' : $this->separator . $this->_relpath($path));
    }

    /**
     * Return true if $path is children of $parent
     *
     * @param  string $path   path to check
     * @param  string $parent parent path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _inpath($path, $parent)
    {
        $cwd = getcwd();
        $real_path = $this->getFullPath($path, $cwd);
        $real_parent = $this->getFullPath($parent, $cwd);
        if ($real_path && $real_parent) {
            return $real_path === $real_parent || 0 === mb_strpos($real_path, rtrim($real_parent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        }

        return false;
    }

    /***************** file stat ********************/

    /**
     * Return stat for given path.
     * Stat contains following fields:
     * - (int)    size    file size in b. required
     * - (int)    ts      file modification time in unix time. required
     * - (string) mime    mimetype. required for folders, others - optionally
     * - (bool)   read    read permissions. required
     * - (bool)   write   write permissions. required
     * - (bool)   locked  is object locked. optionally
     * - (bool)   hidden  is object hidden. optionally
     * - (string) alias   for symlinks - link target path relative to root path. optionally
     * - (string) target  for symlinks - link target path. optionally
     * If file does not exists - returns empty array or false.
     *
     * @param  string $path file path
     *
     * @return array|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _stat($path)
    {
        $stat = [];

        if (!file_exists($path) && !is_link($path)) {
            return $stat;
        }

        //Verifies the given path is the root or is inside the root. Prevents directory traveral.
        if (!$this->_inpath($path, $this->root)) {
            return $stat;
        }

        $stat['isowner'] = false;
        $linkreadable = false;
        if ($path != $this->root && is_link($path)) {
            if (!$this->options['followSymLinks']) {
                return [];
            }
            if (!($target = $this->readlink($path))
                || $target == $path) {
                if (null === $target) {
                    $stat = [];

                    return $stat;
                }
                $stat['mime'] = 'symlink-broken';
                $target = readlink($path);
                $lstat = lstat($path);
                $ostat = $this->getOwnerStat($lstat['uid'], $lstat['gid']);
                $linkreadable = !empty($ostat['isowner']);
            }
            $stat['alias'] = $this->_path($target);
            $stat['target'] = $target;
        }

        $readable = is_readable($path);

        if ($readable) {
            $size = sprintf('%u', filesize($path));
            $stat['ts'] = filemtime($path);
            if ($this->statOwner) {
                $fstat = stat($path);
                $uid = $fstat['uid'];
                $gid = $fstat['gid'];
                $stat['perm'] = mb_substr((string)decoct($fstat['mode']), -4);
                $stat = array_merge($stat, $this->getOwnerStat($uid, $gid));
            }
        }

        if (($dir = is_dir($path)) && $this->options['detectDirIcon']) {
            $favicon = $path . DIRECTORY_SEPARATOR . $this->options['detectDirIcon'];
            if ($this->URL && file_exists($favicon)) {
                $stat['icon'] = $this->URL . str_replace(DIRECTORY_SEPARATOR, '/', mb_substr($favicon, mb_strlen($this->root) + 1));
            }
        }

        if (!isset($stat['mime'])) {
            $stat['mime'] = $dir ? 'directory' : $this->mimetype($path);
        }
        //logical rights first
        $stat['read'] = ($linkreadable || $readable) ? null : false;
        $stat['write'] = is_writable($path) ? null : false;

        if (null === $stat['read']) {
            if ($dir) {
                $stat['size'] = 0;
            } elseif (isset($size)) {
                $stat['size'] = $size;
            }
        }

        if ($this->options['statCorrector']) {
            call_user_func_array($this->options['statCorrector'], [&$stat, $path, $this->statOwner, $this]);
        }

        return $stat;
    }

    /**
     * Get stat `owner`, `group` and `isowner` by `uid` and `gid`
     * Sub-fuction of _stat() and _scandir()
     *
     * @param int $uid
     * @param int $gid
     *
     * @return array  stat
     */
    protected function getOwnerStat($uid, $gid)
    {
        static $names = null;
        static $phpuid = null;

        if (null === $names) {
            $names = ['uid' => [], 'gid' => []];
        }
        if (null === $phpuid) {
            if (is_callable('posix_getuid')) {
                $phpuid = posix_getuid();
            } else {
                $phpuid = 0;
            }
        }

        $stat = [];

        if ($uid) {
            $stat['isowner'] = ($phpuid == $uid);
            if (isset($names['uid'][$uid])) {
                $stat['owner'] = $names['uid'][$uid];
            } elseif (is_callable('posix_getpwuid')) {
                $pwuid = posix_getpwuid($uid);
                $stat['owner'] = $names['uid'][$uid] = $pwuid['name'];
            } else {
                $stat['owner'] = $names['uid'][$uid] = $uid;
            }
        }
        if ($gid) {
            if (isset($names['gid'][$gid])) {
                $stat['group'] = $names['gid'][$gid];
            } elseif (is_callable('posix_getgrgid')) {
                $grgid = posix_getgrgid($gid);
                $stat['group'] = $names['gid'][$gid] = $grgid['name'];
            } else {
                $stat['group'] = $names['gid'][$gid] = $gid;
            }
        }

        return $stat;
    }

    /**
     * Return true if path is dir and has at least one childs directory
     *
     * @param  string $path dir path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _subdirs($path)
    {
        $dirs = false;
        if (is_dir($path) && is_readable($path)) {
            if (class_exists('FilesystemIterator', false)) {
                $dirItr = new ParentIterator(
                    new RecursiveDirectoryIterator(
                        $path,
                        FilesystemIterator::SKIP_DOTS |
                        FilesystemIterator::CURRENT_AS_SELF |
                        (defined('RecursiveDirectoryIterator::FOLLOW_SYMLINKS') ?
                            RecursiveDirectoryIterator::FOLLOW_SYMLINKS : 0)
                    )
                );
                $dirItr->rewind();
                if ($dirItr->hasChildren()) {
                    $dirs = true;
                    $name = $dirItr->getSubPathName();
                    while ($dirItr->valid()) {
                        if (!$this->attr($path . DIRECTORY_SEPARATOR . $name, 'read', null, true)) {
                            $dirs = false;
                            $dirItr->next();
                            $name = $dirItr->getSubPathName();
                            continue;
                        }
                        $dirs = true;
                        break;
                    }
                }
            } else {
                $path = strtr($path, ['[' => '\\[', ']' => '\\]', '*' => '\\*', '?' => '\\?']);

                return (bool)glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            }
        }

        return $dirs;
    }

    /**
     * Return object width and height
     * Usualy used for images, but can be realize for video etc...
     *
     * @param  string $path file path
     * @param  string $mime file mime type
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _dimensions($path, $mime)
    {
        clearstatcache();

        return 0 === mb_strpos($mime, 'image') && is_readable($path) && false !== ($s = getimagesize($path))
            ? $s[0] . 'x' . $s[1]
            : false;
    }

    /******************** file/dir content *********************/

    /**
     * Return symlink target file
     *
     * @param  string $path link path
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function readlink($path)
    {
        if (!($target = readlink($path))) {
            return null;
        }

        if (0 !== mb_strpos($target, $this->systemRoot)) {
            $target = $this->_joinPath(dirname($path), $target);
        }

        if (!file_exists($target)) {
            return false;
        }

        return $target;
    }

    /**
     * Return files list in directory.
     *
     * @param  string $path dir path
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function _scandir($path)
    {
        elFinder::checkAborted();
        $files = [];
        $cache = [];
        $dirWritable = is_writable($path);
        $dirItr = [];
        $followSymLinks = $this->options['followSymLinks'];

        try {
            $dirItr = new DirectoryIterator($path);
        } catch (UnexpectedValueException $e) {
        }

        foreach ($dirItr as $file) {
            try {
                if ($file->isDot()) {
                    continue;
                }

                $files[] = $fpath = $file->getPathname();

                $br = false;
                $stat = [];

                $stat['isowner'] = false;
                $linkreadable = false;
                if ($file->isLink()) {
                    if (!$followSymLinks) {
                        continue;
                    }
                    if (!($target = $this->readlink($fpath))
                        || $target == $fpath) {
                        if (null === $target) {
                            $stat = [];
                            $br = true;
                        } else {
                            $_path = $fpath;
                            $stat['mime'] = 'symlink-broken';
                            $target = readlink($_path);
                            $lstat = lstat($_path);
                            $ostat = $this->getOwnerStat($lstat['uid'], $lstat['gid']);
                            $linkreadable = !empty($ostat['isowner']);
                            $dir = false;
                            $stat['alias'] = $this->_path($target);
                            $stat['target'] = $target;
                        }
                    } else {
                        $dir = is_dir($target);
                        $stat['alias'] = $this->_path($target);
                        $stat['target'] = $target;
                        $stat['mime'] = $dir ? 'directory' : $this->mimetype($stat['alias']);
                    }
                } else {
                    if (($dir = $file->isDir()) && $this->options['detectDirIcon']) {
                        $path = $file->getPathname();
                        $favicon = $path . DIRECTORY_SEPARATOR . $this->options['detectDirIcon'];
                        if ($this->URL && file_exists($favicon)) {
                            $stat['icon'] = $this->URL . str_replace(DIRECTORY_SEPARATOR, '/', mb_substr($favicon, mb_strlen($this->root) + 1));
                        }
                    }
                    $stat['mime'] = $dir ? 'directory' : $this->mimetype($fpath);
                }
                $size = sprintf('%u', $file->getSize());
                $stat['ts'] = $file->getMTime();
                if (!$br) {
                    if ($this->statOwner && !$linkreadable) {
                        $uid = $file->getOwner();
                        $gid = $file->getGroup();
                        $stat['perm'] = mb_substr((string)decoct($file->getPerms()), -4);
                        $stat = array_merge($stat, $this->getOwnerStat($uid, $gid));
                    }

                    //logical rights first
                    $stat['read'] = ($linkreadable || $file->isReadable()) ? null : false;
                    $stat['write'] = $file->isWritable() ? null : false;
                    $stat['locked'] = $dirWritable ? null : true;

                    if (null === $stat['read']) {
                        $stat['size'] = $dir ? 0 : $size;
                    }

                    if ($this->options['statCorrector']) {
                        call_user_func_array($this->options['statCorrector'], [&$stat, $fpath, $this->statOwner, $this]);
                    }
                }

                $cache[] = [$fpath, $stat];
            } catch (RuntimeException $e) {
                continue;
            }
        }

        if ($cache) {
            $cache = $this->convEncOut($cache, false);
            foreach ($cache as $d) {
                $this->updateCache($d[0], $d[1]);
            }
        }

        return $files;
    }

    /**
     * Open file and return file pointer
     *
     * @param  string $path file path
     * @param string  $mode
     *
     * @return false|resource
     * @internal param bool $write open file for writing
     * @author   Dmitry (dio) Levashov
     */
    protected function _fopen($path, $mode = 'rb')
    {
        return fopen($path, $mode);
    }

    /**
     * Close opened file
     *
     * @param  resource $fp file pointer
     * @param string    $path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     */
    protected function _fclose($fp, $path = '')
    {
        return (is_resource($fp) && fclose($fp));
    }

    /********************  file/dir manipulations *************************/

    /**
     * Create dir and return created dir path or false on failed
     *
     * @param  string $path parent dir path
     * @param string  $name new directory name
     *
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkdir($path, $name)
    {
        $path = $this->_joinPath($path, $name);

        if (mkdir($path)) {
            chmod($path, $this->options['dirMode']);

            return $path;
        }

        return false;
    }

    /**
     * Create file and return it's path or false on failed
     *
     * @param  string $path parent dir path
     * @param string  $name new file name
     *
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkfile($path, $name)
    {
        $path = $this->_joinPath($path, $name);

        if (($fp = fopen($path, 'wb'))) {
            fclose($fp);
            chmod($path, $this->options['fileMode']);

            return $path;
        }

        return false;
    }

    /**
     * Create symlink
     *
     * @param  string $source    file to link to
     * @param  string $targetDir folder to create link in
     * @param  string $name      symlink name
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _symlink($source, $targetDir, $name)
    {
        return symlink($source, $this->_joinPath($targetDir, $name));
    }

    /**
     * Copy file into another file
     *
     * @param  string $source    source file path
     * @param  string $targetDir target directory path
     * @param  string $name      new file name
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _copy($source, $targetDir, $name)
    {
        $mtime = filemtime($source);
        $target = $this->_joinPath($targetDir, $name);
        if ($ret = copy($source, $target)) {
            isset($this->options['keepTimestamp']['copy']) && $mtime && touch($target, $mtime);
        }

        return $ret;
    }

    /**
     * Move file into another parent dir.
     * Return new file path or false.
     *
     * @param  string $source source file path
     * @param         $targetDir
     * @param  string $name   file name
     *
     * @return bool|string
     * @internal param string $target target dir path
     * @author   Dmitry (dio) Levashov
     */
    protected function _move($source, $targetDir, $name)
    {
        $mtime = filemtime($source);
        $target = $this->_joinPath($targetDir, $name);
        if ($ret = rename($source, $target) ? $target : false) {
            isset($this->options['keepTimestamp']['move']) && $mtime && touch($target, $mtime);
        }

        return $ret;
    }

    /**
     * Remove file
     *
     * @param  string $path file path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _unlink($path)
    {
        return is_file($path) && unlink($path);
    }

    /**
     * Remove dir
     *
     * @param  string $path dir path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _rmdir($path)
    {
        return rmdir($path);
    }

    /**
     * Create new file and write into it from file pointer.
     * Return new file path or false on error.
     *
     * @param  resource $fp   file pointer
     * @param  string   $dir  target dir path
     * @param  string   $name file name
     * @param  array    $stat file stat (required by some virtual fs)
     *
     * @return bool|string
     * @author Dmitry (dio) Levashov
     **/
    protected function _save($fp, $dir, $name, $stat)
    {
        $path = $this->_joinPath($dir, $name);

        $meta = stream_get_meta_data($fp);
        $uri = isset($meta['uri']) ? $meta['uri'] : '';
        if ($uri && !preg_match('#^[a-zA-Z0-9]+://#', $uri) && !is_link($uri)) {
            fclose($fp);
            $mtime = filemtime($uri);
            $isCmdPaste = ('paste' === $this->ARGS['cmd']);
            $isCmdCopy = ($isCmdPaste && empty($this->ARGS['cut']));
            if (($isCmdCopy || !rename($uri, $path)) && !copy($uri, $path)) {
                return false;
            }
            // keep timestamp on upload
            if ($mtime && 'upload' === $this->ARGS['cmd']) {
                touch($path, isset($this->options['keepTimestamp']['upload']) ? $mtime : time());
            }
        } else {
            if (false === file_put_contents($path, $fp, LOCK_EX)) {
                return false;
            }
        }

        chmod($path, $this->options['fileMode']);

        return $path;
    }

    /**
     * Get file contents
     *
     * @param  string $path file path
     *
     * @return string|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _getContents($path)
    {
        return file_get_contents($path);
    }

    /**
     * Write a string to a file
     *
     * @param  string $path    file path
     * @param  string $content new file content
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _filePutContents($path, $content)
    {
        return (false !== file_put_contents($path, $content, LOCK_EX));
    }

    /**
     * Detect available archivers
     *
     * @throws elFinderAbortException
     * @return void
     */
    protected function _checkArchivers()
    {
        $this->archivers = $this->getArchivers();
    }

    /**
     * chmod availability
     *
     * @param string $path
     * @param string $mode
     *
     * @return bool
     */
    protected function _chmod($path, $mode)
    {
        $modeOct = is_string($mode) ? octdec($mode) : octdec(sprintf('%04o', $mode));

        return chmod($path, $modeOct);
    }

    /**
     * Recursive symlinks search
     *
     * @param  string $path file/dir path
     *
     * @throws Exception
     * @return bool
     * @author Dmitry (dio) Levashov
     */
    protected function _findSymlinks($path)
    {
        return self::localFindSymlinks($path);
    }

    /**
     * Extract files from archive
     *
     * @param  string $path archive path
     * @param  array  $arc  archiver command and arguments (same as in $this->archivers)
     *
     * @throws elFinderAbortException
     * @return array|string|boolean
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     */
    protected function _extract($path, $arc)
    {
        if ($this->quarantine) {
            $dir = $this->quarantine . DIRECTORY_SEPARATOR . md5(basename($path) . mt_rand());
            $archive = (isset($arc['toSpec']) || 'phpfunction' === $arc['cmd']) ? '' : $dir . DIRECTORY_SEPARATOR . basename($path);

            if (!mkdir($dir)) {
                return false;
            }

            // insurance unexpected shutdown
            register_shutdown_function([$this, 'rmdirRecursive'], realpath($dir));

            chmod($dir, 0777);

            // copy in quarantine
            if (!is_readable($path) || ($archive && !copy($path, $archive))) {
                return false;
            }

            // extract in quarantine
            $this->unpackArchive($path, $arc, $archive ? true : $dir);

            // get files list
            try {
                $ls = self::localScandir($dir);
            } catch (Exception $e) {
                return false;
            }

            // no files - extract error ?
            if (empty($ls)) {
                return false;
            }

            $this->archiveSize = 0;

            // find symlinks and check extracted items
            $checkRes = $this->checkExtractItems($dir);
            if ($checkRes['symlinks']) {
                self::localRmdirRecursive($dir);

                return $this->setError(array_merge($this->error, [elFinder::ERROR_ARC_SYMLINKS]));
            }
            $this->archiveSize = $checkRes['totalSize'];
            if ($checkRes['rmNames']) {
                foreach ($checkRes['rmNames'] as $name) {
                    $this->addError(elFinder::ERROR_SAVE, $name);
                }
            }

            // check max files size
            if ($this->options['maxArcFilesSize'] > 0 && $this->options['maxArcFilesSize'] < $this->archiveSize) {
                $this->delTree($dir);

                return $this->setError(elFinder::ERROR_ARC_MAXSIZE);
            }

            $extractTo = $this->extractToNewdir; // 'auto', ture or false

            // archive contains one item - extract in archive dir
            $name = '';
            $src = $dir . DIRECTORY_SEPARATOR . $ls[0];
            if (('auto' === $extractTo || !$extractTo) && 1 === count($ls) && is_file($src)) {
                $name = $ls[0];
            } elseif ('auto' === $extractTo || $extractTo) {
                // for several files - create new directory
                // create unique name for directory
                $src = $dir;
                $splits = elFinder::splitFileExtention(basename($path));
                $name = $splits[0];
                $test = dirname($path) . DIRECTORY_SEPARATOR . $name;
                if (file_exists($test) || is_link($test)) {
                    $name = $this->uniqueName(dirname($path), $name, '-', false);
                }
            }

            if ('' !== $name) {
                $result = dirname($path) . DIRECTORY_SEPARATOR . $name;

                if (!rename($src, $result)) {
                    $this->delTree($dir);

                    return false;
                }
            } else {
                $dstDir = dirname($path);
                $result = [];
                foreach ($ls as $name) {
                    $target = $dstDir . DIRECTORY_SEPARATOR . $name;
                    if (self::localMoveRecursive($dir . DIRECTORY_SEPARATOR . $name, $target, true, $this->options['copyJoin'])) {
                        $result[] = $target;
                    }
                }
                if (!$result) {
                    $this->delTree($dir);

                    return false;
                }
            }

            is_dir($dir) && $this->delTree($dir);

            return (is_array($result) || file_exists($result)) ? $result : false;
        }
        //TODO: Add return statement here
        return false;
    }

    /**
     * Create archive and return its path
     *
     * @param  string $dir   target dir
     * @param  array  $files files names list
     * @param  string $name  archive name
     * @param  array  $arc   archiver options
     *
     * @throws elFinderAbortException
     * @return string|bool
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     */
    protected function _archive($dir, $files, $name, $arc)
    {
        return $this->makeArchive($dir, $files, $name, $arc);
    }

    /******************** Over write functions *************************/

    /**
     * File path of local server side work file path
     *
     * @param  string $path
     *
     * @return string
     * @author Naoki Sawada
     */
    protected function getWorkFile($path)
    {
        return $path;
    }

    /**
     * Delete dirctory trees
     *
     * @param string $localpath path need convert encoding to server encoding
     *
     * @throws elFinderAbortException
     * @return boolean
     * @author Naoki Sawada
     */
    protected function delTree($localpath)
    {
        return $this->rmdirRecursive($localpath);
    }

    /**
     * Return fileinfo based on filename
     * For item ID based path file system
     * Please override if needed on each drivers
     *
     * @param  string $path file cache
     *
     * @return array|bool false
     */
    protected function isNameExists($path)
    {
        $exists = file_exists($this->convEncIn($path));
        // restore locale
        $this->convEncOut();

        return $exists ? $this->stat($path) : false;
    }

    /******************** Over write (Optimized) functions *************************/

    /**
     * Recursive files search
     *
     * @param  string $path dir path
     * @param  string $q    search string
     * @param  array  $mimes
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     * @author Naoki Sawada
     */
    protected function doSearch($path, $q, $mimes)
    {
        if (!empty($this->doSearchCurrentQuery['matchMethod']) || $this->encoding || !class_exists('FilesystemIterator', false)) {
            // has custom match method or non UTF-8, use elFinderVolumeDriver::doSearch()
            return parent::doSearch($path, $q, $mimes);
        }

        $result = [];

        $timeout = $this->options['searchTimeout'] ? $this->searchStart + $this->options['searchTimeout'] : 0;
        if ($timeout && $timeout < time()) {
            $this->setError(elFinder::ERROR_SEARCH_TIMEOUT, $this->path($this->encode($path)));

            return $result;
        }
        elFinder::extendTimeLimit($this->options['searchTimeout'] + 30);

        $match = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator(
                        $path,
                        FilesystemIterator::KEY_AS_PATHNAME |
                        FilesystemIterator::SKIP_DOTS |
                        ((defined('RecursiveDirectoryIterator::FOLLOW_SYMLINKS') && $this->options['followSymLinks']) ?
                            RecursiveDirectoryIterator::FOLLOW_SYMLINKS : 0)
                    ),
                    [$this, 'localFileSystemSearchIteratorFilter']
                ),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iterator as $key => $node) {
                if ($timeout && ($this->error || $timeout < time())) {
                    !$this->error && $this->setError(elFinder::ERROR_SEARCH_TIMEOUT, $this->path($this->encode($node->getPath)));
                    break;
                }
                if ($node->isDir()) {
                    if (false !== $this->stripos($node->getFilename(), $q)) {
                        $match[] = $key;
                    }
                } else {
                    $match[] = $key;
                }
            }
        } catch (Exception $e) {
        }

        if ($match) {
            foreach ($match as $p) {
                if ($timeout && ($this->error || $timeout < time())) {
                    !$this->error && $this->setError(elFinder::ERROR_SEARCH_TIMEOUT, $this->path($this->encode(dirname($p))));
                    break;
                }

                $stat = $this->stat($p);

                if (!$stat) { // invalid links
                    continue;
                }

                if (!empty($stat['hidden']) || !$this->mimeAccepted($stat['mime'], $mimes)) {
                    continue;
                }

                if ((!$mimes || 'directory' !== $stat['mime'])) {
                    $stat['path'] = $this->path($stat['hash']);
                    if ($this->URL && !isset($stat['url'])) {
                        $_path = str_replace(DIRECTORY_SEPARATOR, '/', mb_substr($p, mb_strlen($this->root) + 1));
                        $stat['url'] = $this->URL . str_replace('%2F', '/', rawurlencode($_path));
                    }

                    $result[] = $stat;
                }
            }
        }

        return $result;
    }

    /******************** Original local functions ************************
     *
     * @param $file
     * @param $key
     * @param $iterator
     *
     * @return bool
     */

    public function localFileSystemSearchIteratorFilter($file, $key, $iterator)
    {
        /* @var FilesystemIterator $file */
        /* @var RecursiveDirectoryIterator $iterator */
        $name = $file->getFilename();
        if ($this->doSearchCurrentQuery['excludes']) {
            foreach ($this->doSearchCurrentQuery['excludes'] as $exclude) {
                if (false !== $this->stripos($name, $exclude)) {
                    return false;
                }
            }
        }
        if ($iterator->hasChildren()) {
            if ($this->options['searchExDirReg'] && preg_match($this->options['searchExDirReg'], $key)) {
                return false;
            }

            return (bool)$this->attr($key, 'read', null, true);
        }

        return (false === $this->stripos($name, $this->doSearchCurrentQuery['q'])) ? false : true;
    }
} // END class
