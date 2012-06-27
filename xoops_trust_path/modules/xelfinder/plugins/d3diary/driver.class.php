<?php

/**
 * Simple elFinder driver for MySQL.
 *
 * @author Dmitry (dio) Levashov
 **/
class elFinderVolumeXoopsD3diary extends elFinderVolumeDriver {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'xd';

	protected $mydirname = '';

	/**
	 * d3dConf object
	 *
	 * @var object
	 **/
	protected $d3dConf = null;

	/**
	 * Constructor
	 * Extend options with required fields
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct() {

		$this->options['path'] = '_';
		$this->options['separator'] = '/';
		$this->options['mydirname'] = 'd3diary';
		$this->options['checkSubfolders'] = true;
		$this->options['tmbPath'] = XOOPS_MODULE_PATH . '/'._MD_ELFINDER_MYDIRNAME.'/cache/tmb/';
		$this->options['tmbURL'] = _MD_XELFINDER_MODULE_URL . '/'._MD_ELFINDER_MYDIRNAME.'/cache/tmb/';

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
		
		$this->mydirname = $mydirname = $this->options['mydirname'];
		
		$langmanpath = XOOPS_TRUST_PATH.'/libs/altsys/class/D3LanguageManager.class.php' ;
		if( ! file_exists( $langmanpath ) ) {
			return false;
		}
		require_once( $langmanpath ) ;
		$langman =& D3LanguageManager::getInstance() ;
		$langman->read( 'main.php' , $mydirname , 'd3diary' ) ;
		
		$d3dTrustDir = XOOPS_TRUST_PATH . '/modules/d3diary';
		include_once $d3dTrustDir.'/class/d3diaryConf.class.php';

		$this->d3dConf =& D3diaryConf::getInstance($mydirname, 0, 'photolist');
		if (! is_object($this->d3dConf)) return false;

		mysql_set_charset('utf8');
		
		// make catgory tree
		$func =& $this->d3dConf->func ;
		
		$uid = $this->d3dConf->uid;
		$cat = $func->get_categories($uid, $uid);
		
		$this->catTree = array();
		
		$this->catTree['root'] = array( 'subcats' => array() );
		$pcid = 'root';//-1
		foreach($cat as $_cat) {
			if ( 100 <= $_cat['blogtype'] ) {
				continue;
			}
			$this->catTree[$_cat['cid']] = array(
								'name' => $_cat['cname'],
								'pcid' => (($_cat['subcat'] && $pcid)? $pcid : 'root') );
			if ($_cat['subcat']) {
				if ($pcid !== 'root') {
					if (! isset($this->catTree[$pcid]['subcats'])) {
						$this->catTree[$pcid]['subcats'] = array();
					}
					$this->catTree[$pcid]['subcats'][] = $_cat['cid'];
				}
			} else {
				$pcid = $_cat['cid'];
				$this->catTree['root']['subcats'][] = $pcid;
			}
		}
		$another_pcid = isset($this->catTree[0])? 0 : 'root';
		$this->catTree[-1] = array(
				'name' => 'Another',
				'pcid' => $another_pcid);
		$this->catTree[$another_pcid]['subcats'][] = -1;
		return true;
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

	/*********************************************************************/
	/*                               FS API                              */
	/*********************************************************************/

	/**
	 * Put file stat in cache and return it
	 *
	 * @param  string  $path   file path
	 * @param  array   $stat   file stat
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function updateCache($path, $stat) {
		$stat = parent::updateCache($path, $stat);
		if ($stat && $stat['mime'] !== 'directory') $stat['_localpath'] = str_replace(XOOPS_ROOT_PATH, 'R', realpath($this->options['filePath'])  . DIRECTORY_SEPARATOR . str_replace($this->options['URL'], '', $stat['url']) );
		return $this->cache[$path] = $stat;
	}

	/**
	 * Cache dir contents
	 *
	 * @param  string  $path  dir path
	 * @return void
	 * @author Dmitry Levashov
	 **/
	protected function cacheDir($path) {
		$this->dirsCache[$path] = array();

		if ($path === '_') {
			$cid = 'root';
		} else {
			list($cid) = explode('_', substr($path, 1), 2);
		}

		$row_def = array(
			'size' => 0,
			'ts' => 0,
			'mime' => '',
			'dirs' => 0,
			'read' => true,
			'write' => false,
			'locked' => true,
			'hidden' => false
		);

		$_mtime = array();
		$_size = array();

		if (! empty($this->catTree[$cid]['subcats'])) {
			// category (dirctory)
			foreach ($this->catTree[$cid]['subcats'] as $ccid) {
				$row = $row_def;
				$row['name'] = $this->catTree[$ccid]['name'];
				//$row['ts'] = 0;
				$row['mime'] = 'directory';
				$row['dirs'] = (! empty($this->catTree[$ccid]['subcats']))? 1 : 0;
				if ($this->catTree[$ccid]['pcid'] === 'root') {
					$row['phash'] = $this->encode('_');
				} else {
					$row['phash'] = $this->encode('_'.$this->catTree[$ccid]['pcid'].'_');
				}
				$id = '_'.$ccid.'_';
				if (($stat = $this->updateCache($id, $row)) && empty($stat['hidden'])) {
					$this->dirsCache[$path][] = $id;
				}
			}
		}

		if ($cid !== 'root') {
			// photos
			$uid = $this->d3dConf->uid;
			if ($cid >= 10000) {		// all images of common categories
				$arr_uids = array();
				$cids = array($cid);
			} elseif ($cid == -1) {		// all images of other personnel
				$arr_uids = array();
				$cids = array();
			} else {			// self personal categories' images
				$arr_uids = array($uid);
				$cids = array($cid);
			}
			
			list($photos) = $this->d3dConf->func->get_photolist($arr_uids, $uid, 0, 0, array('cids' => $cids));
			if ($photos) {
				foreach($photos as $photo) {
					$row = $row_def;
					$row['name'] = $photo['title'] . $photo['ptype'];
					$row['ts'] = $photo['tstamp'];
					$row['phash'] = $this->encode('_'.$cid.'_');
					$id = '_'.$cid.'_'.$photo['pname'];
					$row['url'] = $this->options['URL'].$photo['pname'];
					$realpath = realpath($this->options['filePath'].$photo['pname']);
					if (is_file($realpath) && ($cids || ($photo['uid'] != $uid && $photo['cid']))) {
						$row['size'] = filesize($realpath);
						$row['mime'] = $this->mimetypeInternalDetect($photo['pname']);
						$row['simg'] = $photo['thumbnail'];
						if ($photo['openarea'] && $photo['uid'] != $uid) {
							$row['read'] = false;
						}
						if (($stat = $this->updateCache($id, $row)) && empty($stat['hidden'])) {
							$this->dirsCache[$path][] = $id;
						}
					}
				}
			}
		}
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
				$path = $file['phash'] ? $this->decode($file['phash']) : false;
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
		if ($path === '_') {
			return '';
		} else {
			list($cid, $name) = explode('_', substr($path, 1), 2);
			return $name;
		}
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
		if ($dir === '_') {
			return -1;
		} else {
			return $dir . $name;
		}
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
 		if (($file = $this->stat('_')) == false) {
 			return '';
 		}
 		return $file['name'];
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
	protected function _stat($path) {
		if ($path === '_') {
			$pid = 0;
			$cid = 'root';
			$name = '';
		} else {
			list($cid, $pid) = explode('_', substr($path, 1), 2);
			list($pid) = explode('.', $pid);
		}
		$stat_def = array(
			'size' => 0,
			'ts' => 0,
			'mime' => '',
			'dirs' => 0,
			'read' => true,
			'write' => false,
			'locked' => true,
			'hidden' => false
		);

		if ($cid === 'root') {
			$stat['name'] = (! empty($this->options['alias'])? $this->options['alias'] : 'untitle');
			$stat['mime'] = 'directory';
			$stat['dirs'] = true;
			$stat = array_merge($stat_def, $stat);
			return $stat;
		} elseif (! $pid) {
			// category (dirctory)
			if (isset($this->catTree[$cid])) {
				$stat = $stat_def;
				$stat['name'] = $this->catTree[$cid]['name'];
				//$stat['ts'] = 0;
				$stat['mime'] = 'directory';
				$stat['dirs'] = (! empty($this->catTree[$cid]['subcats']))? 1 : 0;
				if ($this->catTree[$cid]['pcid'] === 'root') {
					$stat['phash'] = $this->encode('_');
				} else {
					$stat['phash'] = $this->encode('_'.$this->catTree[$cid]['pcid'].'_');
				}
				return $stat;
			}
		} elseif ($cid !== 'root') {
			// photos
			$uid = $this->d3dConf->uid;
			list($photos) = $this->d3dConf->func->get_photolist(array(), $uid, 0, 0, array('pid' => $pid));
			
			if ($photos) {
				$photo = $photos[0];
				$stat = $stat_def;
				$stat['name'] = $photo['title'] . $photo['ptype'];
				$stat['ts'] = $photo['tstamp'];
				$stat['phash'] = $this->encode('_'.$cid.'_');
				$id = '_'.$cid.'_'.$photo['pname']; 
				$stat['url'] = $this->options['URL'].$photo['pname'];
				$realpath = realpath($this->options['filePath'].$photo['pname']);
				if (is_file($realpath)) {
					$stat['size'] = filesize($realpath);
					$stat['mime'] = $this->mimetypeInternalDetect($photo['pname']);
					$stat['simg'] = $photo['thumbnail'];
					if ($photo['openarea'] && $photo['uid'] != $uid) {
						$stat['read'] = false;
					}
					return $stat;
				}
			}
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
		return ($stat = $this->stat($path)) ? $stat['dirs'] : false;
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
		clearstatcache();
		return strpos($mime, 'image') === 0 && ($s = @getimagesize($this->readlink($path))) !== false 
			? $s[0].'x'.$s[1] 
			: false;
	}

	/******************** file/dir content *********************/

	/**
	 * Return symlink target file
	 *
	 * @param  string  $path  link path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function readlink($path) {
		if ($path !== '_') {
			list(, $name) = explode('_', substr($path, 1), 2);
			if ($name) {
				return realpath($this->options['filePath'] . $name);
			}
		}
		return false;
	}

	/**
	 * Return files list in directory.
	 *
	 * @param  string  $path  dir path
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _scandir($path) {
		if (!isset($this->dirsCache[$path])) {
			$this->cacheDir($path);
		}
		return $this->dirsCache[$path];
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
		return false;
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
		return false;
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
		$res = false;
		return $res;
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
		return false;
	}

	/**
	 * Remove file
	 *
	 * @param  string  $path  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _unlink($path) {
		return false;
	}

	/**
	 * Remove dir
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _rmdir($path) {
		return false;
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
			if (is_file($local) && $contents = file_get_contents($local)) {
				return $contents;
			}
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
		//if ($local = $this->readlink($path)) {
		//	return file_put_contents($local, $content);
		//}
		return false;
	}

	/**
	 * Detect available archivers
	 *
	 * @return void
	 **/
	protected function _checkArchivers() {
		// die('Not yet implemented. (_checkArchivers)');
		return array();
	}

	/**
	 * Unpack archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function _unpack($path, $arc) {
		die('Not yet implemented. (_unpack)');
		return false;
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _findSymlinks($path) {
		die('Not yet implemented. (_findSymlinks)');
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
		die('Not yet implemented. (_extract)');
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
		die('Not yet implemented. (_archive)');
		return false;
	}

} // END class
