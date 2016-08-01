<?php
/*
 * Created on 2012/01/20 by nao-pon http://xoops.hypweb.net/
 * $Id: xoops_elFinder.class.php,v 1.1 2012/01/20 13:32:02 nao-pon Exp $
 */

class xoops_elFinder {

	protected $db;
	
	protected $xoopsUser;
	protected $xoopsModule;
	protected $mydirname;
	protected $isAdmin;
	
	protected $config;
	protected $mygids;
	protected $uid;
	protected $inSpecialGroup;
	
	public $base64encodeSessionData;
	
	protected static $dbCharset = '';
	
	/**
	* Log file path
	*
	* @var string
	**/
	protected $file = '';
	
	protected $defaultVolumeOptions = array(
			'dateFormat' => 'y/m/d H:i',
			'mimeDetect' => 'auto',
			'tmbSize'	 => 48,
			'tmbCrop'	 => true,
			'defaults' => array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false)
	);
	
	protected $writeCmds = array(
	    'mkdir',
	    'mkfile',
	    'rm',
	    'rename',
	    'duplicate',
	    'cut',
	    'paste',
	    'upload',
	    'put',
	    'edit',
	    'archive',
	    'extract',
	    'resize',
		'perm',
		'chmod',
	    'pixlr'
	);
	
	public function __construct($mydirname, $opt = array()) {
		global $xoopsUser, $xoopsModule;
		
		if (!is_object($xoopsModule)) {
			$module_handler = xoops_gethandler('module');
			$mModule = $module_handler->getByDirname($mydirname);
		} else {
			$mModule = $xoopsModule;
		}
		
		$this->xoopsUser = $xoopsUser;
		$this->xoopsModule = $mModule;
		$this->isAdmin = (is_object($xoopsUser) && $xoopsUser->isAdmin($mModule->getVar('mid')));
		$this->mydirname = $mydirname;
		$this->db = & XoopsDatabaseFactory::getDatabaseConnection();
		$this->defaultVolumeOptions = array_merge($this->defaultVolumeOptions, $opt);
		$this->mygids = is_object($this->xoopsUser)? $this->xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		$this->uid = is_object($this->xoopsUser)? intval($this->xoopsUser->getVar('uid')) : 0;
		$this->base64encodeSessionData = ((!defined('_CHARSET') || _CHARSET !== 'UTF-8') && substr($this->getSessionTableType(), -4) !== 'blob');
		
		if (defined('_MD_XELFINDER_NETVOLUME_SESSION_KEY') && !isset($_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY]) && $this->uid) {
			$table = $this->db->prefix($this->mydirname.'_userdat');
			$sql = 'SELECT `data` FROM `'.$table.'` WHERE `key`=\'netVolumes\' AND `uid`='.$this->uid.' LIMIT 1';
			if ($res = $this->db->query($sql)) {
				if ($this->db->getRowsNum($res) > 0) {
					list($data) = $this->db->fetchRow($res);
					if ($data = @unserialize($data)) {
						$_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY] = $data;
						if ($this->base64encodeSessionData) {
							$data = @unserialize(@base64_decode($data));
						}
						if (is_array($data)) {
							$data = array_reverse($data);
							$cacheKey = 'xel_'.$mydirname.'_Caches';
							if (! isset($_SESSION[$cacheKey])) {
								$_SESSION[$cacheKey] = array();
							}
							foreach($data as $volume) {
								if (! isset($_SESSION[$cacheKey]['DropboxTokens']) && $volume['host'] === 'dropbox' && !empty($volume['dropboxUid']) && !empty($volume['accessToken']) && !empty($volume['accessTokenSecret'])) {
									$_SESSION[$cacheKey]['DropboxTokens'] = array($volume['dropboxUid'], $volume['accessToken'], $volume['accessTokenSecret']);
								}
								if (! isset($_SESSION[$cacheKey]['GoogleDriveAuthParams']) && $volume['driver'] === 'FlysystemGoogleDriveNetmountX') {
									$_SESSION[$cacheKey]['GoogleDriveAuthParams'] = array('access_token' => $volume['access_token']);
								}
							}
						}
					}
				}
			}
		}
	}
	
	public function getUserRoll() {
		return array(
			'isAdmin'        => (bool)$this->isAdmin,
			'uid'            => (int)$this->uid,
			'mygids'         => (array)$this->mygids,
			'inSpecialGroup' => (bool)$this->inSpecialGroup
		);
	}
	
	public function getUid() {
		return $this->uid;
	}
	
	public function getDisablesCmds($useAdmin = true) {
		$disabledCmds = array();
		if (!$useAdmin || !$this->isAdmin) {
			if (!empty($this->config['disable_writes_' . (is_object($this->xoopsUser)? 'user' : 'guest')])) {
				$disabledCmds = $this->writeCmds;
			} 
			if (!empty($this->config['disabled_cmds_by_gids'])) {
				$_parts = array_map('trim', explode(':', $this->config['disabled_cmds_by_gids']));
				foreach($_parts as $_part) {
					list($_gid, $_cmds) = explode('=', $_part, 2);
					$_gid = intval($_gid);
					$_cmds = trim($_cmds);
					if (! $_gid || ! $_cmds) continue;
					if (in_array($_gid, $this->mygids)) {
						$_cmds = array_map('trim', explode(',', $_cmds));
						$disabledCmds = array_merge($disabledCmds, $_cmds);
					}
				}
				$disabledCmds = array_unique($disabledCmds);
			}
		}
		return $disabledCmds;
	}
	
	public function getRootVolumeConfigs($config, $extras = array()) {
		$pluginPath = dirname(dirname(__FILE__)) . '/plugins/';
		$configs = explode("\n", $config);
		$files = array();
		
		$ids = array();
		foreach($configs as $_conf) {
			$_conf = trim($_conf);
			if (! $_conf || $_conf[0] === '#') continue;
			$_confs = explode(':', $_conf, 5);
			$_confs = array_map('trim', $_confs);
			list($mydirname, $plugin, $path, $title, $options) = array_pad($_confs, 5, '');
			
			if (! $this->moduleCheckRight($mydirname)) continue;
			
			$extOptions = array();
			$extOptKeys = array(
				'uploadmaxsize' => 'uploadMaxSize',
				'id'            => 'id',
				'encoding'      => 'encoding',
				'locale'        => 'locale',
				'chmod'         => array('statOwner', 'allowChmodReadOnly')
			);
			$defaults = null;
			if ($options) {
				$options = str_getcsv($options, '|');
				if (is_array($options[0])) {
					$options = $options[0];
				}
				foreach($options as $_op) {
					if (strpos($_op, 'gid=') === 0) {
						$_gids = array_map('intval', explode(',', substr($_op, 4)));
						if ($_gids && $this->mygids) {
							if (! array_intersect($this->mygids, $_gids)) {
								continue 2;
							}
						}
					} else if (strpos($_op, 'defaults=') === 0) {
						list(,$_tmp) = explode('=', $_op, 2);
						$defaults = $this->defaultVolumeOptions['defaults'];
						$_tmp = strtolower($_tmp);
						foreach($defaults as $_p) {
							if (strpos($_tmp, $_p[0]) !== false) {
								$defaults[$_p] = true;
							}
						}
					} else if (strpos($_op, 'plugin.') === 0) {
						list($_p, $_tmp) = explode('=', substr($_op, 7), 2);
						if (! isset($extOptions['plugin'])) {
							$extOptions['plugin'] = array();
						}
						$_opts = array();
						$_p = trim($_p);
						$_parts = str_getcsv($_tmp);
						if ($_parts) {
							if (is_array($_parts[0])) {
								$_parts = $_parts[0];
							}
							foreach($_parts as $_part) {
								list($_k, $_v) = explode(':', trim($_part), 2);
								$_v = trim($_v);
								switch(strtolower($_v)) {
									case 'true':
										$_v = true;
										break;
									case 'false':
										$_v = false;
										break;
									default:
										$_fc = $_v[0];
										$_lc = substr($_v, -1);
										if ($_fc === '`' && $_lc === '`') {
											try {
												eval('$_v = '. trim($_v, '`') . ' ;');
											} catch (Exception $e) { continue 2; }
										} else if ($_fc === '(' && $_lc === ')') {
											try {
												eval('$_v = array'. $_v . ' ;');
												if (! is_array($_v)) {
													continue 2;
												}
											} catch (Exception $e) { continue 2; }
										} else {
											is_numeric($_v) && ($_v = strpos($_v, '.')? (float)$_v : (int)$_v);
										}
								}
								$_opts[trim($_k)] = $_v;
							}
						}
						if ($_opts) {
							$extOptions['plugin'][$_p] = $_opts;
						}
					} else {
						list($key, $value) = explode('=', $_op);
						$key = trim($key);
						$lKey = strtolower($key);
						if (isset($extOptKeys[$lKey])) {
							if (is_array($extOptKeys[$lKey])) {
								foreach($extOptKeys[$lKey] as $_key) {
									$extOptions[$_key] = trim($value);
								}
							} else {
								$extOptions[$extOptKeys[$lKey]] = trim($value);
							}
						}
						if (substr($key, 0, 3) === 'ext') {
							$extOptions[$key] = trim($value);
						}
					}
				}
			}
			if (is_array($defaults)) {
				$extOptions['defaults'] = $defaults;
			}
			
			if ($title === '') $title = $mydirname;
			$path = trim($path, '/');
			$path = ($path === '')? '/' : '/' . $path . '/';
			$src = $pluginPath . $plugin . '/volume.php';
			if (is_file($src)) {
				$extra = isset($extras[$mydirname.':'.$plugin])? $extras[$mydirname.':'.$plugin] : array();
				
				//reset value
				$isAdmin = $this->isAdmin;
				$mConfig = $this->config;
				$mDirname = $this->mydirname;
				
				$files[] = compact('src', 'mydirname', 'title', 'path', 'extra', 'extOptions', 'isAdmin', 'mConfig', 'mDirname');
			}
		}
		$files['disabledCmds'] = $this->getDisablesCmds();
		return $files;
	}
	
	public function buildRootVolumes($configs) {
		$roots = array();
		$disabledCmds = $configs['disabledCmds'];
		unset($configs['disabledCmds']);
		foreach($configs as $config) {
			$raw = null;
			extract($config);
			if ($raw) {
				$roots[] = $raw;
				continue;
			}
			
			$volumeOptions = array();
			if (@include $src) {
				if ($volumeOptions) {
					!isset($volumeOptions['disabled']) && ($volumeOptions['disabled'] = array());
					!isset($volumeOptions['id']) && ($volumeOptions['id'] = '_' . $mydirname);
					if (!empty($volumeOptions['readonly'])) {
						$volumeOptions['disabled'] = array_merge($this->writeCmds, is_array($volumeOptions['disabled'])? $volumeOptions['disabled'] : array());
					}
					$volumeOptions = array_replace_recursive($this->defaultVolumeOptions, $volumeOptions, $extra, $extOptions);
					if ($disabledCmds) {
						if (!isset($volumeOptions['disabled']) || !is_array($volumeOptions['disabled'])) {
							$volumeOptions['disabled'] = array();
						}
						$volumeOptions['disabled'] = array_merge($volumeOptions['disabled'], $disabledCmds);
					}
					if (isset($ids[$volumeOptions['id']])) {
						$i = 1;
						while(isset($ids[$volumeOptions['id']])){
							$volumeOptions['id'] = preg_replace('/\d+$/', '', $volumeOptions['id']);
							$volumeOptions['id'] .= $i++;
						}
					}
					$ids[$volumeOptions['id']] = true;
					$roots[] = $volumeOptions;
				}
			}
		}
		return $roots;
	}
	
	public function getAutoSyncSec() {
		if (isset($this->config['autosync_sec_admin'])) {
			if ($this->isAdmin) {
				return intval($this->config['autosync_sec_admin']);
			} else if ($this->inSpecialGroup) {
				return intval($this->config['autosync_sec_spgroups']);
			} else if ($this->uid > 0) {
				return intval($this->config['autosync_sec_user']);
			} else {
				return intval($this->config['autosync_sec_guest']);
			}
		}
		return 0;
	}
	
	private function moduleCheckRight($dirname) {
		static $module_handler = null;
	
		$ret = false;
	
		if (is_null($module_handler)) {
			$module_handler =& xoops_gethandler('module');
		}
	
		if ($XoopsModule = $module_handler->getByDirname($dirname)) {
			$moduleperm_handler =& xoops_gethandler('groupperm');
			$ret = ($moduleperm_handler->checkRight('module_read', $XoopsModule->getVar('mid'), (is_object($this->xoopsUser)? $this->xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS)));
		}
	
		return $ret;
	}
	
	private function getAdminGroups($dirname = '') {
		$aGroups = array();
		if ($dirname === '') {
			$dirname = $this->mydirname;
			$module_handler = xoops_gethandler('module');
			$XoopsModule = $module_handler->getByDirname($dirname);
		} else {
			$XoopsModule = $this->xoopsModule;
		}
		if ($XoopsModule) {
			$mid = $XoopsModule->getVar('mid');
			$hGroupperm = xoops_gethandler('groupperm');
			$hGroup = xoops_gethandler('group');
			$groups = $hGroup->getObjects(null, true);
			foreach($groups as $gid => $group) {
				if ($hGroupperm->checkRight('module_admin', $mid, $gid)) {
					$aGroups[] = $group;
				}
			}
		}
		return $aGroups;
	}
	
	public function setConfig($config) {
		$this->config = $config;
		$this->inSpecialGroup = (array_intersect($this->mygids, ( isset($config['special_groups'])? $config['special_groups'] : array() )));
	}
	
	public function setLogfile($path = '') {
		if ($path) {
			$this->file = $path;
			$dir = dirname($path);
			if (!is_dir($dir)) {
				mkdir($dir);
			}
		}
	}
	
	public function netmountPreCallback() {
		// check session table type
		if (strlen(serialize($_SESSION)) > 64000) {
			// expand session table size, change type to "MEDIUMBLOB"
			$stype = $this->getSessionTableType();
			if ($stype !== 'mediumblob' && $stype !== 'longblob') {
				$this->db->queryF('ALTER TABLE `'.$this->db->prefix('session').'` CHANGE `sess_data` `sess_data` MEDIUMBLOB NOT NULL');
			}
		}
	}
	
	public function netmountCallback($cmd, &$result, $args, $elfinder) {
		if (is_object($this->xoopsUser) && (!empty($result['sync']) || !empty($result['added']))) {
			if ($uid = $this->xoopsUser->getVar('uid')) {
				$uid = intval($uid);
				$table = $this->db->prefix($this->mydirname.'_userdat');
				$netVolumes = $this->db->quoteString(serialize($_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY]));
				$sql = 'SELECT `id` FROM `'.$table.'` WHERE `key`=\'netVolumes\' AND `uid`='.$uid;
				if ($res = $this->db->query($sql)) {
					if ($this->db->getRowsNum($res) > 0) {
						$sql = 'UPDATE `'.$table.'` SET `data`='.$netVolumes.', `mtime`='.time().' WHERE `key`=\'netVolumes\' AND `uid`='.$uid;
					} else {
						$sql = 'INSERT `'.$table.'` SET `key`=\'netVolumes\', `uid` = '.$uid.', `data`='.$netVolumes.', `mtime`='.time();
					}
					$this->db->queryF($sql);
				}
			}
		}
	}
	
	public function notifyMail($cmd, &$result, $args, $elfinder) {
		if (!empty($result['added'])) {
			$mail = false;
			if (is_object($this->xoopsUser)) {
				if ($this->isAdmin) {
					$mail = in_array(XOOPS_GROUP_ADMIN, $this->config['mail_notify_group']);
				} else {
					$mail = (array_intersect($this->config['mail_notify_group'], $this->mygids));
				}
			} else {
				$mail = ($this->config['mail_notify_guest']);
			}
			
			if ($mail) {
				$config_handler = xoops_gethandler('config');
				$xoopsConfig = $config_handler->getConfigsByCat(XOOPS_CONF);
				
				$sep = "\n".str_repeat('-', 40)."\n";
				$self = XOOPS_MODULE_URL . '/' . $this->mydirname . '/connector.php';
				if (is_object($this->xoopsUser)) {
					$uname = $this->xoopsUser->uname('n');
					$uid = $this->xoopsUser->uid();
				} else {
					$uname = $xoopsConfig['anonymous'];
					$uid = 0;
				}
				$date = date('c');
				
				$head = <<<EOD
USER: $uname
UID: $uid
IP: ${_SERVER['REMOTE_ADDR']}
CMD: $cmd
DATE: $date
EOD;
				$msg = array();
				
				foreach ($result['added'] as $file) {
					
					$url = 'unknown';
					if (!empty($file['url'])) {
						$url = ($file['url'] !=  1)? $file['url'] : 'ondemand';
					} else {
						$url = $self . '?cmd=file&target='.$file['hash'];
					}
					$dl = $self . '?cmd=file&download=1&target='.$file['hash'];
					$hash = $file['hash'];
					$path = $elfinder->realpath($file['hash']);
					$name = $file['name'];
					$manager = XOOPS_MODULE_URL . '/' . $this->mydirname . '/manager.php#elf_' . $file['phash'];
					$msg[] = <<<EOD
HASH: $hash
PATH: $path
NAME: $name
URL: $url
DOWNLOAD: $dl
MANAGER: $manager
EOD;
				}
			
				$sitename = $xoopsConfig['sitename'];
				$modname = $this->xoopsModule->getVar('name');
				$subject = '[' . $modname . '] Cmd: "'.$cmd.'" Report';
				$message = join($sep, $msg);
				if (strtoupper(_CHARSET) !== 'UTF-8') {
					ini_set('default_charset', _CHARSET);
					if (version_compare(PHP_VERSION, '5.6', '<')) {
						ini_set('mbstring.internal_encoding', _CHARSET);
					} else {
						@ini_set('mbstring.internal_encoding', '');
					}
					$message = mb_convert_encoding($message, _CHARSET, 'UTF-8');
				}
				
				$xoopsMailer = getMailer();
				$xoopsMailer->useMail();
				$xoopsMailer->setFromName($sitename.':'.$modname);
				$xoopsMailer->setSubject($subject);
				$xoopsMailer->setBody($head.$sep.$message);
				$xoopsMailer->setToGroups($this->getAdminGroups());
				$xoopsMailer->send();
				$xoopsMailer->reset();
			
				if (strtoupper(_CHARSET) !== 'UTF-8') {
					ini_set('mbstring.internal_encoding', 'UTF-8');
				}
			}
		}
	}
	
	public function changeAddParent($cmd, &$result, $args, $elfinder) {
		if (! empty($result['changed'])) {
			if (($target = $result['changed'][0]['phash'])
					&& ($volume = $elfinder->getVolume($target))){
				if ($parents = $volume->parents($target, true)) {
					$exist = array();
					foreach($result['changed'] as $changed) {
						$exist[$changed['hash']] = true;
					}
					foreach($parents as $changed) {
						if (! isset($exist[$changed['hash']])) {
							$result['changed'][] = $changed;
						}
					}
				}
			}
		}
	}
	
	/**
	 * Create log record
	 *
	 * @param  string   $cmd       command name
	 * @param  array    $result    command result
	 * @param  array    $args      command arguments from client
	 * @param  elFinder $elfinder  elFinder instance
	 * @return void|true
	 * @author Dmitry (dio) Levashov
	 **/
	public function log($cmd, &$result, $args, $elfinder) {
		$log = $cmd.' ['.date('d.m H:s')."]\n";
		
		if (!empty($result['error'])) {
			$log .= "\tERROR: ".implode(' ', $result['error'])."\n";
		}
	
		if (!empty($result['warning'])) {
			$log .= "\tWARNING: ".implode(' ', $result['warning'])."\n";
		}
	
		if (!empty($result['removed'])) {
			foreach ($result['removed'] as $file) {
				// removed file contain additional field "realpath"
				$log .= "\tREMOVED: ".$elfinder->realpath($file['hash'])."\n";
			}
		}
	
		if (!empty($result['added'])) {
			foreach ($result['added'] as $file) {
				$log .= "\tADDED: ".$elfinder->realpath($file['hash'])."\n";
			}
		}
	
		if (!empty($result['changed'])) {
			foreach ($result['changed'] as $file) {
				$log .= "\tCHANGED: ".$elfinder->realpath($file['hash'])."\n";
			}
		}
		
		$this->write($log);
	}
	
	/**
	 * Write log into file
	 *
	 * @param  string  $log  log record
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function write($log) {
	
		if ($this->file && ($fp = @fopen($this->file, 'a'))) {
			fwrite($fp, $log."\n");
			fclose($fp);
		}
	}
	
	/**
	 * JPEG image auto rotation by EXIF info for OnUpLoadPreSave callback
	 * 
	 * @param string $path
	 * @param string $name
	 * @param string $src
	 * @param object $elfinder
	 * @param object $volume
	 * @return boolean
	 */
	public function autoRotateOnUpLoadPreSave(&$path, &$name, $src, $elfinder, $volume) {
		if (! class_exists('HypCommonFunc') || version_compare(HypCommonFunc::get_version(), '20150515', '<')) {
			return false;
		}
		$srcImgInfo = @getimagesize($src);
		if ($srcImgInfo === false) {
			return false;
		}
		if (! in_array($srcImgInfo[2], array(IMAGETYPE_JPEG, IMAGETYPE_JPEG2000))) {
			return false;
		}
		$ret = HypCommonFunc::rotateImage($src, 0, 95, $srcImgInfo);
		// remove exif gps info
		HypCommonFunc::removeExifGps($src, $srcImgInfo);
		return ($ret);
	}
	
	/**
	 * Get DB session table data type
	 * 
	 * @return string
	 */
	public function getSessionTableType() {
		$db = $this->db;
		$sql = 'SHOW COLUMNS FROM `'. $db->prefix('session') .'` WHERE Field = \'sess_data\'';
		if ($res = $db->queryF($sql)) {
			if ($row = $db->fetchArray($res)) {
				return strtolower($row['Type']);
			}
		}
		return '';
	}
	
	/**
	 * Get uname by uid
	 * @param int $uid
	 * @return string
	 */
	public static function getUnameByUid($uid){
		static $unames = array();
		static $db = null;
	
		$uid = (int)$uid;
		if (isset($unames[$uid])) {
			return $unames[$uid];
		}
		
		if (is_null($db)) {
			$db = XoopsDatabaseFactory::getDatabaseConnection();
		}
		
		if ($uid === 0) {
			$config_handler = xoops_gethandler('config');
			$xoopsConfig = $config_handler->getConfigsByCat(XOOPS_CONF);
			$uname = $xoopsConfig['anonymous'];
			if (self::$dbCharset === 'utf8' && strtoupper(_CHARSET) !== 'UTF-8') {
				$uname = mb_convert_encoding($uname, 'UTF-8', _CHARSET);
			}
		} else {
			$query = 'SELECT `uname` FROM `'.$db->prefix('users').'` WHERE uid=' . $uid . ' LIMIT 1';
			if ($result = $db->query($query)) {
				list($uname) = $db->fetchRow($result);
			}
			if ((string)$uname === '') {
				return self::getUnameByUid(0);
			}
		}
		if (self::$dbCharset !== 'utf8' && strtoupper(_CHARSET) !== 'UTF-8') {
			$uname = mb_convert_encoding($uname, 'UTF-8', _CHARSET);
		}
		return $unames[$uid] = $uname;
	}
	
	/**
	 * Sets the default client character set
	 * 
	 * @param string $charset
	 * @return bool
	 */
	public static function dbSetCharset($charset = 'utf8') {
		static $link = null;
		if (is_null($link)) {
			$db = XoopsDatabaseFactory::getDatabaseConnection();
			$link = (is_object($db->conn) && get_class($db->conn) === 'mysqli')? $db->conn : false;
		}
		self::$dbCharset = $charset;
		if ($link) {
			return mysqli_set_charset($link, $charset);
		} else {
			return mysql_set_charset($charset);
		}
	}
}
