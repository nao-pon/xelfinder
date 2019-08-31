<?php

/**
 * elFinder driver for local filesystem.
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 **/
class elFinderVolumeXoopsMailbbs extends elFinderVolumeLocalFileSystem
{
    protected $mydirname = '';

    protected $enabledFiles = [];

    protected function set_mailbbs_enabledFiles()
    {
        include(XOOPS_MODULE_PATH . '/' . $this->mydirname . '/config.php');

        $log = preg_replace('#^\./#', '', $log);
        $logfile = XOOPS_MODULE_PATH . '/' . $this->mydirname . '/' . $log;
        $logs = file($logfile);

        $ret = [];
        foreach ($logs as $log) {
            $data = array_pad(explode('<>', $log), 8, '');
            if (intval($data[7]) || !$data[5]) {
                continue;
            } // 未承認 or ファイルなし
            $ext = mb_strtolower(mb_substr($data[5], mb_strrpos($data[5], '.')));
            if ('.jpeg' === $ext) {
                $ext = '.jpg';
            }
            $ret[$data[5]] = mb_convert_encoding($data[2] . $ext, 'UTF-8', _CHARSET);
        }

        $this->enabledFiles = $ret;
    }

    /**
     * Constructor
     * Extend options with required fields
     *
     * @author Dmitry (dio) Levashov
     **/
    public function __construct()
    {
        parent::__construct();

        $this->options['alias'] = '';              // alias to replace root dir name
        $this->options['dirMode'] = 0755;            // new dirs mode
        $this->options['fileMode'] = 0644;            // new files mode
        $this->options['quarantine'] = XOOPS_MODULE_PATH . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/.quarantine';  // quarantine folder name - required to check archive (must be hidden)
        $this->options['maxArcFilesSize'] = 0;        // max allowed archive files size (0 - no limit)

        $this->options['path'] = '';
        $this->options['separator'] = '/';
        $this->options['mydirname'] = 'mailbbs';
        $this->options['mimeDetect'] = 'internal';
        $this->options['tmbPath'] = XOOPS_MODULE_PATH . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/';
        $this->options['tmbURL'] = _MD_XELFINDER_MODULE_URL . '/' . _MD_ELFINDER_MYDIRNAME . '/cache/tmb/';
    }

    /*********************************************************************/
    /*                        INIT AND CONFIGURE                         */
    /*********************************************************************/

    /**
     * Prepare driver before mount volume.
     * Connect to db, check required tables and fetch root path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function init()
    {
        parent::init();

        $this->mydirname = $this->options['mydirname'];

        $this->set_mailbbs_enabledFiles();

        return true;
    }

    /******************** file/dir content *********************/

    /**
     * Return files list in directory.
     *
     * @param  string  $path  dir path
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function _scandir($path)
    {
        $files = [];
        if ($path === $this->root) {
            foreach ($this->enabledFiles as $file => $name) {
                $files[] = $path . '/' . $file;
            }
        }

        return $files;
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
     *
     * If file does not exists - returns empty array or false.
     *
     * @param  string  $path    file path
     * @return array|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _stat($path)
    {
        $stat = parent::_stat($path);
        if ($stat && $path !== $this->root) {
            $file = basename($path);
            $file_enc = rawurlencode($file);
            $stat['name'] = $this->enabledFiles[$file];
            $stat['url'] = $this->options['URL'] . $file_enc;
            if ('directory' !== $stat['mime']) {
                $stat['_localpath'] = dirname(str_replace(XOOPS_ROOT_PATH, 'R', $path)) . DIRECTORY_SEPARATOR . $file_enc;
            } else {
                $stat['url'] = null;
            }
        }

        return $stat;
    }

    /**
     * Return true if path is dir and has at least one childs directory
     *
     * @param  string  $path  dir path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _subdirs($path)
    {
        return false;
    }

    /**
     * Recursive files search
     *
     * @param  string  $path   dir path
     * @param  string  $q      search string
     * @param  array   $mimes
     * @return array
     * @author Dmitry (dio) Levashov, Naoki Sawada
     **/
    protected function doSearch($path, $q, $mimes)
    {
        $result = [];
        $encode = defined('_CHARSET') ? _CHARSET : 'auto';

        foreach ($this->_scandir($path) as $p) {
            $stat = $this->stat($p);

            if (!$stat) { // invalid links
                continue;
            }

            if (!empty($stat['hidden']) || !$this->mimeAccepted($stat['mime'])) {
                continue;
            }

            $name = $stat['name'];

            if (false !== $this->stripos($name, $q) || false !== $this->stripos(basename($stat['_localpath']), $q)) {
                $_path = mb_convert_encoding($this->_path($p), 'UTF-8', $encode);
                if (false !== preg_match('//u', $_path)) { // UTF-8 check for json_encode()
                    $stat['path'] = $_path;
                }

                $result[] = $stat;
            }
        }

        return $result;
    }
} // END class
