<?php
/*
 * Created on 2012/01/20 by nao-pon http://xoops.hypweb.net/
 * $Id: xoops_elFinder.class.php,v 1.1 2012/01/20 13:32:02 nao-pon Exp $
 */

class xoops_elFinder {

	protected $db;
	public $xoopsUser;
	public $mydirname;
	
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
			'defaults' => array('read' => true, 'write' => false)
	);
	
	public function __construct($mydirname, $opt = array()) {
		global $xoopsUser;
		$this->xoopsUser = $xoopsUser;
		$this->mydirname = $mydirname;
		$this->db = & XoopsDatabaseFactory::getDatabaseConnection();
		$this->defaultVolumeOptions = array_merge($this->defaultVolumeOptions, $opt);
		if (!isset($_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY]) && is_object($this->xoopsUser)) {
			if ($uid = $this->xoopsUser->getVar('uid')) {
				$uid = intval($uid);
				$table = $this->db->prefix($this->mydirname.'_userdat');
				$sql = 'SELECT `data` FROM `'.$table.'` WHERE `key`=\'netVolumes\' AND `uid`='.$uid.' LIMIT 1';
				if ($res = $this->db->query($sql)) {
					if ($this->db->getRowsNum($res) > 0) {
						list($data) = $this->db->fetchRow($res);
						if ($data = @unserialize($data)) {
							$_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY] = $data;
						}
					}
				}
			}
		}
	}
	
	public function getRootVolumes($config, $extras = array()) {
		$pluginPath = dirname(dirname(__FILE__)) . '/plugins/';
		$configs = explode("\n", $config);
		$roots = array();
		foreach($configs as $_conf) {
			$_conf = trim($_conf);
			if (! $_conf || $_conf[0] === '#') continue;
			$_confs = explode(':', $_conf);
			$_confs = array_map('trim', $_confs);
			list($mydirname, $plugin, $path, $title, $options) = array_pad($_confs, 6, '');
			if (! $this->moduleCheckRight($mydirname)) continue;
			if ($title === '') $title = $mydirname;
			$path = '/' . trim($path, '/') . '/';
			$volume = $pluginPath . $plugin . '/volume.php';
			if (is_file($volume)) {
				$extra = isset($extras[$mydirname.':'.$plugin])? $extras[$mydirname.':'.$plugin] : array();
				$volumeOptions = array();
				require $volume;
				if ($volumeOptions) {
					$volumeOptions = array_merge($this->defaultVolumeOptions, $volumeOptions, $extra);
					$roots[] = $volumeOptions;
				}
			}
		}
		return $roots;
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
	
	public function setLogfile($path = '') {
		if ($path) {
			$this->file = $path;
			$dir = dirname($path);
			if (!is_dir($dir)) {
				mkdir($dir);
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
	public function log($cmd, $result, $args, $elfinder) {
	
		if ($cmd === 'netmount' && is_object($this->xoopsUser) && !empty($result['sync'])) {
			if ($uid = $this->xoopsUser->getVar('uid')) {
				$uid = intval($uid);
				$table = $this->db->prefix($this->mydirname.'_userdat');
				$netVolumes = mysql_real_escape_string(serialize($_SESSION[_MD_XELFINDER_NETVOLUME_SESSION_KEY]));
				$sql = 'SELECT `id` FROM `'.$table.'` WHERE `key`=\'netVolumes\' AND `uid`='.$uid;
				if ($res = $this->db->query($sql)) {
					if ($this->db->getRowsNum($res) > 0) {
						$sql = 'UPDATE `'.$table.'` SET `data`="'.$netVolumes.'", `mtime`='.time().' WHERE `key`=\'netVolumes\' AND `uid`='.$uid;
					} else {
						$sql = 'INSERT `'.$table.'` SET `key`=\'netVolumes\', `uid` = '.$uid.', `data`="'.$netVolumes.'", `mtime`='.time();
					}
					$this->db->queryF($sql);
				}
			}
				
		}
	
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
				$log .= "\tREMOVED: ".$file['realpath']."\n";
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
	
}
