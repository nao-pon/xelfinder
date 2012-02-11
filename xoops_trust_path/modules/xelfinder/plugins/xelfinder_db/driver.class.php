<?php

/**
 * Simple elFinder driver for MySQL.
 *
 * @author Dmitry (dio) Levashov
 **/
class elFinderVolumeXoopsXelfinder_db extends elFinderVolumeDriver {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'xe';

	protected $mydirname = '';

	protected $x_mid;
	protected $x_uid = 0;
	protected $x_groups = array();
	protected $x_isAdmin = false;

	/**
	 * Database object
	 *
	 * @var mysqli
	 **/
	protected $db = null;

	/**
	 * Tables to store files
	 *
	 * @var string
	 **/
	protected $tbf = '';

	/**
	 * Directory for tmp files
	 * If not set driver will try to use tmbDir as tmpDir
	 *
	 * @var string
	 **/
	protected $tmpPath = '';

	/**
	 * Numbers of sql requests (for debug)
	 *
	 * @var int
	 **/
	protected $sqlCnt = 0;

	/**
	 * Last db error message
	 *
	 * @var string
	 **/
	protected $dbError = '';

	protected $debugMsg;


	/**
	 * Constructor
	 * Extend options with required fields
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct() {
		$this->options['path'] = '1';
		$this->options['separator'] = '/';
		$this->options['mydirname'] = 'xelfinder';
		$this->options['checkSubfolders'] = true;
		$this->options['mimeDetect'] = 'internal';
		$this->options['tmbPath'] = XOOPS_ROOT_PATH . '/modules/'._MD_ELFINDER_MYDIRNAME.'/cache/tmb/';
		$this->options['tmbURL'] = XOOPS_URL . '/modules/'._MD_ELFINDER_MYDIRNAME.'/cache/tmb/';
		$this->options['default_umask'] = '022';
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
	protected function init() {

		$this->db = & XoopsDatabaseFactory::getDatabaseConnection();
		if (! $this->db) {
			return false;
		}

		mysql_set_charset('utf8');

		$this->mydirname = $this->options['mydirname'];

		$this->tbf = $this->db->prefix($this->mydirname) . '_file';

		$module_handler =& xoops_gethandler('module');
		$XoopsModule =& $module_handler->getByDirname($this->mydirname);
		$module = $XoopsModule->getInfo();
		$this->x_mid = $XoopsModule->getVar('mid');

		global $xoopsUser;
		if (is_object($xoopsUser)) {
			$this->x_uid = $xoopsUser->getVar('uid');
			$this->x_groups = $xoopsUser->getGroups();
		} else {
			$this->x_uid = 0;
			$this->x_groups = array(XOOPS_GROUP_ANONYMOUS);
		}

//		$this->updateCache($this->options['path'], $this->_stat($this->options['path']));

		return true;
	}



	/**
	 * Set tmp path
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function configure() {
		parent::configure();

// 		if (($tmp = $this->options['tmpPath'])) {
// 			if (!file_exists($tmp)) {
// 				if (@mkdir($tmp)) {
// 					@chmod($tmp, $this->options['tmbPathMode']);
// 				}
// 			}

// 			$this->tmpPath = is_dir($tmp) && is_writable($tmp) ? $tmp : false;
// 		}

// 		if (!$this->tmpPath && $this->tmbPath && $this->tmbPathWritable) {
// 			$this->tmpPath = $this->tmbPath;
// 		}

		$this->mimeDetect = 'internal';
	}

	/**
	 * Close connection
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function umount() {
		//$this->db->close();
	}

	/**
	 * Return debug info for client
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function debug() {
		$debug = parent::debug();
		$debug['sqlCount'] = $this->sqlCnt;
		if ($this->dbError) {
			$debug['dbError'] = $this->dbError;
		}
		if ($this->debugMsg) {
			$debug['msg'] = $this->debugMsg;
		}
		return $debug;
	}

	protected function _debug($msg) {
		$this->debugMsg .= '|' . $msg;
	}

	/**
	 * Perform sql query and return result.
	 * Increase sqlCnt and save error if occured
	 *
	 * @param  string  $sql  query
	 * @return misc
	 * @author Dmitry (dio) Levashov
	 **/
	protected function query($sql) {
		$this->sqlCnt++;
		$res = $this->db->queryF($sql);
		if (!$res) {
			$this->dbError = $this->db->error;
		}
		return $res;
	}

	/**
	 * Create empty object with required mimetype
	 *
	 * @param  string  $path  parent dir path
	 * @param  string  $name  object name
	 * @param  string  $mime  mime type
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function make($path, $name, $mime) {

		$sql = 'INSERT INTO %s (`parent_id`, `name`, `ctime`, `mtime`, `perm`, `umask` , `uid`, `mime`) VALUES '
		                    . '( %d,         "%s",    %d,      %d,     "%s",   "%s",     "%d",  "%s")';
		$time = time();
		$umask = $this->getUmask($path);
		$perm = $this->getDefaultPerm($mime, $umask);
		$sql = sprintf($sql, $this->tbf, intval($path), mysql_escape_string($name), $time, $time, $perm, $this->options['default_umask'], $this->x_uid, $mime);
		// echo $sql;
		if ($this->query($sql) && $this->db->getAffectedRows() > 0) {
			if ($mime !== 'directory') {
				$id = $this->db->getInsertId();
				$local = $this->readlink($id, true);
				if ($local) return true;
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return temporary file path for required file
	 *
	 * @param  string  $path   file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tmpname($path) {
		return $this->tmpPath.DIRECTORY_SEPARATOR.md5($path);
	}

	/**
	* Resize image
	*
	* @param  string   $hash    image file
	* @param  int      $width   new width
	* @param  int      $height  new height
	* @param  bool     $crop    crop image
	* @return array|false
	* @author Dmitry (dio) Levashov
	* @author Alexey Sukhotin
	**/
	public function resize($hash, $width, $height, $x, $y, $mode = 'resize', $bg='') {
		if ($this->commandDisabled('resize')) {
			return $this->setError(elFinder::ERROR_PERM_DENIED);
		}

		if (($file = $this->file($hash)) == false) {
			return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
		}

		if (!$file['write'] || !$file['read']) {
			return $this->setError(elFinder::ERROR_PERM_DENIED);
		}

		$path = $this->decode($hash);

		if (!$this->canResize($path, $file)) {
			return $this->setError(elFinder::ERROR_UNSUPPORT_TYPE);
		}

		$local = $this->readlink($path);
		if (! $local) {
			return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
		}

		switch($mode) {

			case 'propresize':
				$result = $this->imgResize($local, $width, $height, true, true);
				break;

			case 'crop':
				$result = $this->imgCrop($local, $width, $height, $x, $y);
				break;

			case 'fitsquare':
				$result = $this->imgSquareFit($local, $width, $height, 'center', 'middle', $bg ? $bg : $this->options['tmbBgColor']);
				break;

			default:
				$result = $this->imgResize($local, $width, $height, false, true);
			break;
		}

		if ($result) {
			$this->rmTmb($path);
			$this->clearcache();
			$this->createTmb($path, $file);

			$sql = 'UPDATE %s SET width=%d, height=%d WHERE file_id=%d LIMIT 1';
			$sql = sprintf($sql, $this->tbf, $width, $height, $path);
			$this->query($sql);

			return $this->stat($path);
		}

		return false;
	}

	protected function getUmask($path) {
		$umask = '';
		if ($path > 1) {
			$sql = 'SELECT `umask` FROM '.$this->tbf.' WHERE `file_id`='.intval($path).' LIMIT=1';
			if ($res = $this->db->query($sql)) {
				list($umask) = $this->db->fetchRow($res);
			}
		}
		return $umask? $umask : '022';
	}

	protected function getDefaultPerm($mime, $umask) {
		if ($mime === 'directory') {
			$base = 0x777;
		} else {
			$base = 0x666;
		}
		return strval(dechex($base - intval($umask, 16)));
	}

	protected function getAuthByPerm($perm, $check) {
		// 0110 4bit [0-9a-f]
		// hrwl hidden, read, write, lock
	}

	protected function getGroupsByUid($uid) {
		static $groups = array();

		if (isset($groups[$uid])) return $groups[$uid];

		if ($uid) {
			$module_handler =& xoops_gethandler('module');
			$XoopsModule =& $module_handler->getByDirname($this->mydirname);
			$user_handler =& xoops_gethandler('user');
			$user =& $user_handler->get( $uid );
			$groups[$uid] = $user->getGroups();
			$user = null;
			unset($user);
		} else {
			$groups[$uid] = array( XOOPS_GROUP_ANONYMOUS );
		}

		return $groups[$uid];
	}

	protected function setAuthByPerm(& $dat) {

		if ($dat['uid'] == $this->x_uid || $this->x_isAdmin) {
			$check = $dat['perm'][0];
		} else if (array_intersect($this->getGroupsByUid($dat['uid']), $this->x_groups)) {
			$check = $dat['perm'][1];
		} else {
			$check = $dat['perm'][2];
		}
		$check = intval(strval($check), 16);

		$dat['hidden'] = ((8 & $check) === 8);
		$dat['read']   = ((4 & $check) === 4);
		$dat['write']  = ((6 & $check) === 6);
		$dat['locked'] = !((5 & $check) === 5);
	}

	/*********************************************************************/
	/*                               FS API                              */
	/*********************************************************************/

	/**
	 * Cache dir contents
	 *
	 * @param  string  $path  dir path
	 * @return void
	 * @author Dmitry Levashov
	 **/
	protected function cacheDir($path) {
		$this->dirsCache[$path] = array();

		$sql = 'SELECT f.file_id, f.parent_id, f.name, f.size, f.mtime AS ts, f.mime, f.perm, f.uid, f.width, f.height, IF(ch.file_id, 1, 0) AS dirs
				FROM '.$this->tbf.' AS f
				LEFT JOIN '.$this->tbf.' AS ch ON ch.parent_id=f.file_id AND ch.mime="directory"
				WHERE f.parent_id="'.$path.'"
				GROUP BY f.file_id';

		$res = $this->query($sql);
		if ($res) {
			while ($row = $this->db->fetchArray($res)) {
				// debug($row);
				$id = $row['file_id'];
				if ($row['parent_id']) {
					$row['phash'] = $this->encode($row['parent_id']);
				}

				if ($row['mime'] == 'directory') {
					unset($row['width']);
					unset($row['height']);
				} else {
					unset($row['dirs']);
				}

				$this->setAuthByPerm($row);

				$row['url'] = $this->options['URL'].$row['file_id'].'/'.rawurlencode($row['name']); // Use pathinfo "index.php/[id]/[name]

				unset($row['file_id'], $row['parent_id'], $row['perm'], $row['uid']);

				if (($stat = $this->updateCache($id, $row)) && empty($stat['hidden'])) {
					$this->dirsCache[$path][] = $id;
				}
			}
		}

		return $this->dirsCache[$path];
	}

	/**
	 * Return array of parents paths (ids)
	 *
	 * @param  int   $path  file path (id)
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function getParents($path) {
		$parents = array();

		while ($path) {
			if ($file = $this->stat($path)) {
				array_unshift($parents, $path);
				$path = isset($file['phash']) ? $this->decode($file['phash']) : false;
			}
		}

		if (count($parents)) {
			array_pop($parents);
		}
		return $parents;
	}

	/*********************** paths/urls *************************/

	/**
	 * Return parent directory path
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _dirname($path) {
		return ($stat = $this->stat($path)) ? ($stat['phash'] ? $this->decode($stat['phash']) : $this->root) : false;
	}

	/**
	 * Return file name
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _basename($path) {
		return ($stat = $this->stat($path)) ? $stat['name'] : false;
	}

	/**
	 * Join dir name and file name and return full path
	 *
	 * @param  string  $dir
	 * @param  string  $name
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _joinPath($dir, $name) {
		$sql = 'SELECT `file_id` FROM '.$this->tbf.' WHERE parent_id="'.$dir.'" AND name="'.mysql_escape_string($name).'"';
		// echo $sql;
		// $this->_debug('_joinPath:'.$sql);
		if (($res = $this->query($sql)) && ($r = $this->db->fetchArray($res))) {
			// $this->_debug('got '.$r['file_id']);
			$this->updateCache($r['file_id'], $this->_stat($r['file_id']));
			return $r['file_id'];
		}
		return -1;
	}

	/**
	 * Return normalized path, this works the same as os.path.normpath() in Python
	 *
	 * @param  string  $path  path
	 * @return string
	 * @author Troex Nevelin
	 **/
	protected function _normpath($path) {
		return $path;
	}

	/**
	 * Return file path related to root dir
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _relpath($path) {
		return $path;
	}

	/**
	 * Convert path related to root dir into real path
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _abspath($path) {
		return $path;
	}

	/**
	 * Return fake path started from root dir
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _path($path) {
		if (($file = $this->stat($path)) == false) {
			return '';
		}

		$parentsIds = $this->getParents($path);
		$path = '';
		foreach ($parentsIds as $id) {
			$dir = $this->stat($id);
			$path .= $dir['name'].$this->separator;
		}
		return $path.$file['name'];
	}

	/**
	 * Return true if $path is children of $parent
	 *
	 * @param  string  $path    path to check
	 * @param  string  $parent  parent path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _inpath($path, $parent) {
		return $path == $parent
			? true
			: in_array($parent, $this->getParents($path));
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
	protected function _stat($path, $rootCheck = true) {
		$sql = 'SELECT f.file_id, f.parent_id, f.name, f.size, f.mtime AS ts, f.mime, f.perm, f.uid, f.width, f.height, IF(ch.file_id, 1, 0) AS dirs
				FROM '.$this->tbf.' AS f
				LEFT JOIN '.$this->tbf.' AS p ON p.file_id=f.parent_id
				LEFT JOIN '.$this->tbf.' AS ch ON ch.parent_id=f.file_id AND ch.mime="directory"
				WHERE f.file_id="'.$path.'"
				GROUP BY f.file_id';

		$res = $this->query($sql);

		if ($res && $stat = $this->db->fetchArray($res)) {
			if ($stat['parent_id']) {
				$stat['phash'] = $this->encode($stat['parent_id']);
			}
			if ($stat['mime'] == 'directory') {
				unset($stat['width']);
				unset($stat['height']);
			} else {
				unset($stat['dirs']);
			}
			$this->setAuthByPerm($stat);
			$stat['url'] = $this->options['URL'].$stat['file_id'].'/'.rawurlencode($stat['name']); // Use pathinfo "index.php/[id]/[name]
			unset($stat['file_id'], $stat['parent_id'], $stat['perm'], $stat['uid']);

			return $stat;

		} else if ($rootCheck && $path == 1) {
			$this->_mkdir(0, $this->options['alias']);
			return $this->_stat($path, false);
		}
		return array();
	}

	/**
	 * Return true if path is dir and has at least one childs directory
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _subdirs($path) {
		return ($stat = $this->stat($path)) && isset($stat['dirs']) ? $stat['dirs'] : false;
	}

	/**
	 * Return object width and height
	 * Usualy used for images, but can be realize for video etc...
	 *
	 * @param  string  $path  file path
	 * @param  string  $mime  file mime type
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _dimensions($path, $mime) {
		return ($stat = $this->stat($path)) && $stat['width'] && $stat['height'] ? $stat['width'].'x'.$stat['height'] : '';
	}

	/******************** file/dir content *********************/

	/**
	* Return symlink target file
	*
	* @param  string  $path  link path
	* @return string
	* @author Dmitry (dio) Levashov
	**/
	protected function readlink($path, $make = false) {
		$link = $this->options['filePath'] . $path;
		if (is_file($link)) {
			return $link;
		} else if ($make) {
			return touch($link)? $link : false;
		} else {
			return false;
		}
	}

	/**
	 * Return files list in directory.
	 *
	 * @param  string  $path  dir path
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _scandir($path) {
		return isset($this->dirsCache[$path])
			? $this->dirsCache[$path]
			: $this->cacheDir($path);
	}

	/**
	 * Open file and return file pointer
	 *
	 * @param  string  $path  file path
	 * @param  bool    $write open file for writing
	 * @return resource|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fopen($path, $mode='rb') {
		if ($local = $this->readlink($path)) {
			return @fopen($local, $mode);
		}
		return false;
	}

	/**
	 * Close opened file
	 *
	 * @param  resource  $fp  file pointer
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fclose($fp, $path='') {
		@fclose($fp);
	}

	/********************  file/dir manipulations *************************/

	/**
	 * Create dir and return created dir path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new directory name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkdir($path, $name) {
		return $this->make($path, $name, 'directory') ? $this->_joinPath($path, $name) : false;
	}

	/**
	 * Create file and return it's path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkfile($path, $name) {
		return $this->make($path, $name, 'text/plain') ? $this->_joinPath($path, $name) : false;
	}

	/**
	 * Create symlink. FTP driver does not support symlinks.
	 *
	 * @param  string  $target  link target
	 * @param  string  $path    symlink path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _symlink($target, $path, $name) {
		return false;
	}

	/**
	 * Copy file into another file
	 *
	 * @param  string  $source     source file path
	 * @param  string  $targetDir  target directory path
	 * @param  string  $name       new file name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _copy($source, $targetDir, $name) {
		$this->clearcache();
		$id = $this->_joinPath($targetDir, $name);

		$time = time();
		$sql = $id > 0
			? sprintf('REPLACE INTO %s (`file_id`, `parent_id`, `name`, `size`, `ctime`, `mtime`, `mime`, `width`, `height`, `uid`, `perm`, `umask`) (SELECT %d, %d, `name`, `size`, `ctime`, `mtime`, `mime`, `width`, `height`, `uid`, `perm`, `umask` FROM %s WHERE file_id=%d)', $this->tbf, (int)$id, (int)$this->_dirname($id), $this->tbf, (int)$source)
			: sprintf('INSERT INTO %s (`parent_id`, `name`, `size`, `ctime`, `mtime`, `mime`, `width`, `height`, `uid`, `perm`, `umask`) SELECT %d, "%s", size, %d, %d, mime, width, height, `uid`, `perm`, `umask` FROM %s WHERE file_id=%d', $this->tbf, (int)$targetDir, mysql_escape_string($name), $time, $time, $this->tbf, (int)$source);
		//$this->_debug($sql);
		if ($this->query($sql)) {
			if ($id < 1) $id = $this->db->getInsertId();
			if ($local = $this->readlink($id, true)) {
				if ($target = @fopen($local, 'wb')) {
					$fp = $this->_fopen($source);
					while (!feof($fp)) {
						fwrite($target, fread($fp, 8192));
					}
					fclose($target);
					$this->_fclose($fp);
					return $id;
				}
			}
		}
	}

	/**
	 * Move file into another parent dir.
	 * Return new file path or false.
	 *
	 * @param  string  $source  source file path
	 * @param  string  $target  target dir path
	 * @param  string  $name    file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _move($source, $targetDir, $name) {
		$sql = 'UPDATE %s SET parent_id=%d, name="%s" WHERE file_id=%d LIMIT 1';
		$sql = sprintf($sql, $this->tbf, $targetDir, mysql_escape_string($name), $source);
		return ($this->query($sql) && $this->db->getAffectedRows() > 0);
	}

	/**
	 * Remove file
	 *
	 * @param  string  $path  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _unlink($path) {
		@unlink($this->readlink($path));
		return ($this->query(sprintf('DELETE FROM %s WHERE `file_id`=%d AND `mime`!="directory" LIMIT 1', $this->tbf, $path)) && $this->db->getAffectedRows());
	}

	/**
	 * Remove dir
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _rmdir($path) {
		return ($this->query(sprintf('DELETE FROM %s WHERE `file_id`=%d AND `mime`="directory" LIMIT 1', $this->tbf, $path)) && $this->db->getAffectedRows());
	}

	/**
	 * Create new file and write into it from file pointer.
	 * Return new file path or false on error.
	 *
	 * @param  resource  $fp   file pointer
	 * @param  string    $dir  target dir path
	 * @param  string    $name file name
	 * @return bool|string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _save($fp, $dir, $name, $mime, $w, $h) {
		$this->clearcache();

		$id = $this->_joinPath($dir, $name);

		$this->rmTmb($id);
		rewind($fp);
		$stat = fstat($fp);
		$size = $stat['size'];
		$time = time();
		$perm = $this->getDefaultPerm($mime, $this->getUmask($dir));

		// `file_id`, `parent_id`, `name`, `size`, `ctime`, `mtime`, `perm`, `umask`, `uid`, `mime`, `width`, `height`
		//  %d, %d, "%s", %d, %d, %d, "%s", "%s", %d, "%s", %d, %d
		$sql = $id > 0
			? 'REPLACE INTO %s (`file_id`, `parent_id`, `name`, `size`, `ctime`, `mtime`, `perm`, `uid`, `mime`, `width`, `height`) VALUES ('.$id.',%d, "%s", %d, %d, %d, "%s", "%s", %d, "%s", %d, %d)'
			: 'INSERT INTO %s (`parent_id`, `name`, `size`, `ctime`, `mtime`, `perm`, `uid`, `mime`, `width`, `height`) VALUES (%d, "%s", %d, %d, %d, "%s", %d, "%s", %d, %d)';
		$sql = sprintf($sql, $this->tbf, (int)$dir, mysql_escape_string($name), $size, $time, $time, $perm, (int)$this->x_uid, $mime, $w, $h);

		if ($this->query($sql)) {
			if ($id < 1) $id = $this->db->getInsertId();
			if ($local = $this->readlink($id, true)) {
				if ($target = @fopen($local, 'wb')) {
					while (!feof($fp)) {
						fwrite($target, fread($fp, 8192));
					}
					fclose($target);
					return $id;
				}
			}
		}

		return false;
	}

	/**
	 * Get file contents
	 *
	 * @param  string  $path  file path
	 * @return string|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _getContents($path) {
		if ($local = $this->readlink($path)) {
			return file_get_contents($local);
		}
		return false;
	}

	/**
	 * Write a string to a file
	 *
	 * @param  string  $path     file path
	 * @param  string  $content  new file content
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _filePutContents($path, $content) {
		if ($local = $this->readlink($path)) {
			if (file_put_contents($local, $content)) {
				return $this->query(sprintf('UPDATE %s SET `size`=%d, `mtime`=%d WHERE `file_id` = "%d" LIMIT 1', $this->tbf, strlen($content), time(), $path));
			}
		}
		return false;
	}

	/**
	 * Detect available archivers
	 *
	 * @return void
	 **/
	protected function _checkArchivers() {
		return;
	}

	/**
	 * Unpack archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function _unpack($path, $arc) {
		return;
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _findSymlinks($path) {
		return false;
	}

	/**
	 * Extract files from archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _extract($path, $arc) {
		return false;
	}

	/**
	 * Create archive and return its path
	 *
	 * @param  string  $dir    target dir
	 * @param  array   $files  files names list
	 * @param  string  $name   archive name
	 * @param  array   $arc    archiver options
	 * @return string|bool
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _archive($dir, $files, $name, $arc) {
		return false;
	}

} // END class

?>