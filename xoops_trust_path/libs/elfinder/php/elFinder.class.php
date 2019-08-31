<?php

/**
 * elFinder - file manager for web.
 * Core class.
 *
 * @package elfinder
 * @author  Dmitry (dio) Levashov
 * @author  Troex Nevelin
 * @author  Alexey Sukhotin
 **/
class elFinder
{
    /**
     * API version number
     *
     * @var float
     **/
    protected static $ApiVersion = 2.1;

    /**
     * API version number
     *
     * @deprecated
     * @var string
     **/
    protected $version;

    /**
     * API revision that this connector supports all functions
     *
     * @var integer
     */
    protected static $ApiRevision = 50;

    /**
     * Storages (root dirs)
     *
     * @var array
     **/
    protected $volumes = [];

    /**
     * elFinder instance
     *
     * @var object
     */
    public static $instance = null;

    /**
     * Current request args
     *
     * @var array
     */
    public static $currentArgs = [];

    /**
     * Network mount drivers
     *
     * @var array
     */
    public static $netDrivers = [];

    /**
     * elFinder global locale
     *
     * @var string
     */
    public static $locale = '';

    /**
     * elFinderVolumeDriver default mime.type file path
     *
     * @var string
     */
    public static $defaultMimefile = '';

    /**
     * A file save destination path when a temporary content URL is required
     * on a network volume or the like
     * It can be overwritten by volume route setting
     *
     * @var string
     */
    public static $tmpLinkPath = '';

    /**
     * A file save destination URL when a temporary content URL is required
     * on a network volume or the like
     * It can be overwritten by volume route setting
     *
     * @var string
     */
    public static $tmpLinkUrl = '';

    /**
     * Temporary content URL lifetime (seconds)
     *
     * @var integer
     */
    public static $tmpLinkLifeTime = 3600;

    /**
     * MIME type list handled as a text file
     *
     * @var array
     */
    public static $textMimes = [
        'application/dash+xml',
        'application/docbook+xml',
        'application/javascript',
        'application/json',
        'application/plt',
        'application/sat',
        'application/sql',
        'application/step',
        'application/vnd.hp-hpgl',
        'application/x-awk',
        'application/x-config',
        'application/x-csh',
        'application/x-empty',
        'application/x-mpegurl',
        'application/x-perl',
        'application/x-php',
        'application/x-web-config',
        'application/xhtml+xml',
        'application/xml',
        'audio/x-mp3-playlist',
        'image/cgm',
        'image/svg+xml',
        'image/vnd.dxf',
        'model/iges',
    ];

    /**
     * Maximum memory size to be extended during GD processing
     * (0: not expanded, -1: unlimited or memory size notation)
     *
     * @var integer|string
     */
    public static $memoryLimitGD = 0;

    /**
     * Path of current request flag file for abort check
     *
     * @var string
     */
    protected static $abortCheckFile = null;

    /**
     * elFinder session wrapper object
     *
     * @var elFinderSessionInterface
     */
    protected $session;

    /**
     * elFinder global sessionCacheKey
     *
     * @deprecated
     * @var string
     */
    public static $sessionCacheKey = '';

    /**
     * Is session closed
     *
     * @deprecated
     * @var bool
     */
    private static $sessionClosed = false;

    /**
     * elFinder base64encodeSessionData
     * elFinder save session data as `UTF-8`
     * If the session storage mechanism of the system does not allow `UTF-8`
     * And it must be `true` option 'base64encodeSessionData' of elFinder
     * WARNING: When enabling this option, if saving the data passed from the user directly to the session variable,
     * it make vulnerable to the object injection attack, so use it carefully.
     * see https://github.com/Studio-42/elFinder/issues/2345
     *
     * @var bool
     */
    protected static $base64encodeSessionData = false;

    /**
     * elFinder common tempraly path
     *
     * @var string
     * @default "./.tmp" or sys_get_temp_dir()
     **/
    protected static $commonTempPath = '';

    /**
     * Callable function for URL upload filter
     * The first argument is a URL and the second argument is an instance of the elFinder class
     * A filter should be return true (to allow) / false (to disallow)
     *
     * @var callable
     * @default null
     */
    protected $urlUploadFilter = null;

    /**
     * Connection flag files path that connection check of current request
     *
     * @var string
     * @default value of $commonTempPath
     */
    protected static $connectionFlagsPath = '';

    /**
     * Additional volume root options for network mounting volume
     *
     * @var array
     */
    protected $optionsNetVolumes = [];

    /**
     * Session key of net mount volumes
     *
     * @deprecated
     * @var string
     */
    protected $netVolumesSessionKey = '';

    /**
     * Mounted volumes count
     * Required to create unique volume id
     *
     * @var int
     **/
    public static $volumesCnt = 1;

    /**
     * Default root (storage)
     *
     * @var elFinderVolumeDriver
     **/
    protected $default = null;

    /**
     * Commands and required arguments list
     *
     * @var array
     **/
    protected $commands = [
        'abort' => ['id' => true],
        'archive' => ['targets' => true, 'type' => true, 'mimes' => false, 'name' => false],
        'callback' => ['node' => true, 'json' => false, 'bind' => false, 'done' => false],
        'chmod' => ['targets' => true, 'mode' => true],
        'dim' => ['target' => true, 'substitute' => false],
        'duplicate' => ['targets' => true, 'suffix' => false],
        'editor' => ['name' => true, 'method' => true, 'args' => false],
        'extract' => ['target' => true, 'mimes' => false, 'makedir' => false],
        'file' => ['target' => true, 'download' => false, 'cpath' => false, 'onetime' => false],
        'get' => ['target' => true, 'conv' => false],
        'info' => ['targets' => true, 'compare' => false],
        'ls' => ['target' => true, 'mimes' => false, 'intersect' => false],
        'mkdir' => ['target' => true, 'name' => false, 'dirs' => false],
        'mkfile' => ['target' => true, 'name' => true, 'mimes' => false],
        'netmount' => ['protocol' => true, 'host' => true, 'path' => false, 'port' => false, 'user' => false, 'pass' => false, 'alias' => false, 'options' => false],
        'open' => ['target' => false, 'tree' => false, 'init' => false, 'mimes' => false, 'compare' => false],
        'parents' => ['target' => true, 'until' => false],
        'paste' => ['dst' => true, 'targets' => true, 'cut' => false, 'mimes' => false, 'renames' => false, 'hashes' => false, 'suffix' => false],
        'put' => ['target' => true, 'content' => '', 'mimes' => false, 'encoding' => false],
        'rename' => ['target' => true, 'name' => true, 'mimes' => false, 'targets' => false, 'q' => false],
        'resize' => ['target' => true, 'width' => false, 'height' => false, 'mode' => false, 'x' => false, 'y' => false, 'degree' => false, 'quality' => false, 'bg' => false],
        'rm' => ['targets' => true],
        'search' => ['q' => true, 'mimes' => false, 'target' => false, 'type' => false],
        'size' => ['targets' => true],
        'subdirs' => ['targets' => true],
        'tmb' => ['targets' => true],
        'tree' => ['target' => true],
        'upload' => ['target' => true, 'FILES' => true, 'mimes' => false, 'html' => false, 'upload' => false, 'name' => false, 'upload_path' => false, 'chunk' => false, 'cid' => false, 'node' => false, 'renames' => false, 'hashes' => false, 'suffix' => false, 'mtime' => false, 'overwrite' => false, 'contentSaveId' => false],
        'url' => ['target' => true, 'options' => false],
        'zipdl' => ['targets' => true, 'download' => false],
    ];

    /**
     * Plugins instance
     *
     * @var array
     **/
    protected $plugins = [];

    /**
     * Commands listeners
     *
     * @var array
     **/
    protected $listeners = [];

    /**
     * script work time for debug
     *
     * @var string
     **/
    protected $time = 0;
    /**
     * Is elFinder init correctly?
     *
     * @var bool
     **/
    protected $loaded = false;
    /**
     * Send debug to client?
     *
     * @var string
     **/
    protected $debug = false;

    /**
     * Call `session_write_close()` before exec command?
     *
     * @var bool
     */
    protected $sessionCloseEarlier = true;

    /**
     * SESSION use commands @see __construct()
     *
     * @var array
     */
    protected $sessionUseCmds = [];

    /**
     * session expires timeout
     *
     * @var int
     **/
    protected $timeout = 0;

    /**
     * Temp dir path for Upload
     *
     * @var string
     */
    protected $uploadTempPath = '';

    /**
     * Max allowed archive files size (0 - no limit)
     *
     * @var integer
     */
    protected $maxArcFilesSize = 0;

    /**
     * undocumented class variable
     *
     * @var string
     **/
    protected $uploadDebug = '';

    /**
     * Max allowed numbar of targets (0 - no limit)
     *
     * @var integer
     */
    public $maxTargets = 1000;

    /**
     * Errors from PHP
     *
     * @var array
     **/
    public static $phpErrors = [];

    /**
     * Errors from not mounted volumes
     *
     * @var array
     **/
    public $mountErrors = [];

    /**
     * Archivers cache
     *
     * @var array
     */
    public static $archivers = [];

    /**
     * URL for callback output window for CORS
     * redirect to this URL when callback output
     *
     * @var string URL
     */
    protected $callbackWindowURL = '';

    /**
     * hash of items to unlock on command completion
     *
     * @var array hashes
     */
    protected $autoUnlocks = [];

    /**
     * Item locking expiration (seconds)
     * Default: 3600 secs
     *
     * @var integer
     */
    protected $itemLockExpire = 3600;

    /**
     * Additional request querys
     *
     * @var array|null
     */
    protected $customData = null;

    /**
     * Ids to remove of session var "urlContentSaveIds" for contents uploading by URL
     *
     * @var array
     */
    protected $removeContentSaveIds = [];

    /**
     * Flag of throw Error on exec()
     *
     * @var boolean
     */
    protected $throwErrorOnExec = false;

    // Errors messages
    const ERROR_ACCESS_DENIED = 'errAccess';
    const ERROR_ARC_MAXSIZE = 'errArcMaxSize';
    const ERROR_ARC_SYMLINKS = 'errArcSymlinks';
    const ERROR_ARCHIVE = 'errArchive';
    const ERROR_ARCHIVE_EXEC = 'errArchiveExec';
    const ERROR_ARCHIVE_TYPE = 'errArcType';
    const ERROR_CONF = 'errConf';
    const ERROR_CONF_NO_JSON = 'errJSON';
    const ERROR_CONF_NO_VOL = 'errNoVolumes';
    const ERROR_CONV_UTF8 = 'errConvUTF8';
    const ERROR_COPY = 'errCopy';
    const ERROR_COPY_FROM = 'errCopyFrom';
    const ERROR_COPY_ITSELF = 'errCopyInItself';
    const ERROR_COPY_TO = 'errCopyTo';
    const ERROR_CREATING_TEMP_DIR = 'errCreatingTempDir';
    const ERROR_DIR_NOT_FOUND = 'errFolderNotFound';
    const ERROR_EXISTS = 'errExists';        // 'File named "$1" already exists.'
    const ERROR_EXTRACT = 'errExtract';
    const ERROR_EXTRACT_EXEC = 'errExtractExec';
    const ERROR_FILE_NOT_FOUND = 'errFileNotFound';     // 'File not found.'
    const ERROR_FTP_DOWNLOAD_FILE = 'errFtpDownloadFile';
    const ERROR_FTP_MKDIR = 'errFtpMkdir';
    const ERROR_FTP_UPLOAD_FILE = 'errFtpUploadFile';
    const ERROR_INV_PARAMS = 'errCmdParams';
    const ERROR_INVALID_DIRNAME = 'errInvDirname';    // 'Invalid folder name.'
    const ERROR_INVALID_NAME = 'errInvName';       // 'Invalid file name.'
    const ERROR_LOCKED = 'errLocked';        // '"$1" is locked and can not be renamed, moved or removed.'
    const ERROR_MAX_TARGTES = 'errMaxTargets'; // 'Max number of selectable items is $1.'
    const ERROR_MKDIR = 'errMkdir';
    const ERROR_MKFILE = 'errMkfile';
    const ERROR_MKOUTLINK = 'errMkOutLink';        // 'Unable to create a link to outside the volume root.'
    const ERROR_MOVE = 'errMove';
    const ERROR_NETMOUNT = 'errNetMount';
    const ERROR_NETMOUNT_FAILED = 'errNetMountFailed';
    const ERROR_NETMOUNT_NO_DRIVER = 'errNetMountNoDriver';
    const ERROR_NETUNMOUNT = 'errNetUnMount';
    const ERROR_NOT_ARCHIVE = 'errNoArchive';
    const ERROR_NOT_DIR = 'errNotFolder';
    const ERROR_NOT_FILE = 'errNotFile';
    const ERROR_NOT_REPLACE = 'errNotReplace';       // Object "$1" already exists at this location and can not be replaced with object of another type.
    const ERROR_NOT_UTF8_CONTENT = 'errNotUTF8Content';
    const ERROR_OPEN = 'errOpen';
    const ERROR_PERM_DENIED = 'errPerm';
    const ERROR_REAUTH_REQUIRE = 'errReauthRequire';  // 'Re-authorization is required.'
    const ERROR_RENAME = 'errRename';
    const ERROR_REPLACE = 'errReplace';          // 'Unable to replace "$1".'
    const ERROR_RESIZE = 'errResize';
    const ERROR_RESIZESIZE = 'errResizeSize';
    const ERROR_RM = 'errRm';               // 'Unable to remove "$1".'
    const ERROR_RM_SRC = 'errRmSrc';            // 'Unable remove source file(s)'
    const ERROR_SAVE = 'errSave';
    const ERROR_SEARCH_TIMEOUT = 'errSearchTimeout';    // 'Timed out while searching "$1". Search result is partial.'
    const ERROR_SESSION_EXPIRES = 'errSessionExpires';
    const ERROR_TRGDIR_NOT_FOUND = 'errTrgFolderNotFound'; // 'Target folder "$1" not found.'
    const ERROR_UNKNOWN = 'errUnknown';
    const ERROR_UNKNOWN_CMD = 'errUnknownCmd';
    const ERROR_UNSUPPORT_TYPE = 'errUsupportType';
    const ERROR_UPLOAD = 'errUpload';           // 'Upload error.'
    const ERROR_UPLOAD_FILE = 'errUploadFile';       // 'Unable to upload "$1".'
    const ERROR_UPLOAD_FILE_MIME = 'errUploadMime';       // 'File type not allowed.'
    const ERROR_UPLOAD_FILE_SIZE = 'errUploadFileSize';   // 'File exceeds maximum allowed size.'
    const ERROR_UPLOAD_NO_FILES = 'errUploadNoFiles';    // 'No files found for upload.'
    const ERROR_UPLOAD_TEMP = 'errUploadTemp';       // 'Unable to make temporary file for upload.'
    const ERROR_UPLOAD_TOTAL_SIZE = 'errUploadTotalSize';  // 'Data exceeds the maximum allowed size.'
    const ERROR_UPLOAD_TRANSFER = 'errUploadTransfer';   // '"$1" transfer error.'

    /**
     * Constructor
     *
     * @param  array  elFinder and roots configurations
     * @param mixed $opts
     *
     * @author Dmitry (dio) Levashov
     */
    public function __construct($opts)
    {
        // set default_charset
        if (version_compare(PHP_VERSION, '5.6', '>=')) {
            if (($_val = ini_get('iconv.internal_encoding')) && 'UTF-8' !== mb_strtoupper($_val)) {
                ini_set('iconv.internal_encoding', '');
            }
            if (($_val = ini_get('mbstring.internal_encoding')) && 'UTF-8' !== mb_strtoupper($_val)) {
                ini_set('mbstring.internal_encoding', '');
            }
            if (($_val = ini_get('internal_encoding')) && 'UTF-8' !== mb_strtoupper($_val)) {
                ini_set('internal_encoding', '');
            }
        } else {
            if (function_exists('iconv_set_encoding') && 'UTF-8' !== mb_strtoupper(iconv_get_encoding('internal_encoding'))) {
                iconv_set_encoding('internal_encoding', 'UTF-8');
            }
            if (function_exists('mb_internal_encoding') && 'UTF-8' !== mb_strtoupper(mb_internal_encoding())) {
                mb_internal_encoding('UTF-8');
            }
        }
        ini_set('default_charset', 'UTF-8');

        // define accept constant of server commands path
        !defined('ELFINDER_TAR_PATH') && define('ELFINDER_TAR_PATH', 'tar');
        !defined('ELFINDER_GZIP_PATH') && define('ELFINDER_GZIP_PATH', 'gzip');
        !defined('ELFINDER_BZIP2_PATH') && define('ELFINDER_BZIP2_PATH', 'bzip2');
        !defined('ELFINDER_XZ_PATH') && define('ELFINDER_XZ_PATH', 'xz');
        !defined('ELFINDER_ZIP_PATH') && define('ELFINDER_ZIP_PATH', 'zip');
        !defined('ELFINDER_UNZIP_PATH') && define('ELFINDER_UNZIP_PATH', 'unzip');
        !defined('ELFINDER_RAR_PATH') && define('ELFINDER_RAR_PATH', 'rar');
        !defined('ELFINDER_UNRAR_PATH') && define('ELFINDER_UNRAR_PATH', 'unrar');
        !defined('ELFINDER_7Z_PATH') && define('ELFINDER_7Z_PATH', ('WIN' === mb_substr(PHP_OS, 0, 3)) ? '7z' : '7za');
        !defined('ELFINDER_CONVERT_PATH') && define('ELFINDER_CONVERT_PATH', 'convert');
        !defined('ELFINDER_IDENTIFY_PATH') && define('ELFINDER_IDENTIFY_PATH', 'identify');
        !defined('ELFINDER_EXIFTRAN_PATH') && define('ELFINDER_EXIFTRAN_PATH', 'exiftran');
        !defined('ELFINDER_JPEGTRAN_PATH') && define('ELFINDER_JPEGTRAN_PATH', 'jpegtran');
        !defined('ELFINDER_FFMPEG_PATH') && define('ELFINDER_FFMPEG_PATH', 'ffmpeg');

        !defined('ELFINDER_DISABLE_ZIPEDITOR') && define('ELFINDER_DISABLE_ZIPEDITOR', false);

        // enable(true)/disable(false) handling postscript on ImageMagick
        // Should be `false` as long as there is a Ghostscript vulnerability
        // see https://artifex.com/news/ghostscript-security-resolved/
        !defined('ELFINDER_IMAGEMAGICK_PS') && define('ELFINDER_IMAGEMAGICK_PS', false);

        // for backward compat
        $this->version = (string)self::$ApiVersion;

        // set error handler of WARNING, NOTICE
        $errLevel = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_STRICT | E_RECOVERABLE_ERROR;
        if (defined('E_DEPRECATED')) {
            $errLevel |= E_DEPRECATED | E_USER_DEPRECATED;
        }
        set_error_handler('elFinder::phpErrorHandler', $errLevel);

        // Associative array of files to delete at the end of script: ['temp file path' => true]
        $GLOBALS['elFinderTempFiles'] = [];
        // regist Shutdown function
        register_shutdown_function(['elFinder', 'onShutdown']);

        // convert PATH_INFO to GET query
        if (!empty($_SERVER['PATH_INFO'])) {
            $_ps = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (!isset($_GET['cmd'])) {
                $_cmd = $_ps[0];
                if (isset($this->commands[$_cmd])) {
                    $_GET['cmd'] = $_cmd;
                    $_i = 1;
                    foreach (array_keys($this->commands[$_cmd]) as $_k) {
                        if (isset($_ps[$_i])) {
                            if (!isset($_GET[$_k])) {
                                $_GET[$_k] = $_ps[$_i];
                            }
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        // set elFinder instance
        self::$instance = $this;

        // setup debug mode
        $this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
        if ($this->debug) {
            error_reporting(defined('ELFINDER_DEBUG_ERRORLEVEL') ? ELFINDER_DEBUG_ERRORLEVEL : -1);
            ini_set('display_errors', '1');
            // clear output buffer and stop output filters
            while (ob_get_level() && ob_end_clean()) {
            }
        }

        if (!interface_exists('elFinderSessionInterface')) {
            include_once dirname(__FILE__) . '/elFinderSessionInterface.php';
        }

        // session handler
        if (!empty($opts['session']) && $opts['session'] instanceof elFinderSessionInterface) {
            $this->session = $opts['session'];
        } else {
            $sessionOpts = [
                'base64encode' => !empty($opts['base64encodeSessionData']),
                'keys' => [
                    'default' => !empty($opts['sessionCacheKey']) ? $opts['sessionCacheKey'] : 'elFinderCaches',
                    'netvolume' => !empty($opts['netVolumesSessionKey']) ? $opts['netVolumesSessionKey'] : 'elFinderNetVolumes',
                ],
            ];
            if (!class_exists('elFinderSession')) {
                include_once dirname(__FILE__) . '/elFinderSession.php';
            }
            $this->session = new elFinderSession($sessionOpts);
        }
        // try session start | restart
        $this->session->start();

        // 'netmount' added to handle requests synchronously on unmount
        $sessionUseCmds = ['netmount'];
        if (isset($opts['sessionUseCmds']) && is_array($opts['sessionUseCmds'])) {
            $sessionUseCmds = array_merge($sessionUseCmds, $opts['sessionUseCmds']);
        }

        // set self::$volumesCnt by HTTP header "X-elFinder-VolumesCntStart"
        if (isset($_SERVER['HTTP_X_ELFINDER_VOLUMESCNTSTART']) && ($volumesCntStart = intval($_SERVER['HTTP_X_ELFINDER_VOLUMESCNTSTART']))) {
            self::$volumesCnt = $volumesCntStart;
        }

        $this->time = $this->utime();
        $this->sessionCloseEarlier = isset($opts['sessionCloseEarlier']) ? (bool)$opts['sessionCloseEarlier'] : true;
        $this->sessionUseCmds = array_flip($sessionUseCmds);
        $this->timeout = (isset($opts['timeout']) ? $opts['timeout'] : 0);
        $this->uploadTempPath = (isset($opts['uploadTempPath']) ? $opts['uploadTempPath'] : '');
        $this->callbackWindowURL = (isset($opts['callbackWindowURL']) ? $opts['callbackWindowURL'] : '');
        $this->maxTargets = (isset($opts['maxTargets']) ? intval($opts['maxTargets']) : $this->maxTargets);
        self::$commonTempPath = (isset($opts['commonTempPath']) ? realpath($opts['commonTempPath']) : dirname(__FILE__) . '/.tmp');
        if (!is_writable(self::$commonTempPath)) {
            self::$commonTempPath = sys_get_temp_dir();
            if (!is_writable(self::$commonTempPath)) {
                self::$commonTempPath = '';
            }
        }
        if (isset($opts['connectionFlagsPath']) && is_writable($opts['connectionFlagsPath'] = realpath($opts['connectionFlagsPath']))) {
            self::$connectionFlagsPath = $opts['connectionFlagsPath'];
        } else {
            self::$connectionFlagsPath = self::$commonTempPath;
        }

        if (!empty($opts['tmpLinkPath'])) {
            self::$tmpLinkPath = realpath($opts['tmpLinkPath']);
        }
        if (!empty($opts['tmpLinkUrl'])) {
            self::$tmpLinkUrl = $opts['tmpLinkUrl'];
        }
        if (!empty($opts['tmpLinkLifeTime'])) {
            self::$tmpLinkLifeTime = $opts['tmpLinkLifeTime'];
        }
        if (!empty($opts['textMimes']) && is_array($opts['textMimes'])) {
            self::$textMimes = $opts['textMimes'];
        }
        if (!empty($opts['urlUploadFilter'])) {
            $this->urlUploadFilter = $opts['urlUploadFilter'];
        }
        $this->maxArcFilesSize = isset($opts['maxArcFilesSize']) ? intval($opts['maxArcFilesSize']) : 0;
        $this->optionsNetVolumes = (isset($opts['optionsNetVolumes']) && is_array($opts['optionsNetVolumes'])) ? $opts['optionsNetVolumes'] : [];
        if (isset($opts['itemLockExpire'])) {
            $this->itemLockExpire = intval($opts['itemLockExpire']);
        }

        // deprecated settings
        $this->netVolumesSessionKey = !empty($opts['netVolumesSessionKey']) ? $opts['netVolumesSessionKey'] : 'elFinderNetVolumes';
        self::$sessionCacheKey = !empty($opts['sessionCacheKey']) ? $opts['sessionCacheKey'] : 'elFinderCaches';

        // check session cache
        $_optsMD5 = md5(json_encode($opts['roots']));
        if ($this->session->get('_optsMD5') !== $_optsMD5) {
            $this->session->set('_optsMD5', $_optsMD5);
        }

        // setlocale and global locale regists to elFinder::locale
        self::$locale = !empty($opts['locale']) ? $opts['locale'] : ('WIN' === mb_substr(PHP_OS, 0, 3) ? 'C' : 'en_US.UTF-8');
        if (false === setlocale(LC_ALL, self::$locale)) {
            self::$locale = setlocale(LC_ALL, '0');
        }

        // set defaultMimefile
        self::$defaultMimefile = isset($opts['defaultMimefile']) ? $opts['defaultMimefile'] : '';

        // set memoryLimitGD
        self::$memoryLimitGD = isset($opts['memoryLimitGD']) ? $opts['memoryLimitGD'] : 0;

        // set flag of throwErrorOnExec
        // `true` need `try{}` block for `$connector->run();`
        $this->throwErrorOnExec = !empty($opts['throwErrorOnExec']);

        // set archivers
        self::$archivers = isset($opts['archivers']) && is_array($opts['archivers']) ? $opts['archivers'] : [];

        // bind events listeners
        if (!empty($opts['bind']) && is_array($opts['bind'])) {
            $_req = 'POST' == $_SERVER['REQUEST_METHOD'] ? $_POST : $_GET;
            $_reqCmd = isset($_req['cmd']) ? $_req['cmd'] : '';
            foreach ($opts['bind'] as $cmd => $handlers) {
                $doRegist = (false !== mb_strpos($cmd, '*'));
                if (!$doRegist) {
                    $doRegist = ($_reqCmd && in_array($_reqCmd, array_map('self::getCmdOfBind', explode(' ', $cmd))));
                }
                if ($doRegist) {
                    // for backward compatibility
                    if (!is_array($handlers)) {
                        $handlers = [$handlers];
                    } else {
                        if (2 === count($handlers) && is_callable($handlers)) {
                            $handlers = [$handlers];
                        }
                    }
                    foreach ($handlers as $handler) {
                        if ($handler) {
                            if (is_string($handler) && mb_strpos($handler, '.')) {
                                list($_domain, $_name, $_method) = array_pad(explode('.', $handler), 3, '');
                                if (0 === strcasecmp($_domain, 'plugin')) {
                                    if ($plugin = $this->getPluginInstance($_name, isset($opts['plugin'][$_name]) ? $opts['plugin'][$_name] : [])
                                        and method_exists($plugin, $_method)) {
                                        $this->bind($cmd, [$plugin, $_method]);
                                    }
                                }
                            } else {
                                $this->bind($cmd, $handler);
                            }
                        }
                    }
                }
            }
        }

        if (!isset($opts['roots']) || !is_array($opts['roots'])) {
            $opts['roots'] = [];
        }

        // try to enable elFinderVolumeFlysystemZipArchiveNetmount to zip editing
        if (empty(self::$netDrivers['ziparchive'])) {
            self::$netDrivers['ziparchive'] = 'FlysystemZipArchiveNetmount';
        }

        // check for net volumes stored in session
        $netVolumes = $this->getNetVolumes();
        foreach ($netVolumes as $key => $root) {
            if (!isset($root['id'])) {
                // given fixed unique id
                if (!$root['id'] = $this->getNetVolumeUniqueId($netVolumes)) {
                    $this->mountErrors[] = 'Netmount Driver "' . $root['driver'] . '" : Could\'t given volume id.';
                    continue;
                }
            }
            $opts['roots'][$key] = $root;
        }

        // "mount" volumes
        foreach ($opts['roots'] as $i => $o) {
            $class = 'elFinderVolume' . (isset($o['driver']) ? $o['driver'] : '');

            if (class_exists($class)) {
                /* @var elFinderVolumeDriver $volume */
                $volume = new $class();

                try {
                    if ($this->maxArcFilesSize && (empty($o['maxArcFilesSize']) || $this->maxArcFilesSize < $o['maxArcFilesSize'])) {
                        $o['maxArcFilesSize'] = $this->maxArcFilesSize;
                    }
                    // pass session handler
                    $volume->setSession($this->session);
                    if ($volume->mount($o)) {
                        // unique volume id (ends on "_") - used as prefix to files hash
                        $id = $volume->id();

                        $this->volumes[$id] = $volume;
                        if ((!$this->default || $volume->root() !== $volume->defaultPath()) && $volume->isReadable()) {
                            $this->default = $this->volumes[$id];
                        }
                    } else {
                        $this->removeNetVolume($i, $volume);
                        $this->mountErrors[] = 'Driver "' . $class . '" : ' . implode(' ', $volume->error());
                    }
                } catch (Exception $e) {
                    $this->removeNetVolume($i, $volume);
                    $this->mountErrors[] = 'Driver "' . $class . '" : ' . $e->getMessage();
                }
            } else {
                $this->removeNetVolume($i, null);
                $this->mountErrors[] = 'Driver "' . $class . '" does not exist';
            }
        }

        // if at least one readable volume - ii desu >_<
        $this->loaded = !empty($this->default);

        // restore error handler for now
        restore_error_handler();
    }

    /**
     * Return elFinder session wrapper instance
     *
     * @return  elFinderSessionInterface
     **/
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Return true if fm init correctly
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    public function loaded()
    {
        return $this->loaded;
    }

    /**
     * Return version (api) number
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    public function version()
    {
        return self::$ApiVersion;
    }

    /**
     * Return revision (api) number
     *
     * @return string
     * @author Naoki Sawada
     **/
    public function revision()
    {
        return self::$ApiRevision;
    }

    /**
     * Add handler to elFinder command
     *
     * @param  string  command name
     * @param  string|array  callback name or array(object, method)
     * @param mixed $cmd
     * @param mixed $handler
     *
     * @return elFinder
     * @author Dmitry (dio) Levashov
     **/
    public function bind($cmd, $handler)
    {
        $allCmds = array_keys($this->commands);
        $cmds = [];
        foreach (explode(' ', $cmd) as $_cmd) {
            if ('' !== $_cmd) {
                if ($all = false !== mb_strpos($_cmd, '*')) {
                    list(, $sub) = array_pad(explode('.', $_cmd), 2, '');
                    if ($sub) {
                        $sub = str_replace('\'', '\\\'', $sub);
                        $subs = array_fill(0, count($allCmds), $sub);
                        $cmds = array_merge($cmds, array_map(['elFinder', 'addSubToBindName'], $allCmds, $subs));
                    } else {
                        $cmds = array_merge($cmds, $allCmds);
                    }
                } else {
                    $cmds[] = $_cmd;
                }
            }
        }
        $cmds = array_unique($cmds);

        foreach ($cmds as $cmd) {
            if (!isset($this->listeners[$cmd])) {
                $this->listeners[$cmd] = [];
            }

            if (is_callable($handler)) {
                $this->listeners[$cmd][] = $handler;
            }
        }

        return $this;
    }

    /**
     * Remove event (command exec) handler
     *
     * @param  string  command name
     * @param  string|array  callback name or array(object, method)
     * @param mixed $cmd
     * @param mixed $handler
     *
     * @return elFinder
     * @author Dmitry (dio) Levashov
     **/
    public function unbind($cmd, $handler)
    {
        if (!empty($this->listeners[$cmd])) {
            foreach ($this->listeners[$cmd] as $i => $h) {
                if ($h === $handler) {
                    unset($this->listeners[$cmd][$i]);

                    return $this;
                }
            }
        }

        return $this;
    }

    /**
     * Return true if command exists
     *
     * @param  string  command name
     * @param mixed $cmd
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    public function commandExists($cmd)
    {
        return $this->loaded && isset($this->commands[$cmd]) && method_exists($this, $cmd);
    }

    /**
     * Return root - file's owner (public func of volume())
     *
     * @param  string  file hash
     * @param mixed $hash
     *
     * @return elFinderVolumeDriver
     * @author Naoki Sawada
     */
    public function getVolume($hash)
    {
        return $this->volume($hash);
    }

    /**
     * Return command required arguments info
     *
     * @param  string  command name
     * @param mixed $cmd
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    public function commandArgsList($cmd)
    {
        if ($this->commandExists($cmd)) {
            $list = $this->commands[$cmd];
            $list['reqid'] = false;
        } else {
            $list = [];
        }

        return $list;
    }

    private function session_expires()
    {
        if (!$last = $this->session->get(':LAST_ACTIVITY')) {
            $this->session->set(':LAST_ACTIVITY', time());

            return false;
        }

        if (($this->timeout > 0) && (time() - $last > $this->timeout)) {
            return true;
        }

        $this->session->set(':LAST_ACTIVITY', time());

        return false;
    }

    /**
     * Exec command and return result
     *
     * @param  string $cmd  command name
     * @param  array  $args command arguments
     *
     * @throws elFinderAbortException|Exception
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    public function exec($cmd, $args)
    {
        // set error handler of WARNING, NOTICE
        set_error_handler('elFinder::phpErrorHandler', E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);

        // set current request args
        self::$currentArgs = $args;

        if (!$this->loaded) {
            return ['error' => $this->error(self::ERROR_CONF, self::ERROR_CONF_NO_VOL)];
        }

        if ($this->session_expires()) {
            return ['error' => $this->error(self::ERROR_SESSION_EXPIRES)];
        }

        if (!$this->commandExists($cmd)) {
            return ['error' => $this->error(self::ERROR_UNKNOWN_CMD)];
        }

        // check request id
        $args['reqid'] = preg_replace('[^0-9a-fA-F]', '', !empty($args['reqid']) ? $args['reqid'] : (!empty($_SERVER['HTTP_X_ELFINDERREQID']) ? $_SERVER['HTTP_X_ELFINDERREQID'] : ''));

        // to abort this request
        if ('abort' === $cmd) {
            $this->abort($args);

            return ['error' => 0];
        }

        // make flag file and set self::$abortCheckFile
        if ($args['reqid']) {
            $this->abort(['makeFile' => $args['reqid']]);
        }

        if (!empty($args['mimes']) && is_array($args['mimes'])) {
            foreach ($this->volumes as $id => $v) {
                $this->volumes[$id]->setMimesFilter($args['mimes']);
            }
        }

        // regist shutdown function as fallback
        register_shutdown_function([$this, 'itemAutoUnlock']);

        // detect destination dirHash and volume
        $dstVolume = false;
        $dst = !empty($args['target']) ? $args['target'] : (!empty($args['dst']) ? $args['dst'] : '');
        if ($dst) {
            $dstVolume = $this->volume($dst);
        } elseif (isset($args['targets']) && is_array($args['targets']) && isset($args['targets'][0])) {
            $dst = $args['targets'][0];
            $dstVolume = $this->volume($dst);
            if ($dstVolume && ($_stat = $dstVolume->file($dst)) && !empty($_stat['phash'])) {
                $dst = $_stat['phash'];
            } else {
                $dst = '';
            }
        } elseif ('open' === $cmd) {
            // for initial open without args `target`
            $dstVolume = $this->default;
            $dst = $dstVolume->defaultPath();
        }

        $result = null;

        // call pre handlers for this command
        $args['sessionCloseEarlier'] = isset($this->sessionUseCmds[$cmd]) ? false : $this->sessionCloseEarlier;
        if (!empty($this->listeners[$cmd . '.pre'])) {
            foreach ($this->listeners[$cmd . '.pre'] as $handler) {
                $_res = call_user_func_array($handler, [$cmd, &$args, $this, $dstVolume]);
                if (is_array($_res)) {
                    if (!empty($_res['preventexec'])) {
                        $result = ['error' => true];
                        if ('upload' === $cmd && !empty($args['node'])) {
                            $result['callback'] = [
                                'node' => $args['node'],
                                'bind' => $cmd,
                            ];
                        }
                        if (!empty($_res['results']) && is_array($_res['results'])) {
                            $result = array_merge($result, $_res['results']);
                        }
                        break;
                    }
                }
            }
        }

        // unlock session data for multiple access
        if ($this->sessionCloseEarlier && $args['sessionCloseEarlier']) {
            $this->session->close();
            // deprecated property
            self::$sessionClosed = true;
        }

        if ('WIN' === mb_substr(PHP_OS, 0, 3)) {
            // set time out
            self::extendTimeLimit(300);
        }

        if (!is_array($result)) {
            try {
                $result = $this->$cmd($args);
            } catch (elFinderAbortException $e) {
                throw $e;
            } catch (Exception $e) {
                $result = [
                    'error' => htmlspecialchars($e->getMessage()),
                    'sync' => true,
                ];
                if ($this->throwErrorOnExec) {
                    throw $e;
                }
            }
        }

        // check change dstDir
        $changeDst = false;
        if ($dst && $dstVolume && (!empty($result['added']) || !empty($result['removed']))) {
            $changeDst = true;
        }

        foreach ($this->volumes as $volume) {
            $removed = $volume->removed();
            if (!empty($removed)) {
                if (!isset($result['removed'])) {
                    $result['removed'] = [];
                }
                $result['removed'] = array_merge($result['removed'], $removed);
                if (!$changeDst && $dst && $dstVolume && $volume === $dstVolume) {
                    $changeDst = true;
                }
            }
            $added = $volume->added();
            if (!empty($added)) {
                if (!isset($result['added'])) {
                    $result['added'] = [];
                }
                $result['added'] = array_merge($result['added'], $added);
                if (!$changeDst && $dst && $dstVolume && $volume === $dstVolume) {
                    $changeDst = true;
                }
            }
            $volume->resetResultStat();
        }

        // dstDir is changed
        if ($changeDst) {
            if ($dstDir = $dstVolume->dir($dst)) {
                if (!isset($result['changed'])) {
                    $result['changed'] = [];
                }
                $result['changed'][] = $dstDir;
            }
        }

        // call handlers for this command
        if (!empty($this->listeners[$cmd])) {
            foreach ($this->listeners[$cmd] as $handler) {
                if (call_user_func_array($handler, [$cmd, &$result, $args, $this, $dstVolume])) {
                    // handler return true to force sync client after command completed
                    $result['sync'] = true;
                }
            }
        }

        // replace removed files info with removed files hashes
        if (!empty($result['removed'])) {
            $removed = [];
            foreach ($result['removed'] as $file) {
                $removed[] = $file['hash'];
            }
            $result['removed'] = array_unique($removed);
        }
        // remove hidden files and filter files by mimetypes
        if (!empty($result['added'])) {
            $result['added'] = $this->filter($result['added']);
        }
        // remove hidden files and filter files by mimetypes
        if (!empty($result['changed'])) {
            $result['changed'] = $this->filter($result['changed']);
        }

        if ($this->debug || !empty($args['debug'])) {
            $result['debug'] = [
                'connector' => 'php',
                'phpver' => PHP_VERSION,
                'time' => $this->utime() - $this->time,
                'memory' => (function_exists('memory_get_peak_usage') ? ceil(memory_get_peak_usage() / 1024) . 'Kb / ' : '') . ceil(memory_get_usage() / 1024) . 'Kb / ' . ini_get('memory_limit'),
                'upload' => $this->uploadDebug,
                'volumes' => [],
                'mountErrors' => $this->mountErrors,
                'phpErrors' => self::$phpErrors,
            ];
            self::$phpErrors = [];

            foreach ($this->volumes as $id => $volume) {
                $result['debug']['volumes'][] = $volume->debug();
            }
        }

        // remove sesstion var 'urlContentSaveIds'
        if ($this->removeContentSaveIds) {
            $urlContentSaveIds = $this->session->get('urlContentSaveIds', []);
            foreach (array_keys($this->removeContentSaveIds) as $contentSaveId) {
                if (isset($urlContentSaveIds[$contentSaveId])) {
                    unset($urlContentSaveIds[$contentSaveId]);
                }
            }
            if ($urlContentSaveIds) {
                $this->session->set('urlContentSaveIds', $urlContentSaveIds);
            } else {
                $this->session->remove('urlContentSaveIds');
            }
        }

        foreach ($this->volumes as $volume) {
            $volume->saveSessionCache();
            $volume->umount();
        }

        // unlock locked items
        $this->itemAutoUnlock();

        // custom data
        if (null !== $this->customData) {
            $result['customData'] = $this->customData ? json_encode($this->customData) : '';
        }

        if (!empty($result['callback'])) {
            $result['callback']['json'] = json_encode($result);
            $this->callback($result['callback']);

            return [];
        }

        return $result;
    }

    /**
     * Return file real path
     *
     * @param  string $hash file hash
     *
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    public function realpath($hash)
    {
        if (false == ($volume = $this->volume($hash))) {
            return false;
        }

        return $volume->realpath($hash);
    }

    /**
     * Sets custom data(s).
     *
     * @param  string|array $key The key or data array
     * @param  mixed        $val The value
     *
     * @return self    ( elFinder instance )
     */
    public function setCustomData($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->customData[$k] = $v;
            }
        } else {
            $this->customData[$key] = $val;
        }

        return $this;
    }

    /**
     * Removes a custom data.
     *
     * @param  string $key The key
     *
     * @return self    ( elFinder instance )
     */
    public function removeCustomData($key)
    {
        $this->customData[$key] = null;

        return $this;
    }

    /**
     * Update sesstion value of a NetVolume option
     *
     * @param string $netKey
     * @param string $optionKey
     * @param mixed  $val
     */
    public function updateNetVolumeOption($netKey, $optionKey, $val)
    {
        $netVolumes = $this->getNetVolumes();
        if (is_string($netKey) && isset($netVolumes[$netKey]) && is_string($optionKey)) {
            $netVolumes[$netKey][$optionKey] = $val;
        }
    }

    /**
     * remove of session var "urlContentSaveIds"
     *
     * @param string $id
     */
    public function removeUrlContentSaveId($id)
    {
        $this->removeContentSaveIds[$id] = true;
    }

    /**
     * Return network volumes config.
     *
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function getNetVolumes()
    {
        if ($data = $this->session->get('netvolume', [])) {
            return $data;
        }

        return [];
    }

    /**
     * Save network volumes config.
     *
     * @param  array $volumes volumes config
     *
     * @return void
     * @author Dmitry (dio) Levashov
     */
    protected function saveNetVolumes($volumes)
    {
        $this->session->set('netvolume', $volumes);
    }

    /**
     * Remove netmount volume
     *
     * @param string $key    netvolume key
     * @param object $volume volume driver instance
     *
     * @return bool
     */
    protected function removeNetVolume($key, $volume)
    {
        $netVolumes = $this->getNetVolumes();
        $res = true;
        if (is_object($volume) && method_exists($volume, 'netunmount')) {
            $res = $volume->netunmount($netVolumes, $key);
            $volume->clearSessionCache();
        }
        if ($res) {
            if (is_string($key) && isset($netVolumes[$key])) {
                unset($netVolumes[$key]);
                $this->saveNetVolumes($netVolumes);

                return true;
            }
        }

        return false;
    }

    /**
     * Get plugin instance & set to $this->plugins
     *
     * @param  string $name Plugin name (dirctory name)
     * @param  array  $opts Plugin options (optional)
     *
     * @return object | bool Plugin object instance Or false
     * @author Naoki Sawada
     */
    protected function getPluginInstance($name, $opts = [])
    {
        $key = mb_strtolower($name);
        if (!isset($this->plugins[$key])) {
            $class = 'elFinderPlugin' . $name;
            // to try auto load
            if (!class_exists($class)) {
                $p_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'plugin.php';
                if (is_file($p_file)) {
                    include_once $p_file;
                }
            }
            if (class_exists($class, false)) {
                $this->plugins[$key] = new $class($opts);
            } else {
                $this->plugins[$key] = false;
            }
        }

        return $this->plugins[$key];
    }

    /***************************************************************************/
    /*                                 commands                                */
    /***************************************************************************/

    /**
     * Normalize error messages
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    public function error()
    {
        $errors = [];

        foreach (func_get_args() as $msg) {
            if (is_array($msg)) {
                $errors = array_merge($errors, $msg);
            } else {
                $errors[] = $msg;
            }
        }

        return count($errors) ? $errors : [self::ERROR_UNKNOWN];
    }

    /**
     * @param $args
     *
     * @throws elFinderAbortException
     * @return array
     */
    protected function netmount($args)
    {
        $options = [];
        $protocol = $args['protocol'];
        $toast = '';

        if ('netunmount' === $protocol) {
            if (!empty($args['user']) && $volume = $this->volume($args['user'])) {
                if ($this->removeNetVolume($args['host'], $volume)) {
                    return ['removed' => [['hash' => $volume->root()]]];
                }
            }

            return ['sync' => true, 'error' => $this->error(self::ERROR_NETUNMOUNT)];
        }

        $driver = isset(self::$netDrivers[$protocol]) ? self::$netDrivers[$protocol] : '';
        $class = 'elFinderVolume' . $driver;

        if (!class_exists($class)) {
            return ['error' => $this->error(self::ERROR_NETMOUNT, $args['host'], self::ERROR_NETMOUNT_NO_DRIVER)];
        }

        if (!$args['path']) {
            $args['path'] = '/';
        }

        foreach ($args as $k => $v) {
            if ('options' != $k && 'protocol' != $k && $v) {
                $options[$k] = $v;
            }
        }

        if (is_array($args['options'])) {
            foreach ($args['options'] as $key => $value) {
                $options[$key] = $value;
            }
        }

        /* @var elFinderVolumeDriver $volume */
        $volume = new $class();

        // pass session handler
        $volume->setSession($this->session);

        if (is_callable([$volume, 'netmountPrepare'])) {
            $options = $volume->netmountPrepare($options);
            if (isset($options['exit'])) {
                if ('callback' === $options['exit']) {
                    $this->callback($options['out']);
                }

                return $options;
            }
            if (!empty($options['toast'])) {
                $toast = $options['toast'];
                unset($options['toast']);
            }
        }

        $netVolumes = $this->getNetVolumes();

        if (!isset($options['id'])) {
            // given fixed unique id
            if (!$options['id'] = $this->getNetVolumeUniqueId($netVolumes)) {
                return ['error' => $this->error(self::ERROR_NETMOUNT, $args['host'], 'Could\'t given volume id.')];
            }
        }

        // load additional volume root options
        if (!empty($this->optionsNetVolumes['*'])) {
            $options = array_merge($this->optionsNetVolumes['*'], $options);
        }
        if (!empty($this->optionsNetVolumes[$protocol])) {
            $options = array_merge($this->optionsNetVolumes[$protocol], $options);
        }

        if (!$key = $volume->netMountKey) {
            $key = md5($protocol . '-' . serialize($options));
        }
        $options['netkey'] = $key;

        if (!isset($netVolumes[$key]) && $volume->mount($options)) {
            // call post-process function of netmount
            if (is_callable([$volume, 'postNetmount'])) {
                $volume->postNetmount($options);
            }
            $options['driver'] = $driver;
            $netVolumes[$key] = $options;
            $this->saveNetVolumes($netVolumes);
            $rootstat = $volume->file($volume->root());
            $res = ['added' => [$rootstat]];
            if ($toast) {
                $res['toast'] = $toast;
            }

            return $res;
        }
        $this->removeNetVolume(null, $volume);

        return ['error' => $this->error(self::ERROR_NETMOUNT, $args['host'], implode(' ', $volume->error()))];
    }

    /**
     * "Open" directory
     * Return array with following elements
     *  - cwd          - opened dir info
     *  - files        - opened dir content [and dirs tree if $args[tree]]
     *  - api          - api version (if $args[init])
     *  - uplMaxSize   - if $args[init]
     *  - error        - on failed
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function open($args)
    {
        $target = $args['target'];
        $init = !empty($args['init']);
        $tree = !empty($args['tree']);
        $volume = $this->volume($target);
        $cwd = $volume ? $volume->dir($target) : false;
        $hash = $init ? 'default folder' : '#' . $target;
        $compare = '';

        // on init request we can get invalid dir hash -
        // dir which can not be opened now, but remembered by client,
        // so open default dir
        if ((!$cwd || !$cwd['read']) && $init) {
            $volume = $this->default;
            $target = $volume->defaultPath();
            $cwd = $volume->dir($target);
        }

        if (!$cwd) {
            return ['error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_DIR_NOT_FOUND)];
        }
        if (!$cwd['read']) {
            return ['error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_PERM_DENIED)];
        }

        $files = [];

        // get current working directory files list
        if (false === ($ls = $volume->scandir($cwd['hash']))) {
            return ['error' => $this->error(self::ERROR_OPEN, $cwd['name'], $volume->error())];
        }

        if (isset($cwd['dirs']) && 1 != $cwd['dirs']) {
            $cwd = $volume->dir($target);
        }

        // get other volume root
        if ($tree) {
            foreach ($this->volumes as $id => $v) {
                $files[] = $v->file($v->root());
            }
        }

        // long polling mode
        if ($args['compare']) {
            $sleep = max(1, (int)$volume->getOption('lsPlSleep'));
            $standby = (int)$volume->getOption('plStandby');
            if ($standby > 0 && $sleep > $standby) {
                $standby = $sleep;
            }
            $limit = max(0, floor($standby / $sleep)) + 1;
            do {
                self::extendTimeLimit(30 + $sleep);
                $_mtime = 0;
                foreach ($ls as $_f) {
                    $_mtime = max($_mtime, $_f['ts']);
                }
                $compare = strval(count($ls)) . ':' . strval($_mtime);
                if ($compare !== $args['compare']) {
                    break;
                }
                if (--$limit) {
                    sleep($sleep);
                    $volume->clearstatcache();
                    if (false === ($ls = $volume->scandir($cwd['hash']))) {
                        break;
                    }
                }
            } while ($limit);
            if (false === $ls) {
                return ['error' => $this->error(self::ERROR_OPEN, $cwd['name'], $volume->error())];
            }
        }

        if ($ls) {
            if ($files) {
                $files = array_merge($files, $ls);
            } else {
                $files = $ls;
            }
        }

        $result = [
            'cwd' => $cwd,
            'options' => $volume->options($cwd['hash']),
            'files' => $files,
        ];

        if ($compare) {
            $result['cwd']['compare'] = $compare;
        }

        if (!empty($args['init'])) {
            $result['api'] = sprintf('%.1F%03d', self::$ApiVersion, self::$ApiRevision);
            $result['uplMaxSize'] = ini_get('upload_max_filesize');
            $result['uplMaxFile'] = ini_get('max_file_uploads');
            $result['netDrivers'] = array_keys(self::$netDrivers);
            $result['maxTargets'] = $this->maxTargets;
            if ($volume) {
                $result['cwd']['root'] = $volume->root();
            }
            if (self::$textMimes) {
                $result['textMimes'] = self::$textMimes;
            }
        }

        return $result;
    }

    /**
     * Return dir files names list
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function ls($args)
    {
        $target = $args['target'];
        $intersect = isset($args['intersect']) ? $args['intersect'] : [];

        if (false == ($volume = $this->volume($target))
            || false === ($list = $volume->ls($target, $intersect))) {
            return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
        }

        return ['list' => $list];
    }

    /**
     * Return subdirs for required directory
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function tree($args)
    {
        $target = $args['target'];

        if (false == ($volume = $this->volume($target))
            || false == ($tree = $volume->tree($target))) {
            return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
        }

        return ['tree' => $tree];
    }

    /**
     * Return parents dir for required directory
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function parents($args)
    {
        $target = $args['target'];
        $until = $args['until'];

        if (false == ($volume = $this->volume($target))
            || false == ($tree = $volume->parents($target, false, $until))) {
            return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
        }

        return ['tree' => $tree];
    }

    /**
     * Return new created thumbnails list
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws ImagickException
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function tmb($args)
    {
        $result = ['images' => []];
        $targets = $args['targets'];

        foreach ($targets as $target) {
            self::checkAborted();

            if (false != ($volume = $this->volume($target))
                && (false != ($tmb = $volume->tmb($target)))) {
                $result['images'][$target] = $tmb;
            }
        }

        return $result;
    }

    /**
     * Download files/folders as an archive file
     * 1st: Return srrsy contains download archive file info
     * 2nd: Return array contains opened file pointer, root itself and required headers
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws Exception
     * @return array
     * @author Naoki Sawada
     */
    protected function zipdl($args)
    {
        $targets = $args['targets'];
        $download = !empty($args['download']);
        $h404 = 'HTTP/1.x 404 Not Found';

        if (!$download) {
            //1st: Return array contains download archive file info
            $error = [self::ERROR_ARCHIVE];
            if (false !== ($volume = $this->volume($targets[0]))) {
                if ($dlres = $volume->zipdl($targets)) {
                    $path = $dlres['path'];
                    register_shutdown_function(['elFinder', 'rmFileInDisconnected'], $path);
                    if (1 === count($targets)) {
                        $name = basename($volume->path($targets[0]));
                    } else {
                        $name = $dlres['prefix'] . '_Files';
                    }
                    $name .= '.' . $dlres['ext'];
                    $uniqid = uniqid();
                    $this->session->set('zipdl' . $uniqid, basename($path));
                    $result = [
                        'zipdl' => [
                            'file' => $uniqid,
                            'name' => $name,
                            'mime' => $dlres['mime'],
                        ],
                    ];

                    return $result;
                }
                $error = array_merge($error, $volume->error());
            }

            return ['error' => $error];
        }
        // 2nd: Return array contains opened file session key, root itself and required headers
        if (4 !== count($targets) || false == ($volume = $this->volume($targets[0])) || !($file = $this->session->get('zipdl' . $targets[1]))) {
            return ['error' => 'File not found', 'header' => $h404, 'raw' => true];
        }
        $this->session->remove('zipdl' . $targets[1]);
        if ($volume->commandDisabled('zipdl')) {
            return ['error' => 'File not found', 'header' => $h404, 'raw' => true];
        }
        $path = $volume->getTempPath() . DIRECTORY_SEPARATOR . basename($file);
        if (!is_readable($path) || !is_writable($path)) {
            return ['error' => 'File not found', 'header' => $h404, 'raw' => true];
        }
        // register auto delete on shutdown
        $GLOBALS['elFinderTempFiles'][$path] = true;
        // for HTTP headers
        $name = $targets[2];
        $mime = $targets[3];

        $filenameEncoded = rawurlencode($name);
        if (false === mb_strpos($filenameEncoded, '%')) { // ASCII only
            $filename = 'filename="' . $name . '"';
        } else {
            $ua = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/MSIE [4-8]/', $ua)) { // IE < 9 do not support RFC 6266 (RFC 2231/RFC 5987)
                $filename = 'filename="' . $filenameEncoded . '"';
            } elseif (false === mb_strpos($ua, 'Chrome') && false !== mb_strpos($ua, 'Safari') && preg_match('#Version/[3-5]#', $ua)) { // Safari < 6
                $filename = 'filename="' . str_replace('"', '', $name) . '"';
            } else { // RFC 6266 (RFC 2231/RFC 5987)
                $filename = 'filename*=UTF-8\'\'' . $filenameEncoded;
            }
        }

        $fp = fopen($path, 'rb');
        $file = fstat($fp);
        $result = [
                'pointer' => $fp,
                'header' => [
                    'Content-Type: ' . $mime,
                    'Content-Disposition: attachment; ' . $filename,
                    'Content-Transfer-Encoding: binary',
                    'Content-Length: ' . $file['size'],
                    'Accept-Ranges: none',
                    'Connection: close',
                ],
            ];

        return $result;
    }

    /**
     * Required to output file in browser when volume URL is not set
     * Return array contains opened file pointer, root itself and required headers
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function file($args)
    {
        $target = $args['target'];
        $download = !empty($args['download']);
        $onetime = !empty($args['onetime']);
        //$h304     = 'HTTP/1.1 304 Not Modified';
        //$h403     = 'HTTP/1.0 403 Access Denied';
        $h404 = 'HTTP/1.0 404 Not Found';
        $a404 = ['error' => 'File not found', 'header' => $h404, 'raw' => true];

        if ($onetime) {
            $volume = null;
            $tmpdir = self::$commonTempPath;
            if (!$tmpdir || !is_file($tmpf = $tmpdir . DIRECTORY_SEPARATOR . 'ELF' . $target)) {
                return $a404;
            }
            $GLOBALS['elFinderTempFiles'][$tmpf] = true;
            if ($file = json_decode(file_get_contents($tmpf), true)) {
                $src = base64_decode($file['file']);
                if (!is_file($src) || !($fp = fopen($src, 'rb'))) {
                    return $a404;
                }
                if (0 === mb_strpos($src, $tmpdir)) {
                    $GLOBALS['elFinderTempFiles'][$src] = true;
                }
                unset($file['file']);
                $file['read'] = true;
                $file['size'] = filesize($src);
            } else {
                return $a404;
            }
        } else {
            if (false == ($volume = $this->volume($target))) {
                return $a404;
            }

            if (false == ($file = $volume->file($target))) {
                return $a404;
            }

            if (!$file['read']) {
                return $a404;
            }

            if (false == ($fp = $volume->open($target))) {
                return $a404;
            }
        }

        // check aborted by user
        self::checkAborted();

        // allow change MIME type by 'file.pre' callback functions
        $mime = isset($args['mime']) ? $args['mime'] : $file['mime'];
        if ($download || $onetime) {
            $disp = 'attachment';
        } else {
            $dispInlineRegex = $volume->getOption('dispInlineRegex');
            $inlineRegex = false;
            if ($dispInlineRegex) {
                $inlineRegex = '#' . str_replace('#', '\\#', $dispInlineRegex) . '#';

                try {
                    preg_match($inlineRegex, '');
                } catch (Exception $e) {
                    $inlineRegex = false;
                }
            }
            if (!$inlineRegex) {
                $inlineRegex = '#^(?:(?:image|text)|application/x-shockwave-flash$)#';
            }
            $disp = preg_match($inlineRegex, $mime) ? 'inline' : 'attachment';
        }

        $filenameEncoded = rawurlencode($file['name']);
        if (false === mb_strpos($filenameEncoded, '%')) { // ASCII only
            $filename = 'filename="' . $file['name'] . '"';
        } else {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            if (preg_match('/MSIE [4-8]/', $ua)) { // IE < 9 do not support RFC 6266 (RFC 2231/RFC 5987)
                $filename = 'filename="' . $filenameEncoded . '"';
            } elseif (false === mb_strpos($ua, 'Chrome') && false !== mb_strpos($ua, 'Safari') && preg_match('#Version/[3-5]#', $ua)) { // Safari < 6
                $filename = 'filename="' . str_replace('"', '', $file['name']) . '"';
            } else { // RFC 6266 (RFC 2231/RFC 5987)
                $filename = 'filename*=UTF-8\'\'' . $filenameEncoded;
            }
        }

        if ($args['cpath'] && $args['reqid']) {
            setcookie('elfdl' . $args['reqid'], '1', 0, $args['cpath']);
        }

        $result = [
            'volume' => $volume,
            'pointer' => $fp,
            'info' => $file,
            'header' => [
                'Content-Type: ' . $mime,
                'Content-Disposition: ' . $disp . '; ' . $filename,
                'Content-Transfer-Encoding: binary',
                'Content-Length: ' . $file['size'],
                'Last-Modified: ' . gmdate('D, d M Y H:i:s T', $file['ts']),
                'Connection: close',
            ],
        ];

        if (!$onetime) {
            // add cache control headers
            if ($cacheHeaders = $volume->getOption('cacheHeaders')) {
                $result['header'] = array_merge($result['header'], $cacheHeaders);
            }

            // check 'xsendfile'
            $xsendfile = $volume->getOption('xsendfile');
            $path = null;
            if ($xsendfile) {
                $info = stream_get_meta_data($fp);
                if ($path = empty($info['uri']) ? null : $info['uri']) {
                    $basePath = rtrim($volume->getOption('xsendfilePath'), DIRECTORY_SEPARATOR);
                    if ($basePath) {
                        $root = rtrim($volume->getRootPath(), DIRECTORY_SEPARATOR);
                        if (0 === mb_strpos($path, $root)) {
                            $path = $basePath . mb_substr($path, mb_strlen($root));
                        } else {
                            $path = null;
                        }
                    }
                }
            }
            if ($path) {
                $result['header'][] = $xsendfile . ': ' . $path;
                $result['info']['xsendfile'] = $xsendfile;
            }
        }

        // add "Content-Location" if file has url data
        if (isset($file['url']) && $file['url'] && 1 != $file['url']) {
            $result['header'][] = 'Content-Location: ' . $file['url'];
        }

        return $result;
    }

    /**
     * Count total files size
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function size($args)
    {
        $size = 0;
        $files = 0;
        $dirs = 0;
        $itemCount = true;
        $sizes = [];

        foreach ($args['targets'] as $target) {
            self::checkAborted();
            if (false == ($volume = $this->volume($target))
                || false == ($file = $volume->file($target))
                || !$file['read']) {
                return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
            }

            $volRes = $volume->size($target);
            if (is_array($volRes)) {
                $sizeInfo = ['size' => 0, 'fileCnt' => 0, 'dirCnt' => 0];
                if (!empty($volRes['size'])) {
                    $sizeInfo['size'] = $volRes['size'];
                    $size += $volRes['size'];
                }
                if (!empty($volRes['files'])) {
                    $sizeInfo['fileCnt'] = $volRes['files'];
                }
                if (!empty($volRes['dirs'])) {
                    $sizeInfo['dirCnt'] = $volRes['dirs'];
                }
                if ($itemCount) {
                    $files += $sizeInfo['fileCnt'];
                    $dirs += $sizeInfo['dirCnt'];
                }
                $sizes[$target] = $sizeInfo;
            } elseif (is_numeric($volRes)) {
                $size += $volRes;
                $files = $dirs = 'unknown';
                $itemCount = false;
            }
        }

        return ['size' => $size, 'fileCnt' => $files, 'dirCnt' => $dirs, 'sizes' => $sizes];
    }

    /**
     * Create directory
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function mkdir($args)
    {
        $target = $args['target'];
        $name = $args['name'];
        $dirs = $args['dirs'];
        if ('' === $name && !$dirs) {
            return ['error' => $this->error(self::ERROR_INV_PARAMS, 'mkdir')];
        }

        if (false == ($volume = $this->volume($target))) {
            return ['error' => $this->error(self::ERROR_MKDIR, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)];
        }
        if ($dirs) {
            sort($dirs);
            $reset = null;
            $mkdirs = [];
            foreach ($dirs as $dir) {
                $tgt = &$mkdirs;
                $_names = explode('/', trim($dir, '/'));
                foreach ($_names as $_key => $_name) {
                    if (!isset($tgt[$_name])) {
                        $tgt[$_name] = [];
                    }
                    $tgt = &$tgt[$_name];
                }
                $tgt = &$reset;
            }
            $res = $this->ensureDirsRecursively($volume, $target, $mkdirs);
            $ret = [
                'added' => $res['stats'],
                'hashes' => $res['hashes'],
            ];
            if ($res['error']) {
                $ret['warning'] = $this->error(self::ERROR_MKDIR, $res['error'][0], $volume->error());
            }

            return $ret;
        }

        return false == ($dir = $volume->mkdir($target, $name))
                ? ['error' => $this->error(self::ERROR_MKDIR, $name, $volume->error())]
                : ['added' => [$dir]];
    }

    /**
     * Create empty file
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function mkfile($args)
    {
        $target = $args['target'];
        $name = $args['name'];

        if (false == ($volume = $this->volume($target))) {
            return ['error' => $this->error(self::ERROR_MKFILE, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)];
        }

        return false == ($file = $volume->mkfile($target, $args['name']))
            ? ['error' => $this->error(self::ERROR_MKFILE, $name, $volume->error())]
            : ['added' => [$file]];
    }

    /**
     * Rename file, Accept multiple items >= API 2.1031
     *
     * @param  array $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     * @author Naoki Sawada
     */
    protected function rename($args)
    {
        $target = $args['target'];
        $name = $args['name'];
        $query = (false !== mb_strpos($args['q'], '*')) ? $args['q'] : '';
        $targets = $args['targets'];
        $rms = [];
        $notfounds = [];
        $locked = [];
        $errs = [];
        $files = [];
        $removed = [];
        $res = [];
        $type = 'normal';

        if (!($volume = $this->volume($target))) {
            return ['error' => $this->error(self::ERROR_RENAME, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }

        if ($targets) {
            array_unshift($targets, $target);
            foreach ($targets as $h) {
                if ($rm = $volume->file($h)) {
                    if ($this->itemLocked($h)) {
                        $locked[] = $rm['name'];
                    } else {
                        $rm['realpath'] = $volume->realpath($h);
                        $rms[] = $rm;
                    }
                } else {
                    $notfounds[] = '#' . $h;
                }
            }
            if (!$rms) {
                $res['error'] = [];
                if ($notfounds) {
                    $res['error'] = [self::ERROR_RENAME, implode(', ', $notfounds), self::ERROR_FILE_NOT_FOUND];
                }
                if ($locked) {
                    array_push($res['error'], self::ERROR_LOCKED, implode(', ', $locked));
                }

                return $res;
            }

            $res['warning'] = [];
            if ($notfounds) {
                array_push($res['warning'], self::ERROR_RENAME, implode(', ', $notfounds), self::ERROR_FILE_NOT_FOUND);
            }
            if ($locked) {
                array_push($res['warning'], self::ERROR_LOCKED, implode(', ', $locked));
            }

            if ($query) {
                // batch rename
                $splits = self::splitFileExtention($query);
                if ($splits[1] && '*' === $splits[0]) {
                    $type = 'extention';
                    $name = $splits[1];
                } elseif (mb_strlen($splits[0]) > 1) {
                    if ('*' === mb_substr($splits[0], -1)) {
                        $type = 'prefix';
                        $name = mb_substr($splits[0], 0, mb_strlen($splits[0]) - 1);
                    } elseif ('*' === mb_substr($splits[0], 0, 1)) {
                        $type = 'suffix';
                        $name = mb_substr($splits[0], 1);
                    }
                }
                if ('normal' !== $type) {
                    if (!empty($this->listeners['rename.pre'])) {
                        $_args = ['name' => $name];
                        foreach ($this->listeners['rename.pre'] as $handler) {
                            $_res = call_user_func_array($handler, ['rename', &$_args, $this, $volume]);
                            if (!empty($_res['preventexec'])) {
                                break;
                            }
                        }
                        $name = $_args['name'];
                    }
                }
            }
            foreach ($rms as $rm) {
                if ('normal' === $type) {
                    $rname = $volume->uniqueName($volume->realpath($rm['phash']), $name, '', false);
                } else {
                    $rname = $name;
                    if ('extention' === $type) {
                        $splits = self::splitFileExtention($rm['name']);
                        $rname = $splits[0] . '.' . $name;
                    } elseif ('prefix' === $type) {
                        $rname = $name . $rm['name'];
                    } elseif ('suffix' === $type) {
                        $splits = self::splitFileExtention($rm['name']);
                        $rname = $splits[0] . $name . ($splits[1] ? ('.' . $splits[1]) : '');
                    }
                    $rname = $volume->uniqueName($volume->realpath($rm['phash']), $rname, '', true);
                }
                if ($file = $volume->rename($rm['hash'], $rname)) {
                    $files[] = $file;
                    $removed[] = $rm;
                } else {
                    $errs[] = $rm['name'];
                }
            }

            if (!$files) {
                $res['error'] = $this->error(self::ERROR_RENAME, implode(', ', $errs), $volume->error());
                if (!$res['warning']) {
                    unset($res['warning']);
                }

                return $res;
            }
            if ($errs) {
                array_push($res['warning'], self::ERROR_RENAME, implode(', ', $errs), $volume->error());
            }
            if (!$res['warning']) {
                unset($res['warning']);
            }
            $res['added'] = $files;
            $res['removed'] = $removed;

            return $res;
        }
        if (!($rm = $volume->file($target))) {
            return ['error' => $this->error(self::ERROR_RENAME, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }
        if ($this->itemLocked($target)) {
            return ['error' => $this->error(self::ERROR_LOCKED, $rm['name'])];
        }
        $rm['realpath'] = $volume->realpath($target);

        return false == ($file = $volume->rename($target, $name))
                ? ['error' => $this->error(self::ERROR_RENAME, $rm['name'], $volume->error())]
                : ['added' => [$file], 'removed' => [$rm]];
    }

    /**
     * Duplicate file - create copy with "copy %d" suffix
     *
     * @param array $args command arguments
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function duplicate($args)
    {
        $targets = is_array($args['targets']) ? $args['targets'] : [];
        $result = [];
        $suffix = empty($args['suffix']) ? 'copy' : $args['suffix'];

        $this->itemLock($targets);

        foreach ($targets as $target) {
            self::checkAborted();

            if (false == ($volume = $this->volume($target))
                || false == ($src = $volume->file($target))) {
                $result['warning'] = $this->error(self::ERROR_COPY, '#' . $target, self::ERROR_FILE_NOT_FOUND);
                break;
            }

            if (false == ($file = $volume->duplicate($target, $suffix))) {
                $result['warning'] = $this->error($volume->error());
                break;
            }
        }

        return $result;
    }

    /**
     * Remove dirs/files
     *
     * @param array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function rm($args)
    {
        $targets = is_array($args['targets']) ? $args['targets'] : [];
        $result = ['removed' => []];

        foreach ($targets as $target) {
            self::checkAborted();

            if (false == ($volume = $this->volume($target))) {
                $result['warning'] = $this->error(self::ERROR_RM, '#' . $target, self::ERROR_FILE_NOT_FOUND);
                break;
            }

            if ($this->itemLocked($target)) {
                $rm = $volume->file($target);
                $result['warning'] = $this->error(self::ERROR_LOCKED, $rm['name']);
                break;
            }

            if (!$volume->rm($target)) {
                $result['warning'] = $this->error($volume->error());
                break;
            }
        }

        return $result;
    }

    /**
     * Return has subdirs
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @return array
     * @author Dmitry Naoki Sawada
     **/
    protected function subdirs($args)
    {
        $result = ['subdirs' => []];
        $targets = $args['targets'];

        foreach ($targets as $target) {
            if (false !== ($volume = $this->volume($target))) {
                $result['subdirs'][$target] = $volume->subdirs($target) ? 1 : 0;
            }
        }

        return $result;
    }

    /**
     * Gateway for custom contents editor
     *
     * @param  array $args command arguments
     *
     * @return array
     * @author Naoki Sawada
     */
    protected function editor($args = [])
    {
        /* @var elFinderEditor $editor */
        $name = $args['name'];
        if (is_array($name)) {
            $res = [];
            foreach ($name as $c) {
                $class = 'elFinderEditor' . $c;
                if (class_exists($class)) {
                    $editor = new $class($this, $args['args']);
                    $res[$c] = $editor->enabled();
                } else {
                    $res[$c] = 0;
                }
            }

            return $res;
        }
        $class = 'elFinderEditor' . $name;
        $method = '';
        if (class_exists($class)) {
            $editor = new $class($this, $args['args']);
            $method = $args['method'];
            if ($editor->isAllowedMethod($method) && method_exists($editor, $method)) {
                return $editor->$method();
            }
        }

        return ['error', $this->error(self::ERROR_UNKNOWN_CMD, 'editor.' . $name . '.' . $method)];
    }

    /**
     * Abort current request and make flag file to running check
     *
     * @param array $args
     *
     * @return void
     */
    protected function abort($args = [])
    {
        if (!self::$connectionFlagsPath || 'HEAD' === $_SERVER['REQUEST_METHOD']) {
            return;
        }
        $flagFile = self::$connectionFlagsPath . DIRECTORY_SEPARATOR . 'elfreq%s';
        if (!empty($args['makeFile'])) {
            self::$abortCheckFile = sprintf($flagFile, $args['makeFile']);
            touch(self::$abortCheckFile);
            $GLOBALS['elFinderTempFiles'][self::$abortCheckFile] = true;

            return;
        }

        $file = !empty($args['id']) ? sprintf($flagFile, $args['id']) : self::$abortCheckFile;
        $file && is_file($file) && unlink($file);
    }

    /**
     * Get remote contents
     *
     * @param  string   $url          target url
     * @param  int      $timeout      timeout (sec)
     * @param  int      $redirect_max redirect max count
     * @param  string   $ua
     * @param  resource $fp
     *
     * @return string, resource or bool(false)
     * @retval  string contents
     * @retval  resource conttents
     * @rettval false  error
     * @author  Naoki Sawada
     **/
    protected function get_remote_contents(&$url, $timeout = 30, $redirect_max = 5, $ua = 'Mozilla/5.0', $fp = null)
    {
        if (preg_match('~^(?:ht|f)tps?://[-_.!\~*\'()a-z0-9;/?:\@&=+\$,%#\*\[\]]+~i', $url)) {
            $info = parse_url($url);
            $host = trim(mb_strtolower($info['host']), '.');
            // do not support IPv6 address
            if (preg_match('/^\[.*\]$/', $host)) {
                return false;
            }
            // do not support non dot host
            if (false === mb_strpos($host, '.')) {
                return false;
            }
            // do not support URL-encoded host
            if (false !== mb_strpos($host, '%')) {
                return false;
            }
            // disallow including "localhost" and "localdomain"
            if (preg_match('/\b(?:localhost|localdomain)\b/', $host)) {
                return false;
            }
            // wildcard DNS (e.g xip.io)
            if (preg_match('/0x[0-9a-f]+|[0-9]+(?:\.(?:0x[0-9a-f]+|[0-9]+)){1,3}/', $host)) {
                $host = gethostbyname($host);
            }
            // check IPv4 local loopback, private network and link local
            if (preg_match('/^0x[0-9a-f]+|[0-9]+(?:\.(?:0x[0-9a-f]+|[0-9]+)){1,3}$/', $host, $m)) {
                $long = (int)sprintf('%u', ip2long($host));
                if (!$long) {
                    return false;
                }
                $local = (int)sprintf('%u', ip2long('127.255.255.255')) >> 24;
                $prv1 = (int)sprintf('%u', ip2long('10.255.255.255')) >> 24;
                $prv2 = (int)sprintf('%u', ip2long('172.31.255.255')) >> 20;
                $prv3 = (int)sprintf('%u', ip2long('192.168.255.255')) >> 16;
                $link = (int)sprintf('%u', ip2long('169.254.255.255')) >> 16;

                if ($long >> 24 === $local || $long >> 24 === $prv1 || $long >> 20 === $prv2 || $long >> 16 === $prv3 || $long >> 16 === $link) {
                    return false;
                }
            }
            // dose not support 'user' and 'pass' for security reasons
            $url = $info['scheme'] . '://' . $host . (!empty($info['port']) ? (':' . $info['port']) : '') . $info['path'] . (!empty($info['query']) ? ('?' . $info['query']) : '') . (!empty($info['fragment']) ? ('#' . $info['fragment']) : '');
            // check by URL upload filter
            if ($this->urlUploadFilter && is_callable($this->urlUploadFilter)) {
                if (!call_user_func_array($this->urlUploadFilter, [$url, $this])) {
                    return false;
                }
            }
            $method = (function_exists('curl_exec') && !ini_get('safe_mode') && !ini_get('open_basedir')) ? 'curl_get_contents' : 'fsock_get_contents';

            return $this->$method($url, $timeout, $redirect_max, $ua, $fp);
        }

        return false;
    }

    /**
     * Get remote contents with cURL
     *
     * @param  string   $url          target url
     * @param  int      $timeout      timeout (sec)
     * @param  int      $redirect_max redirect max count
     * @param  string   $ua
     * @param  resource $outfp
     *
     * @return string, resource or bool(false)
     * @retval string contents
     * @retval resource conttents
     * @retval false  error
     * @author Naoki Sawada
     **/
    protected function curl_get_contents(&$url, $timeout, $redirect_max, $ua, $outfp)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($outfp) {
            curl_setopt($ch, CURLOPT_FILE, $outfp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $redirect_max);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        $result = curl_exec($ch);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $outfp ? $outfp : $result;
    }

    /**
     * Get remote contents with fsockopen()
     *
     * @param  string   $url          url
     * @param  int      $timeout      timeout (sec)
     * @param  int      $redirect_max redirect max count
     * @param  string   $ua
     * @param  resource $outfp
     *
     * @throws elFinderAbortException
     * @return string, resource or bool(false)
     * @retval string contents
     * @retval resource conttents
     * @retval false  error
     * @author Naoki Sawada
     */
    protected function fsock_get_contents(&$url, $timeout, $redirect_max, $ua, $outfp)
    {
        $connect_timeout = 3;
        $connect_try = 3;
        $method = 'GET';
        $readsize = 4096;
        $ssl = '';

        $getSize = null;
        $headers = '';

        $arr = parse_url($url);
        if (!$arr) {
            // Bad request
            return false;
        }
        if ('https' === $arr['scheme']) {
            $ssl = 'ssl://';
        }

        // query
        $arr['query'] = isset($arr['query']) ? '?' . $arr['query'] : '';
        // port
        $port = isset($arr['port']) ? $arr['port'] : '';
        $arr['port'] = $port ? $port : ($ssl ? 443 : 80);

        $url_base = $arr['scheme'] . '://' . $arr['host'] . ($port ? (':' . $port) : '');
        $url_path = isset($arr['path']) ? $arr['path'] : '/';
        $uri = $url_path . $arr['query'];

        $query = $method . ' ' . $uri . " HTTP/1.0\r\n";
        $query .= 'Host: ' . $arr['host'] . "\r\n";
        $query .= "Accept: */*\r\n";
        $query .= "Connection: close\r\n";
        if (!empty($ua)) {
            $query .= 'User-Agent: ' . $ua . "\r\n";
        }
        if (null !== $getSize) {
            $query .= 'Range: bytes=0-' . ($getSize - 1) . "\r\n";
        }

        $query .= $headers;

        $query .= "\r\n";

        $fp = $connect_try_count = 0;
        while (!$fp && $connect_try_count < $connect_try) {
            $errno = 0;
            $errstr = '';
            $fp = fsockopen(
                $ssl . $arr['host'],
                $arr['port'],
                $errno,
                $errstr,
                $connect_timeout
            );
            if ($fp) {
                break;
            }
            $connect_try_count++;
            if (connection_aborted()) {
                throw new elFinderAbortException();
            }
            sleep(1); // wait 1sec
        }

        if (!$fp) {
            return false;
        }

        $fwrite = 0;
        for ($written = 0; $written < mb_strlen($query); $written += $fwrite) {
            $fwrite = fwrite($fp, mb_substr($query, $written));
            if (!$fwrite) {
                break;
            }
        }

        if ($timeout) {
            socket_set_timeout($fp, $timeout);
        }

        $_response = '';
        $header = '';
        while ("\r\n" !== $_response) {
            $_response = fgets($fp, $readsize);
            $header .= $_response;
        }

        $rccd = array_pad(explode(' ', $header, 2), 2, ''); // array('HTTP/1.1','200')
        $rc = (int)$rccd[1];

        $ret = false;
        // Redirect
        switch ($rc) {
            case 307: // Temporary Redirect
            case 303: // See Other
            case 302: // Moved Temporarily
            case 301: // Moved Permanently
                $matches = [];
                if (preg_match('/^Location: (.+?)(#.+)?$/im', $header, $matches) && --$redirect_max > 0) {
                    $_url = $url;
                    $url = trim($matches[1]);
                    if (!preg_match('/^https?:\//', $url)) { // no scheme
                        if ('/' != $url[0]) { // Relative path
                            // to Absolute path
                            $url = mb_substr($url_path, 0, mb_strrpos($url_path, '/')) . '/' . $url;
                        }
                        // add sheme,host
                        $url = $url_base . $url;
                    }
                    if ($_url === $url) {
                        sleep(1);
                    }
                    fclose($fp);

                    return $this->fsock_get_contents($url, $timeout, $redirect_max, $ua, $outfp);
                }
                break;
            case 200:
                $ret = true;
        }
        if (!$ret) {
            fclose($fp);

            return false;
        }

        $body = '';
        if (!$outfp) {
            $outfp = fopen('php://temp', 'rwb');
            $body = true;
        }
        while (fwrite($outfp, fread($fp, $readsize))) {
            if ($timeout) {
                $_status = socket_get_status($fp);
                if ($_status['timed_out']) {
                    fclose($outfp);
                    fclose($fp);

                    return false; // Request Time-out
                }
            }
        }
        if ($body) {
            rewind($outfp);
            $body = stream_get_contents($outfp);
            fclose($outfp);
            $outfp = null;
        }

        fclose($fp);

        return $outfp ? $outfp : $body; // Data
    }

    /**
     * Parse Data URI scheme
     *
     * @param  string $str
     * @param  array  $extTable
     * @param  array  $args
     *
     * @return array
     * @author Naoki Sawada
     */
    protected function parse_data_scheme($str, $extTable, $args = null)
    {
        $data = $name = $mime = '';
        // Scheme 'data://' require `allow_url_fopen` and `allow_url_include`
        if ($fp = fopen('data://' . mb_substr($str, 5), 'rb')) {
            if ($data = stream_get_contents($fp)) {
                $meta = stream_get_meta_data($fp);
                $mime = $meta['mediatype'];
            }
            fclose($fp);
        } elseif (preg_match('~^data:(.+?/.+?)?(?:;charset=.+?)?;base64,~', mb_substr($str, 0, 128), $m)) {
            $data = base64_decode(mb_substr($str, mb_strlen($m[0])));
            if ($m[1]) {
                $mime = $m[1];
            }
        }
        if ($data) {
            $ext = ($mime && isset($extTable[$mime])) ? '.' . $extTable[$mime] : '';
            // Set name if name eq 'image.png' and $args has 'name' array, e.g. clipboard data
            if (is_array($args['name']) && isset($args['name'][0])) {
                $name = $args['name'][0];
                if ($ext) {
                    $name = preg_replace('/\.[^.]*$/', '', $name);
                }
            } else {
                $name = mb_substr(md5($data), 0, 8);
            }
            $name .= $ext;
        } else {
            $data = $name = '';
        }

        return [$data, $name];
    }

    /**
     * Detect file MIME Type by local path
     *
     * @param  string $path Local path
     *
     * @return string file MIME Type
     * @author Naoki Sawada
     */
    protected function detectMimeType($path)
    {
        static $type, $finfo;
        if (!$type) {
            if (class_exists('finfo', false)) {
                $tmpFileInfo = explode(';', finfo_file(finfo_open(FILEINFO_MIME), __FILE__));
            } else {
                $tmpFileInfo = false;
            }
            $regexp = '/text\/x\-(php|c\+\+)/';
            if ($tmpFileInfo && preg_match($regexp, array_shift($tmpFileInfo))) {
                $type = 'finfo';
                $finfo = finfo_open(FILEINFO_MIME);
            } elseif (function_exists('mime_content_type')
                && preg_match($regexp, array_shift(explode(';', mime_content_type(__FILE__))))) {
                $type = 'mime_content_type';
            } elseif (function_exists('getimagesize')) {
                $type = 'getimagesize';
            } else {
                $type = 'none';
            }
        }

        $mime = '';
        if ('finfo' === $type) {
            $mime = finfo_file($finfo, $path);
        } elseif ('mime_content_type' === $type) {
            $mime = mime_content_type($path);
        } elseif ('getimagesize' === $type) {
            if ($img = getimagesize($path)) {
                $mime = $img['mime'];
            }
        }

        if ($mime) {
            $mime = explode(';', $mime);
            $mime = trim($mime[0]);

            if (in_array($mime, ['application/x-empty', 'inode/x-empty'])) {
                // finfo return this mime for empty files
                $mime = 'text/plain';
            } elseif ('application/x-zip' == $mime) {
                // http://elrte.org/redmine/issues/163
                $mime = 'application/zip';
            }
        }

        return $mime ? $mime : 'unknown';
    }

    /**
     * Detect file type extension by local path
     *
     * @param  object $volume elFinderVolumeDriver instance
     * @param  string $path   Local path
     * @param  string $name   Filename to save
     *
     * @return string file type extension with dot
     * @author Naoki Sawada
     */
    protected function detectFileExtension($volume, $path, $name)
    {
        $mime = $this->detectMimeType($path);
        if ('unknown' === $mime) {
            $mime = 'application/octet-stream';
        }
        $ext = $volume->getExtentionByMime($volume->mimeTypeNormalize($mime, $name));

        return $ext ? ('.' . $ext) : '';
    }

    /**
     * Get temporary directory path
     *
     * @param  string $volumeTempPath
     *
     * @return string
     * @author Naoki Sawada
     */
    private function getTempDir($volumeTempPath = null)
    {
        $testDirs = [];
        if ($this->uploadTempPath) {
            $testDirs[] = rtrim(realpath($this->uploadTempPath), DIRECTORY_SEPARATOR);
        }
        if ($volumeTempPath) {
            $testDirs[] = rtrim(realpath($volumeTempPath), DIRECTORY_SEPARATOR);
        }
        if (self::$commonTempPath) {
            $testDirs[] = self::$commonTempPath;
        }
        $tempDir = '';
        foreach ($testDirs as $testDir) {
            if (!$testDir || !is_dir($testDir)) {
                continue;
            }
            if (is_writable($testDir)) {
                $tempDir = $testDir;
                $gc = time() - 3600;
                foreach (glob($tempDir . DIRECTORY_SEPARATOR . 'ELF*') as $cf) {
                    if (filemtime($cf) < $gc) {
                        unlink($cf);
                    }
                }
                break;
            }
        }

        return $tempDir;
    }

    /**
     * chmod
     *
     * @param array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author David Bartle
     */
    protected function chmod($args)
    {
        $targets = $args['targets'];
        $mode = intval((string)$args['mode'], 8);

        if (!is_array($targets)) {
            $targets = [$targets];
        }

        $result = [];

        if (false == ($volume = $this->volume($targets[0]))) {
            $result['error'] = $this->error(self::ERROR_CONF_NO_VOL);

            return $result;
        }

        $this->itemLock($targets);

        $files = [];
        $errors = [];
        foreach ($targets as $target) {
            self::checkAborted();

            $file = $volume->chmod($target, $mode);
            if ($file) {
                $files = array_merge($files, is_array($file) ? $file : [$file]);
            } else {
                $errors = array_merge($errors, $volume->error());
            }
        }

        if ($files) {
            $result['changed'] = $files;
            if ($errors) {
                $result['warning'] = $this->error($errors);
            }
        } else {
            $result['error'] = $this->error($errors);
        }

        return $result;
    }

    /**
     * Check chunked upload files
     *
     * @param string $tmpname uploaded temporary file path
     * @param string $chunk   uploaded chunk file name
     * @param string $cid     uploaded chunked file id
     * @param string $tempDir temporary dirctroy path
     * @param null   $volume
     *
     * @throws elFinderAbortException
     * @return array|null
     * @author Naoki Sawada
     */
    private function checkChunkedFile($tmpname, $chunk, $cid, $tempDir, $volume = null)
    {
        /* @var elFinderVolumeDriver $volume */
        if (preg_match('/^(.+)(\.\d+_(\d+))\.part$/s', $chunk, $m)) {
            $fname = $m[1];
            $encname = md5($cid . '_' . $fname);
            $base = $tempDir . DIRECTORY_SEPARATOR . 'ELF' . $encname;
            $clast = intval($m[3]);
            if (null === $tmpname) {
                ignore_user_abort(true);
                // chunked file upload fail
                foreach (glob($base . '*') as $cf) {
                    unlink($cf);
                }
                ignore_user_abort(false);

                return null;
            }

            $range = isset($_POST['range']) ? trim($_POST['range']) : '';
            if ($range && preg_match('/^(\d+),(\d+),(\d+)$/', $range, $ranges)) {
                $start = $ranges[1];
                $len = $ranges[2];
                $size = $ranges[3];
                $tmp = $base . '.part';
                $csize = filesize($tmpname);

                $tmpExists = is_file($tmp);
                if (!$tmpExists) {
                    // check upload max size
                    $uploadMaxSize = $volume ? $volume->getUploadMaxSize() : 0;
                    if ($uploadMaxSize > 0 && $size > $uploadMaxSize) {
                        return [self::ERROR_UPLOAD_FILE_SIZE, false];
                    }
                    // make temp file
                    $ok = false;
                    if ($fp = fopen($tmp, 'wb')) {
                        flock($fp, LOCK_EX);
                        $ok = ftruncate($fp, $size);
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        touch($base);
                    }
                    if (!$ok) {
                        unlink($tmp);

                        return [self::ERROR_UPLOAD_TEMP, false];
                    }
                } else {
                    // wait until makeing temp file (for anothor session)
                    $cnt = 1200; // Time limit 120 sec
                    while (!is_file($base) && --$cnt) {
                        usleep(100000); // wait 100ms
                    }
                    if (!$cnt) {
                        return [self::ERROR_UPLOAD_TEMP, false];
                    }
                }

                // check size info
                if ($len != $csize || $start + $len > $size || ($tmpExists && $size != filesize($tmp))) {
                    return [self::ERROR_UPLOAD_TEMP, false];
                }

                // write chunk data
                $src = fopen($tmpname, 'rb');
                $fp = fopen($tmp, 'cb');
                fseek($fp, $start);
                $writelen = stream_copy_to_stream($src, $fp, $len);
                fclose($fp);
                fclose($src);

                try {
                    // to check connection is aborted
                    self::checkAborted();
                } catch (elFinderAbortException $e) {
                    unlink($tmpname);
                    is_file($tmp) && unlink($tmp);
                    is_file($base) && unlink($base);
                    throw $e;
                }

                if ($writelen != $len) {
                    return [self::ERROR_UPLOAD_TEMP, false];
                }

                // write counts
                file_put_contents($base, "\0", FILE_APPEND | LOCK_EX);

                if (filesize($base) >= $clast + 1) {
                    // Completion
                    unlink($base);

                    return [$tmp, $fname];
                }
            } else {
                // old way
                $part = $base . $m[2];
                if (move_uploaded_file($tmpname, $part)) {
                    chmod($part, 0600);
                    if ($clast < count(glob($base . '*'))) {
                        $parts = [];
                        for ($i = 0; $i <= $clast; $i++) {
                            $name = $base . '.' . $i . '_' . $clast;
                            if (is_readable($name)) {
                                $parts[] = $name;
                            } else {
                                $parts = null;
                                break;
                            }
                        }
                        if ($parts) {
                            if (!is_file($base)) {
                                touch($base);
                                if ($resfile = tempnam($tempDir, 'ELF')) {
                                    $target = fopen($resfile, 'wb');
                                    foreach ($parts as $f) {
                                        $fp = fopen($f, 'rb');
                                        while (!feof($fp)) {
                                            fwrite($target, fread($fp, 8192));
                                        }
                                        fclose($fp);
                                        unlink($f);
                                    }
                                    fclose($target);
                                    unlink($base);

                                    return [$resfile, $fname];
                                }
                                unlink($base);
                            }
                        }
                    }
                }
            }
        }

        return ['', ''];
    }

    /**
     * Save uploaded files
     *
     * @param  array
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function upload($args)
    {
        $ngReg = '/[\/\\?*:|"<>]/';
        $target = $args['target'];
        $volume = $this->volume($target);
        $files = isset($args['FILES']['upload']) && is_array($args['FILES']['upload']) ? $args['FILES']['upload'] : [];
        $header = empty($args['html']) ? [] : ['header' => 'Content-Type: text/html; charset=utf-8'];
        $result = array_merge(['added' => []], $header);
        $paths = $args['upload_path'] ? $args['upload_path'] : [];
        $chunk = $args['chunk'] ? $args['chunk'] : '';
        $cid = $args['cid'] ? (int)$args['cid'] : '';
        $mtimes = $args['mtime'] ? $args['mtime'] : [];
        $tmpfname = '';

        if (!$volume) {
            return array_merge(['error' => $this->error(self::ERROR_UPLOAD, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)], $header);
        }

        // check $chunk
        if (false !== mb_strpos($chunk, '/') || false !== mb_strpos($chunk, '\\')) {
            return ['error' => $this->error(self::ERROR_UPLOAD)];
        }

        if ('' !== $args['overwrite']) {
            $volume->setUploadOverwrite($args['overwrite']);
        }

        $renames = $hashes = [];
        $suffix = '~';
        if ($args['renames'] && is_array($args['renames'])) {
            $renames = array_flip($args['renames']);
            if (is_string($args['suffix']) && !preg_match($ngReg, $args['suffix'])) {
                $suffix = $args['suffix'];
            }
        }
        if ($args['hashes'] && is_array($args['hashes'])) {
            $hashes = array_flip($args['hashes']);
        }

        $this->itemLock($target);

        // file extentions table by MIME
        $extTable = array_flip(array_unique($volume->getMimeTable()));

        if (empty($files)) {
            if (isset($args['upload']) && is_array($args['upload']) && ($tempDir = $this->getTempDir($volume->getTempPath()))) {
                $names = [];
                foreach ($args['upload'] as $i => $url) {
                    // check chunked file upload commit
                    if ($chunk) {
                        if ('chunkfail' === $url && 'chunkfail' === $args['mimes']) {
                            $this->checkChunkedFile(null, $chunk, $cid, $tempDir);
                            if (preg_match('/^(.+)(\.\d+_(\d+))\.part$/s', $chunk, $m)) {
                                $result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $m[1], self::ERROR_UPLOAD_TEMP);
                            }

                            return $result;
                        }
                        $tmpfname = $tempDir . '/' . $chunk;
                        $files['tmp_name'][$i] = $tmpfname;
                        $files['name'][$i] = $url;
                        $files['error'][$i] = 0;
                        $GLOBALS['elFinderTempFiles'][$tmpfname] = true;
                        break;
                    }

                    $tmpfname = $tempDir . DIRECTORY_SEPARATOR . 'ELF_FATCH_' . md5($url . microtime(true));
                    $GLOBALS['elFinderTempFiles'][$tmpfname] = true;

                    $_name = '';
                    // check is data:
                    if ('data:' === mb_substr($url, 0, 5)) {
                        list($data, $args['name'][$i]) = $this->parse_data_scheme($url, $extTable, $args);
                    } else {
                        $fp = fopen($tmpfname, 'wb');
                        if ($data = $this->get_remote_contents($url, 30, 5, 'Mozilla/5.0', $fp)) {
                            // to check connection is aborted
                            self::checkAborted();
                            $_name = preg_replace('~^.*?([^/#?]+)(?:\?.*)?(?:#.*)?$~', '$1', rawurldecode($url));
                            // Check `Content-Disposition` response header
                            if ($data && ($headers = get_headers($url, true)) && !empty($headers['Content-Disposition'])) {
                                if (preg_match('/filename\*=(?:([a-zA-Z0-9_-]+?)\'\')"?([a-z0-9_.~%-]+)"?/i', $headers['Content-Disposition'], $m)) {
                                    $_name = rawurldecode($m[2]);
                                    if ($m[1] && 'UTF-8' !== mb_strtoupper($m[1]) && function_exists('mb_convert_encoding')) {
                                        $_name = mb_convert_encoding($_name, 'UTF-8', $m[1]);
                                    }
                                } elseif (preg_match('/filename="?([ a-z0-9_.~%-]+)"?/i', $headers['Content-Disposition'], $m)) {
                                    $_name = rawurldecode($m[1]);
                                }
                            }
                        }
                    }
                    if ($data) {
                        if (isset($args['name'][$i])) {
                            $_name = $args['name'][$i];
                        }
                        if ($_name) {
                            $_ext = '';
                            if (preg_match('/(\.[a-z0-9]{1,7})$/', $_name, $_match)) {
                                $_ext = $_match[1];
                            }
                            if ((is_resource($data) && fclose($data)) || file_put_contents($tmpfname, $data)) {
                                $GLOBALS['elFinderTempFiles'][$tmpfname] = true;
                                $_name = preg_replace($ngReg, '_', $_name);
                                list($_a, $_b) = array_pad(explode('.', $_name, 2), 2, '');
                                if ('' === $_b) {
                                    if ($_ext) {
                                        rename($tmpfname, $tmpfname . $_ext);
                                        $tmpfname = $tmpfname . $_ext;
                                    }
                                    $_b = $this->detectFileExtension($volume, $tmpfname, $_name);
                                    $_name = $_a . $_b;
                                } else {
                                    $_b = '.' . $_b;
                                }
                                if (isset($names[$_name])) {
                                    $_name = $_a . '_' . $names[$_name]++ . $_b;
                                } else {
                                    $names[$_name] = 1;
                                }
                                $files['tmp_name'][$i] = $tmpfname;
                                $files['name'][$i] = $_name;
                                $files['error'][$i] = 0;
                                // set to auto rename
                                $volume->setUploadOverwrite(false);
                            } else {
                                unlink($tmpfname);
                            }
                        }
                    }
                }
            }
            if (empty($files)) {
                return array_merge(['error' => $this->error(self::ERROR_UPLOAD, self::ERROR_UPLOAD_NO_FILES)], $header);
            }
        }

        $addedDirs = [];
        $errors = [];
        foreach ($files['name'] as $i => $name) {
            if (($error = $files['error'][$i]) > 0) {
                $result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, UPLOAD_ERR_INI_SIZE == $error || UPLOAD_ERR_FORM_SIZE == $error ? self::ERROR_UPLOAD_FILE_SIZE : self::ERROR_UPLOAD_TRANSFER, $error);
                $this->uploadDebug = 'Upload error code: ' . $error;
                break;
            }

            $tmpname = $files['tmp_name'][$i];
            $thash = ($paths && isset($paths[$i])) ? $paths[$i] : $target;
            $mtime = isset($mtimes[$i]) ? $mtimes[$i] : 0;
            if ('blob' === $name) {
                if ($chunk) {
                    if ($tempDir = $this->getTempDir($volume->getTempPath())) {
                        list($tmpname, $name) = $this->checkChunkedFile($tmpname, $chunk, $cid, $tempDir, $volume);
                        if ($tmpname) {
                            if (false === $name) {
                                preg_match('/^(.+)(\.\d+_(\d+))\.part$/s', $chunk, $m);
                                $result['error'] = $this->error(self::ERROR_UPLOAD_FILE, $m[1], $tmpname);
                                $result['_chunkfailure'] = true;
                                $this->uploadDebug = 'Upload error: ' . $tmpname;
                            } elseif ($name) {
                                $result['_chunkmerged'] = basename($tmpname);
                                $result['_name'] = $name;
                                $result['_mtime'] = $mtime;
                            }
                        }
                    } else {
                        $result['error'] = $this->error(self::ERROR_UPLOAD_FILE, $chunk, self::ERROR_UPLOAD_TEMP);
                        $this->uploadDebug = 'Upload error: unable open tmp file';
                    }

                    return $result;
                }
                // for form clipboard with Google Chrome or Opera
                $name = 'image.png';
            }

            // Set name if name eq 'image.png' and $args has 'name' array, e.g. clipboard data
            if ('image' === mb_strtolower(mb_substr($name, 0, 5)) && is_array($args['name']) && isset($args['name'][$i])) {
                $type = $files['type'][$i];
                $name = $args['name'][$i];
                $ext = isset($extTable[$type]) ? '.' . $extTable[$type] : '';
                if ($ext) {
                    $name = preg_replace('/\.[^.]*$/', '', $name);
                }
                $name .= $ext;
            }

            // do hook function 'upload.presave'
            if (!empty($this->listeners['upload.presave'])) {
                foreach ($this->listeners['upload.presave'] as $handler) {
                    $_res = call_user_func_array($handler, [&$thash, &$name, $tmpname, $this, $volume]);
                    if ($_res && is_array($_res)) {
                        $_err = !empty($_res['error']) ? $_res['error'] : (!empty($_res['warning']) ? $_res['warning'] : null);
                        if ($_err) {
                            if (is_array($_err)) {
                                $errors = array_merge($errors, $_err);
                            } else {
                                $errors[] = (string)$_err;
                            }
                            if ($_res['error']) {
                                if (!is_uploaded_file($tmpname)) {
                                    if (unlink($tmpname) && $tmpfname) {
                                        unset($GLOBALS['elFinderTempFiles'][$tmpfname]);
                                    }
                                }
                                continue 2;
                            }
                        }
                    }
                }
            }

            clearstatcache();
            if ($mtime && is_file($tmpname)) {
                // for keep timestamp option in the LocalFileSystem volume
                touch($tmpname, $mtime);
            }

            $fp = null;
            if (!is_file($tmpname) || false === ($fp = fopen($tmpname, 'rb'))) {
                $errors = array_merge($errors, [self::ERROR_UPLOAD_FILE, $name, (false === $fp ? self::ERROR_UPLOAD_TEMP : self::ERROR_UPLOAD_TRANSFER)]);
                $this->uploadDebug = 'Upload error: unable open tmp file';
                if (!is_uploaded_file($tmpname)) {
                    if (unlink($tmpname) && $tmpfname) {
                        unset($GLOBALS['elFinderTempFiles'][$tmpfname]);
                    }
                    continue;
                }
                break;
            }
            $rnres = [];
            if ('' !== $thash && $thash !== $target) {
                if ($dir = $volume->dir($thash)) {
                    $_target = $thash;
                    if (!isset($addedDirs[$thash])) {
                        $addedDirs[$thash] = true;
                        $result['added'][] = $dir;
                        // to support multi-level directory creation
                        $_phash = isset($dir['phash']) ? $dir['phash'] : null;
                        while ($_phash && !isset($addedDirs[$_phash]) && $_phash !== $target) {
                            if ($_dir = $volume->dir($_phash)) {
                                $addedDirs[$_phash] = true;
                                $result['added'][] = $_dir;
                                $_phash = isset($_dir['phash']) ? $_dir['phash'] : null;
                            } else {
                                break;
                            }
                        }
                    }
                } else {
                    $result['error'] = $this->error(self::ERROR_UPLOAD, self::ERROR_TRGDIR_NOT_FOUND, 'hash@' . $thash);
                    break;
                }
            } else {
                $_target = $target;
                // file rename for backup
                if (isset($renames[$name])) {
                    $dir = $volume->realpath($_target);
                    if (isset($hashes[$name])) {
                        $hash = $hashes[$name];
                    } else {
                        $hash = $volume->getHash($dir, $name);
                    }
                    $rnres = $this->rename(['target' => $hash, 'name' => $volume->uniqueName($dir, $name, $suffix, true, 0)]);
                    if (!empty($rnres['error'])) {
                        $result['warning'] = $rnres['error'];
                        if (!is_array($rnres['error'])) {
                            $errors = array_push($errors, $rnres['error']);
                        } else {
                            $errors = array_merge($errors, $rnres['error']);
                        }
                        continue;
                    }
                }
            }
            if (!$_target || false === ($file = $volume->upload($fp, $_target, $name, $tmpname, ($_target === $target) ? $hashes : []))) {
                $errors = array_merge($errors, $this->error(self::ERROR_UPLOAD_FILE, $name, $volume->error()));
                fclose($fp);
                if (!is_uploaded_file($tmpname) && unlink($tmpname)) {
                    unset($GLOBALS['elFinderTempFiles'][$tmpname]);
                }
                continue;
            }

            is_resource($fp) && fclose($fp);
            if (!is_uploaded_file($tmpname)) {
                clearstatcache();
                if (!is_file($tmpname) || unlink($tmpname)) {
                    unset($GLOBALS['elFinderTempFiles'][$tmpname]);
                }
            }
            $result['added'][] = $file;
            if ($rnres) {
                $result = array_merge_recursive($result, $rnres);
            }
        }

        if ($errors) {
            $result['warning'] = $errors;
        }

        if ($GLOBALS['elFinderTempFiles']) {
            foreach (array_keys($GLOBALS['elFinderTempFiles']) as $_temp) {
                is_file($_temp) && unlink($_temp);
            }
        }
        $result['removed'] = $volume->removed();

        if (!empty($args['node'])) {
            $result['callback'] = [
                'node' => $args['node'],
                'bind' => 'upload',
            ];
        }

        return $result;
    }

    /**
     * Copy/move files into new destination
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function paste($args)
    {
        $dst = $args['dst'];
        $targets = is_array($args['targets']) ? $args['targets'] : [];
        $cut = !empty($args['cut']);
        $error = $cut ? self::ERROR_MOVE : self::ERROR_COPY;
        $result = ['changed' => [], 'added' => [], 'removed' => [], 'warning' => []];

        if (false == ($dstVolume = $this->volume($dst))) {
            return ['error' => $this->error($error, '#' . $targets[0], self::ERROR_TRGDIR_NOT_FOUND, '#' . $dst)];
        }

        $this->itemLock($dst);

        $hashes = $renames = [];
        $suffix = '~';
        if (!empty($args['renames'])) {
            $renames = array_flip($args['renames']);
            if (is_string($args['suffix']) && !preg_match('/[\/\\?*:|"<>]/', $args['suffix'])) {
                $suffix = $args['suffix'];
            }
        }
        if (!empty($args['hashes'])) {
            $hashes = array_flip($args['hashes']);
        }

        foreach ($targets as $target) {
            self::checkAborted();

            if (false == ($srcVolume = $this->volume($target))) {
                $result['warning'] = array_merge($result['warning'], $this->error($error, '#' . $target, self::ERROR_FILE_NOT_FOUND));
                continue;
            }

            $rnres = [];
            if ($renames) {
                $file = $srcVolume->file($target);
                if (isset($renames[$file['name']])) {
                    $dir = $dstVolume->realpath($dst);
                    if (isset($hashes[$file['name']])) {
                        $hash = $hashes[$file['name']];
                    } else {
                        $hash = $dstVolume->getHash($dir, $file['name']);
                    }
                    $rnres = $this->rename(['target' => $hash, 'name' => $dstVolume->uniqueName($dir, $file['name'], $suffix, true, 0)]);
                    if (!empty($rnres['error'])) {
                        $result['warning'] = array_merge($result['warning'], $rnres['error']);
                        continue;
                    }
                }
            }

            if ($cut && $this->itemLocked($target)) {
                $rm = $srcVolume->file($target);
                $result['warning'] = array_merge($result['warning'], $this->error(self::ERROR_LOCKED, $rm['name']));
                continue;
            }

            if (false == ($file = $dstVolume->paste($srcVolume, $target, $dst, $cut, $hashes))) {
                $result['warning'] = array_merge($result['warning'], $this->error($dstVolume->error()));
                continue;
            }

            if ($error = $dstVolume->error()) {
                $result['warning'] = array_merge($result['warning'], $this->error($error));
            }

            if ($rnres) {
                $result = array_merge_recursive($result, $rnres);
            }
        }
        if (count($result['warning']) < 1) {
            unset($result['warning']);
        } else {
            $result['sync'] = true;
        }

        return $result;
    }

    /**
     * Return file content
     *
     * @param  array $args command arguments
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function get($args)
    {
        $target = $args['target'];
        $volume = $this->volume($target);
        $enc = false;

        if (!$volume || false == ($file = $volume->file($target))) {
            return ['error' => $this->error(self::ERROR_OPEN, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }

        if (false === ($content = $volume->getContents($target))) {
            return ['error' => $this->error(self::ERROR_OPEN, $volume->path($target), $volume->error())];
        }

        $mime = isset($file['mime']) ? $file['mime'] : '';
        if ($mime && ('text' === mb_strtolower(mb_substr($mime, 0, 4)) || in_array(mb_strtolower($mime), self::$textMimes))) {
            $enc = '';
            if ('' !== $content) {
                if (!$args['conv'] || '1' == $args['conv']) {
                    // detect encoding
                    if (function_exists('mb_detect_encoding')) {
                        if ($enc = mb_detect_encoding($content, mb_detect_order(), true)) {
                            $encu = mb_strtoupper($enc);
                            if ('UTF-8' === $encu || 'ASCII' === $encu) {
                                $enc = '';
                            }
                        } else {
                            $enc = 'unknown';
                        }
                    } elseif (!preg_match('//u', $content)) {
                        $enc = 'unknown';
                    }
                    if ('unknown' === $enc) {
                        $enc = $volume->getOption('encoding');
                        if (!$enc || 'UTF-8' === mb_strtoupper($enc)) {
                            $enc = 'unknown';
                        }
                    }
                    if ($enc && 'unknown' !== $enc) {
                        $utf8 = iconv($enc, 'UTF-8', $content);
                        if (false === $utf8 && function_exists('mb_convert_encoding')) {
                            $utf8 = mb_convert_encoding($content, 'UTF-8', $enc);
                            if (mb_convert_encoding($utf8, $enc, 'UTF-8') !== $content) {
                                $enc = 'unknown';
                            }
                        } else {
                            if (false === $utf8 || iconv('UTF-8', $enc, $utf8) !== $content) {
                                $enc = 'unknown';
                            }
                        }
                        if ('unknown' !== $enc) {
                            $content = $utf8;
                        }
                    }
                    if ($enc) {
                        if ('1' == $args['conv']) {
                            $args['conv'] = '';
                            if ('unknown' === $enc) {
                                $content = false;
                            }
                        } elseif ('unknown' === $enc) {
                            return ['doconv' => $enc];
                        }
                    }
                    if ('1' == $args['conv']) {
                        $args['conv'] = '';
                    }
                }
                if ($args['conv']) {
                    $enc = $args['conv'];
                    if ('UTF-8' !== mb_strtoupper($enc)) {
                        $_content = $content;
                        $content = iconv($enc, 'UTF-8', $content);
                        if (false === $content && function_exists('mb_convert_encoding')) {
                            $content = mb_convert_encoding($_content, 'UTF-8', $enc);
                        }
                    } else {
                        $enc = '';
                    }
                }
            }
        } else {
            $content = 'data:' . ($mime ? $mime : 'application/octet-stream') . ';base64,' . base64_encode($content);
        }

        if (false !== $enc) {
            $json = false;
            if (false !== $content) {
                $json = json_encode($content);
            }
            if (false === $content || false === $json || mb_strlen($json) < mb_strlen($content)) {
                return ['error' => $this->error(self::ERROR_CONV_UTF8, self::ERROR_NOT_UTF8_CONTENT, $volume->path($target))];
            }
        }

        $res = [
            'header' => [
                'Content-Type: application/json',
            ],
            'content' => $content,
        ];

        // add cache control headers
        if ($cacheHeaders = $volume->getOption('cacheHeaders')) {
            $res['header'] = array_merge($res['header'], $cacheHeaders);
        }

        if ($enc) {
            $res['encoding'] = $enc;
        }

        return $res;
    }

    /**
     * Save content into text file
     *
     * @param $args
     *
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function put($args)
    {
        $target = $args['target'];
        $encoding = isset($args['encoding']) ? $args['encoding'] : '';

        if (false == ($volume = $this->volume($target))
            || false == ($file = $volume->file($target))) {
            return ['error' => $this->error(self::ERROR_SAVE, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }

        $this->itemLock($target);

        if ('scheme' === $encoding) {
            if (preg_match('~^https?://~i', $args['content'])) {
                /** @var resource $fp */
                $fp = $this->get_remote_contents($args['content'], 30, 5, 'Mozilla/5.0', $volume->tmpfile());
                if (!$fp) {
                    return ['error' => self::ERROR_SAVE, $args['content'], self::ERROR_FILE_NOT_FOUND];
                }
                $fmeta = stream_get_meta_data($fp);
                $mime = $this->detectMimeType($fmeta['uri']);
                if ('unknown' === $mime) {
                    $mime = 'application/octet-stream';
                }
                $mime = $volume->mimeTypeNormalize($mime, $file['name']);
                $args['content'] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fmeta['uri']));
            }
            $encoding = '';
            $args['content'] = "\0" . $args['content'];
        } elseif ('hash' === $encoding) {
            $_hash = $args['content'];
            if ($_src = $this->getVolume($_hash)) {
                if ($_file = $_src->file($_hash)) {
                    if ($_data = $_src->getContents($_hash)) {
                        $args['content'] = 'data:' . $file['mime'] . ';base64,' . base64_encode($_data);
                    }
                }
            }
            $encoding = '';
            $args['content'] = "\0" . $args['content'];
        }
        if ($encoding) {
            $content = iconv('UTF-8', $encoding, $args['content']);
            if (false === $content && function_exists('mb_detect_encoding')) {
                $content = mb_convert_encoding($args['content'], $encoding, 'UTF-8');
            }
            if (false !== $content) {
                $args['content'] = $content;
            }
        }
        if (false == ($file = $volume->putContents($target, $args['content']))) {
            return ['error' => $this->error(self::ERROR_SAVE, $volume->path($target), $volume->error())];
        }

        return ['changed' => [$file]];
    }

    /**
     * Extract files from archive
     *
     * @param  array $args command arguments
     *
     * @return array
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function extract($args)
    {
        $target = $args['target'];
        $makedir = isset($args['makedir']) ? (bool)$args['makedir'] : null;

        if (false == ($volume = $this->volume($target))
            || false == ($file = $volume->file($target))) {
            return ['error' => $this->error(self::ERROR_EXTRACT, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }

        $res = [];
        if ($file = $volume->extract($target, $makedir)) {
            $res['added'] = isset($file['read']) ? [$file] : $file;
            if ($err = $volume->error()) {
                $res['warning'] = $err;
            }
        } else {
            $res['error'] = $this->error(self::ERROR_EXTRACT, $volume->path($target), $volume->error());
        }

        return $res;
    }

    /**
     * Create archive
     *
     * @param  array $args command arguments
     *
     * @throws Exception
     * @return array
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     */
    protected function archive($args)
    {
        $targets = isset($args['targets']) && is_array($args['targets']) ? $args['targets'] : [];
        $name = isset($args['name']) ? $args['name'] : '';

        if (false == ($volume = $this->volume($targets[0]))) {
            return $this->error(self::ERROR_ARCHIVE, self::ERROR_TRGDIR_NOT_FOUND);
        }

        foreach ($targets as $target) {
            $this->itemLock($target);
        }

        return ($file = $volume->archive($targets, $args['type'], $name))
            ? ['added' => [$file]]
            : ['error' => $this->error(self::ERROR_ARCHIVE, $volume->error())];
    }

    /**
     * Search files
     *
     * @param  array $args command arguments
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry Levashov
     */
    protected function search($args)
    {
        $q = trim($args['q']);
        $mimes = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : [];
        $target = !empty($args['target']) ? $args['target'] : null;
        $type = !empty($args['type']) ? $args['type'] : null;
        $result = [];
        $errors = [];

        if ($target) {
            if ($volume = $this->volume($target)) {
                $result = $volume->search($q, $mimes, $target, $type);
                $errors = array_merge($errors, $volume->error());
            }
        } else {
            foreach ($this->volumes as $volume) {
                $result = array_merge($result, $volume->search($q, $mimes, null, $type));
                $errors = array_merge($errors, $volume->error());
            }
        }

        $result = ['files' => $result];
        if ($errors) {
            $result['warning'] = $errors;
        }

        return $result;
    }

    /**
     * Return file info (used by client "places" ui)
     *
     * @param  array $args command arguments
     *
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry Levashov
     */
    protected function info($args)
    {
        $files = [];
        $compare = null;
        // long polling mode
        if ($args['compare'] && 1 === count($args['targets'])) {
            $compare = intval($args['compare']);
            $hash = $args['targets'][0];
            if ($volume = $this->volume($hash)) {
                $standby = (int)$volume->getOption('plStandby');
                $_compare = false;
                if (($syncCheckFunc = $volume->getOption('syncCheckFunc')) && is_callable($syncCheckFunc)) {
                    $_compare = call_user_func_array($syncCheckFunc, [$volume->realpath($hash), $standby, $compare, $volume, $this]);
                }
                if (false !== $_compare) {
                    $compare = $_compare;
                } else {
                    $sleep = max(1, (int)$volume->getOption('tsPlSleep'));
                    $limit = max(1, $standby / $sleep) + 1;
                    do {
                        self::extendTimeLimit(30 + $sleep);
                        $volume->clearstatcache();
                        if (false != ($info = $volume->file($hash))) {
                            if ($info['ts'] != $compare) {
                                $compare = $info['ts'];
                                break;
                            }
                        } else {
                            $compare = 0;
                            break;
                        }
                        if (--$limit) {
                            sleep($sleep);
                        }
                    } while ($limit);
                }
            }
        } else {
            foreach ($args['targets'] as $hash) {
                self::checkAborted();
                if (false != ($volume = $this->volume($hash))
                    && false != ($info = $volume->file($hash))) {
                    $info['path'] = $volume->path($hash);
                    $files[] = $info;
                }
            }
        }

        $result = ['files' => $files];
        if (null !== $compare) {
            $result['compare'] = strval($compare);
        }

        return $result;
    }

    /**
     * Return image dimensions
     *
     * @param  array $args command arguments
     *
     * @throws ImagickException
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     */
    protected function dim($args)
    {
        $res = [];
        $target = $args['target'];

        if (false != ($volume = $this->volume($target))) {
            if ($dim = $volume->dimensions($target, $args)) {
                if (is_array($dim) && isset($dim['dim'])) {
                    $res = $dim;
                } else {
                    $res = ['dim' => $dim];
                    if ($subImgLink = $volume->getSubstituteImgLink($target, explode('x', $dim))) {
                        $res['url'] = $subImgLink;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Resize image
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws ImagickException
     * @throws elFinderAbortException
     * @return array
     * @author Dmitry (dio) Levashov
     * @author Alexey Sukhotin
     */
    protected function resize($args)
    {
        $target = $args['target'];
        $width = (int)$args['width'];
        $height = (int)$args['height'];
        $x = (int)$args['x'];
        $y = (int)$args['y'];
        $mode = $args['mode'];
        $bg = $args['bg'];
        $degree = (int)$args['degree'];
        $quality = (int)$args['quality'];

        if (false == ($volume = $this->volume($target))
            || false == ($file = $volume->file($target))) {
            return ['error' => $this->error(self::ERROR_RESIZE, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
        }

        if ('rotate' !== $mode && ($width < 1 || $height < 1)) {
            return ['error' => $this->error(self::ERROR_RESIZESIZE)];
        }

        return ($file = $volume->resize($target, $width, $height, $x, $y, $mode, $bg, $degree, $quality))
            ? (!empty($file['losslessRotate']) ? $file : ['changed' => [$file]])
            : ['error' => $this->error(self::ERROR_RESIZE, $volume->path($target), $volume->error())];
    }

    /**
     * Return content URL
     *
     * @param  array $args command arguments
     *
     * @return array
     * @author Naoki Sawada
     **/
    protected function url($args)
    {
        $target = $args['target'];
        $options = isset($args['options']) ? $args['options'] : [];
        if (false != ($volume = $this->volume($target))) {
            if (!$volume->commandDisabled('url')) {
                $url = $volume->getContentUrl($target, $options);

                return $url ? ['url' => $url] : [];
            }
        }

        return [];
    }

    /**
     * Output callback result with JavaScript that control elFinder
     * or HTTP redirect to callbackWindowURL
     *
     * @param  array  command arguments
     * @param mixed $args
     *
     * @throws elFinderAbortException
     * @author Naoki Sawada
     */
    protected function callback($args)
    {
        $checkReg = '/[^a-zA-Z0-9;._-]/';
        $node = (isset($args['node']) && !preg_match($checkReg, $args['node'])) ? $args['node'] : '';
        $json = (isset($args['json']) && json_decode($args['json'])) ? $args['json'] : '{}';
        $bind = (isset($args['bind']) && !preg_match($checkReg, $args['bind'])) ? $args['bind'] : '';
        $done = (!empty($args['done']));

        while (ob_get_level()) {
            if (!ob_end_clean()) {
                break;
            }
        }

        if ($done || !$this->callbackWindowURL) {
            $script = '';
            if ($node) {
                if ($bind) {
                    $trigger = 'elf.trigger(\'' . $bind . '\', data);';
                    $triggerdone = 'elf.trigger(\'' . $bind . 'done\');';
                    $triggerfail = 'elf.trigger(\'' . $bind . 'fail\', data);';
                } else {
                    $trigger = $triggerdone = $triggerfail = '';
                }
                $script .= '
					var w = window.opener || window.parent || window;
					try {
						var elf = w.document.getElementById(\'' . $node . '\').elfinder;
						if (elf) {
							var data = ' . $json . ';
							if (data.error) {
								' . $triggerfail . '
								elf.error(data.error);
							} else {
								data.warning && elf.error(data.warning);
								data.removed && data.removed.length && elf.remove(data);
								data.added   && data.added.length   && elf.add(data);
								data.changed && data.changed.length && elf.change(data);
								' . $trigger . '
								' . $triggerdone . '
								data.sync && elf.sync();
							}
						}
					} catch(e) {
						// for CORS
						w.postMessage && w.postMessage(JSON.stringify({bind:\'' . $bind . '\',data:' . $json . '}), \'*\');
					}';
            }
            $script .= 'window.close();';

            $out = '<!DOCTYPE html><html><head><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><script>' . $script . '</script></head><body><a href="#" onlick="window.close();return false;">Close this window</a></body></html>';

            header('Content-Type: text/html; charset=utf-8');
            header('Content-Length: ' . mb_strlen($out));
            header('Cache-Control: private');
            header('Pragma: no-cache');

            echo $out;
        } else {
            $url = $this->callbackWindowURL;
            $url .= ((false === mb_strpos($url, '?')) ? '?' : '&')
                . '&node=' . rawurlencode($node)
                . (('{}' !== $json) ? ('&json=' . rawurlencode($json)) : '')
                . ($bind ? ('&bind=' . rawurlencode($bind)) : '')
                . '&done=1';

            header('Location: ' . $url);
        }
        throw new elFinderAbortException();
    }

    /**
     * PHP error handler, catch error types only E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     *
     * @return boolean
     */
    public static function phpErrorHandler($errno, $errstr, $errfile, $errline)
    {
        static $base = null;

        $proc = false;

        if (null === $base) {
            $base = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        }

        if (!(error_reporting() & $errno)) {
            return $proc;
        }

        $errfile = str_replace($base, '', $errfile);

        switch ($errno) {
            case E_WARNING:
            case E_USER_WARNING:
                self::$phpErrors[] = "WARNING: $errstr in $errfile line $errline.";
                $proc = true;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                self::$phpErrors[] = "NOTICE: $errstr in $errfile line $errline.";
                $proc = true;
                break;
            case E_STRICT:
                self::$phpErrors[] = "STRICT: $errstr in $errfile line $errline.";
                $proc = true;
                break;
            case E_RECOVERABLE_ERROR:
                self::$phpErrors[] = "RECOVERABLE_ERROR: $errstr in $errfile line $errline.";
                $proc = true;
                break;
        }

        if (defined('E_DEPRECATED')) {
            switch ($errno) {
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    self::$phpErrors[] = "DEPRECATED: $errstr in $errfile line $errline.";
                    $proc = true;
                    break;
            }
        }

        return $proc;
    }

    /***************************************************************************/
    /*                                   utils                                 */
    /***************************************************************************/

    /**
     * Return root - file's owner
     *
     * @param  string  file hash
     * @param mixed $hash
     *
     * @return elFinderVolumeDriver|bool (false)
     * @author Dmitry (dio) Levashov
     **/
    protected function volume($hash)
    {
        foreach ($this->volumes as $id => $v) {
            if (0 === mb_strpos('' . $hash, $id)) {
                return $this->volumes[$id];
            }
        }

        return false;
    }

    /**
     * Return files info array
     *
     * @param  array $data one file info or files info
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function toArray($data)
    {
        return isset($data['hash']) || !is_array($data) ? [$data] : $data;
    }

    /**
     * Return fils hashes list
     *
     * @param  array $files files info
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function hashes($files)
    {
        $ret = [];
        foreach ($files as $file) {
            $ret[] = $file['hash'];
        }

        return $ret;
    }

    /**
     * Remove from files list hidden files and files with required mime types
     *
     * @param  array $files files info
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function filter($files)
    {
        $exists = [];
        foreach ($files as $i => $file) {
            if (isset($exists[$file['hash']]) || !empty($file['hidden']) || !$this->default->mimeAccepted($file['mime'])) {
                unset($files[$i]);
            }
            $exists[$file['hash']] = true;
        }

        return array_values($files);
    }

    protected function utime()
    {
        $time = explode(' ', microtime());

        return (float)$time[1] + (float)$time[0];
    }

    /**
     * Return Network mount volume unique ID
     *
     * @param  array  $netVolumes Saved netvolumes array
     * @param  string $prefix     Id prefix
     *
     * @return string|false
     * @author Naoki Sawada
     **/
    protected function getNetVolumeUniqueId($netVolumes = null, $prefix = 'nm')
    {
        if (null === $netVolumes) {
            $netVolumes = $this->getNetVolumes();
        }
        $ids = [];
        foreach ($netVolumes as $vOps) {
            if (isset($vOps['id']) && 0 === mb_strpos($vOps['id'], $prefix)) {
                $ids[$vOps['id']] = true;
            }
        }
        if (!$ids) {
            $id = $prefix . '1';
        } else {
            $i = 0;
            while (isset($ids[$prefix . ++$i]) && $i < 10000);
            $id = $prefix . $i;
            if (isset($ids[$id])) {
                $id = false;
            }
        }

        return $id;
    }

    /**
     * Is item locked?
     *
     * @param string $hash
     *
     * @return boolean
     */
    protected function itemLocked($hash)
    {
        if (!self::$commonTempPath) {
            return false;
        }
        $lock = self::$commonTempPath . DIRECTORY_SEPARATOR . $hash . '.lock';
        if (file_exists($lock)) {
            if (filemtime($lock) + $this->itemLockExpire < time()) {
                unlink($lock);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Do lock target item
     *
     * @param array|string $hashes
     * @param bool      $autoUnlock
     *
     * @return void
     */
    protected function itemLock($hashes, $autoUnlock = true)
    {
        if (!self::$commonTempPath) {
            return;
        }
        if (!is_array($hashes)) {
            $hashes = [$hashes];
        }
        foreach ($hashes as $hash) {
            $lock = self::$commonTempPath . DIRECTORY_SEPARATOR . $hash . '.lock';
            if ($this->itemLocked($hash)) {
                $cnt = file_get_contents($lock) + 1;
            } else {
                $cnt = 1;
            }
            if (file_put_contents($lock, $cnt, LOCK_EX)) {
                if ($autoUnlock) {
                    $this->autoUnlocks[] = $hash;
                }
            }
        }
    }

    /**
     * Do unlock target item
     *
     * @param string $hash
     *
     * @return boolean
     */
    protected function itemUnlock($hash)
    {
        if (!$this->itemLocked($hash)) {
            return true;
        }
        $lock = self::$commonTempPath . DIRECTORY_SEPARATOR . $hash . '.lock';
        $cnt = file_get_contents($lock);
        if (--$cnt < 1) {
            unlink($lock);

            return true;
        }
        file_put_contents($lock, $cnt, LOCK_EX);

        return false;
    }

    /**
     * unlock locked items on command completion
     *
     * @return void
     */
    public function itemAutoUnlock()
    {
        if ($this->autoUnlocks) {
            foreach ($this->autoUnlocks as $hash) {
                $this->itemUnlock($hash);
            }
            $this->autoUnlocks = [];
        }
    }

    /**
     * Ensure directories recursively
     *
     * @param  object $volume Volume object
     * @param  string $target Target hash
     * @param  array  $dirs   Array of directory tree to ensure
     * @param  string $path   Relative path form target hash
     *
     * @return array|false      array('stats' => array([stat of maked directory]), 'hashes' => array('[path]' => '[hash]'), 'makes' => array([New directory hashes]), 'error' => array([Error name]))
     * @author Naoki Sawada
     **/
    protected function ensureDirsRecursively($volume, $target, $dirs, $path = '')
    {
        $res = ['stats' => [], 'hashes' => [], 'makes' => [], 'error' => []];
        foreach ($dirs as $name => $sub) {
            $name = (string)$name;
            $dir = $newDir = null;
            if ((($parent = $volume->realpath($target)) && ($dir = $volume->dir($volume->getHash($parent, $name)))) || ($newDir = $volume->mkdir($target, $name))) {
                $_path = $path . '/' . $name;
                if ($newDir) {
                    $res['makes'][] = $newDir['hash'];
                    $dir = $newDir;
                }
                $res['stats'][] = $dir;
                $res['hashes'][$_path] = $dir['hash'];
                if (count($sub)) {
                    $res = array_merge_recursive($res, $this->ensureDirsRecursively($volume, $dir['hash'], $sub, $_path));
                }
            } else {
                $res['error'][] = $name;
            }
        }

        return $res;
    }

    /***************************************************************************/
    /*                           static  utils                                 */
    /***************************************************************************/

    /**
     * Return full version of API that this connector supports all functions
     *
     * @return string
     */
    public static function getApiFullVersion()
    {
        return (string)self::$ApiVersion . '.' . (string)self::$ApiRevision;
    }

    /**
     * Return Is Animation Gif
     *
     * @param  string $path server local path of target image
     *
     * @return bool
     */
    public static function isAnimationGif($path)
    {
        list(, , $type) = getimagesize($path);
        switch ($type) {
            case IMAGETYPE_GIF:
                break;
            default:
                return false;
        }

        $imgcnt = 0;
        $fp = fopen($path, 'rb');
        fread($fp, 4);
        $c = fread($fp, 1);
        if (0x39 != ord($c)) {  // GIF89a
            return false;
        }

        while (!feof($fp)) {
            do {
                $c = fread($fp, 1);
            } while (0x21 != ord($c) && !feof($fp));

            if (feof($fp)) {
                break;
            }

            $c2 = fread($fp, 2);
            if ('f904' == bin2hex($c2)) {
                $imgcnt++;
                if (2 === $imgcnt) {
                    break;
                }
            }

            if (feof($fp)) {
                break;
            }
        }

        if ($imgcnt > 1) {
            return true;
        }

        return false;
    }

    /**
     * Return Is Animation Png
     *
     * @param  string $path server local path of target image
     *
     * @return bool
     */
    public static function isAnimationPng($path)
    {
        list(, , $type) = getimagesize($path);
        switch ($type) {
            case IMAGETYPE_PNG:
                break;
            default:
                return false;
        }

        $fp = fopen($path, 'rb');
        $img_bytes = fread($fp, 1024);
        fclose($fp);
        if ($img_bytes) {
            if (false !== mb_strpos(mb_substr($img_bytes, 0, mb_strpos($img_bytes, 'IDAT')), 'acTL')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return Is seekable stream resource
     *
     * @param resource $resource
     *
     * @return bool
     */
    public static function isSeekableStream($resource)
    {
        $metadata = stream_get_meta_data($resource);

        return $metadata['seekable'];
    }

    /**
     * Rewind stream resource
     *
     * @param resource $resource
     *
     * @return void
     */
    public static function rewind($resource)
    {
        self::isSeekableStream($resource) && rewind($resource);
    }

    /**
     * serialize and base64_encode of session data (If needed)
     *
     * @deprecated
     *
     * @param  mixed $var target variable
     *
     * @author Naoki Sawada
     * @return mixed|string
     */
    public static function sessionDataEncode($var)
    {
        if (self::$base64encodeSessionData) {
            $var = base64_encode(serialize($var));
        }

        return $var;
    }

    /**
     * base64_decode and unserialize of session data  (If needed)
     *
     * @deprecated
     *
     * @param  mixed $var     target variable
     * @param  bool  $checkIs data type for check (array|string|object|int)
     *
     * @author Naoki Sawada
     * @return bool|mixed
     */
    public static function sessionDataDecode(&$var, $checkIs = null)
    {
        if (self::$base64encodeSessionData) {
            $data = unserialize(base64_decode($var));
        } else {
            $data = $var;
        }
        $chk = true;
        if ($checkIs) {
            switch ($checkIs) {
                case 'array':
                    $chk = is_array($data);
                    break;
                case 'string':
                    $chk = is_string($data);
                    break;
                case 'object':
                    $chk = is_object($data);
                    break;
                case 'int':
                    $chk = is_int($data);
                    break;
            }
        }
        if (!$chk) {
            unset($var);

            return false;
        }

        return $data;
    }

    /**
     * Call session_write_close() if session is restarted
     *
     * @deprecated
     * @return void
     */
    public static function sessionWrite()
    {
        if (session_id()) {
            session_write_close();
        }
    }

    /**
     * Return elFinder static variable
     *
     * @param $key
     *
     * @return mixed|null
     */
    public static function getStaticVar($key)
    {
        return isset(self::$$key) ? self::$$key : null;
    }

    /**
     * Extend PHP execution time limit and also check connection is aborted
     *
     * @param int $time
     *
     * @throws elFinderAbortException
     * @return void
     */
    public static function extendTimeLimit($time = null)
    {
        static $defLimit = null;
        if (!self::aborted()) {
            if (null === $defLimit) {
                $defLimit = ini_get('max_execution_time');
            }
            if (0 != $defLimit) {
                $time = null === $time ? $defLimit : max($defLimit, $time);
                set_time_limit($time);
            }
        } else {
            throw new elFinderAbortException();
        }
    }

    /**
     * Check connection is aborted
     * Script stop immediately if connection aborted
     *
     * @throws elFinderAbortException
     * @return void
     */
    public static function checkAborted()
    {
        self::extendTimeLimit();
    }

    /**
     * Return bytes from php.ini value
     *
     * @param string $iniName
     * @param string $val
     *
     * @return number
     */
    public static function getIniBytes($iniName = '', $val = '')
    {
        if ('' !== $iniName) {
            $val = ini_get($iniName);
            if (false === $val) {
                return 0;
            }
        }
        $val = trim($val, "bB \t\n\r\0\x0B");
        $last = mb_strtolower($val[mb_strlen($val) - 1]);
        $val = (int)$val;
        switch ($last) {
            case 't':
                $val *= 1024;
                // no break
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Get script url.
     *
     * @return string full URL
     * @author Naoki Sawada
     */
    public static function getConnectorUrl()
    {
        $https = (isset($_SERVER['HTTPS']) && 'off' !== mb_strtolower($_SERVER['HTTPS']));
        $url = ($https ? 'https://' : 'http://')
            . $_SERVER['SERVER_NAME']                                              // host
            . (((!$https && 80 == $_SERVER['SERVER_PORT']) || ($https && 443 == $_SERVER['SERVER_PORT'])) ? '' : (':' . $_SERVER['SERVER_PORT']))  // port
            . $_SERVER['REQUEST_URI'];                                             // path & query
        list($url) = explode('?', $url);

        return $url;
    }

    /**
     * Get stream resource pointer by URL
     *
     * @param array $data array('target'=>'URL', 'headers' => array())
     * @param int   $redirectLimit
     *
     * @return resource|boolean
     * @author Naoki Sawada
     */
    public static function getStreamByUrl($data, $redirectLimit = 5)
    {
        if (isset($data['target'])) {
            $data = [
                'cnt' => 0,
                'url' => $data['target'],
                'headers' => isset($data['headers']) ? $data['headers'] : [],
                'cookies' => [],
            ];
        }
        if ($data['cnt'] > $redirectLimit) {
            return false;
        }
        $dlurl = $data['url'];
        $data['url'] = '';
        $headers = $data['headers'];

        if ($dlurl) {
            $url = parse_url($dlurl);
            $ports = [
                'http' => '80',
                'https' => '443',
                'ftp' => '21',
            ];
            $url['scheme'] = mb_strtolower($url['scheme']);
            if (!isset($url['port']) && isset($ports[$url['scheme']])) {
                $url['port'] = $ports[$url['scheme']];
            }
            if (!isset($url['port'])) {
                return false;
            }
            $cookies = [];
            if ($data['cookies']) {
                foreach ($data['cookies'] as $d => $c) {
                    if (false !== mb_strpos($url['host'], $d)) {
                        $cookies[] = $c;
                    }
                }
            }

            $transport = ('https' === $url['scheme']) ? 'tls' : 'tcp';
            $query = isset($url['query']) ? '?' . $url['query'] : '';
            $stream = stream_socket_client($transport . '://' . $url['host'] . ':' . $url['port']);
            stream_set_timeout($stream, 300);
            fwrite($stream, "GET {$url['path']}{$query} HTTP/1.1\r\n");
            fwrite($stream, "Host: {$url['host']}\r\n");
            foreach ($headers as $header) {
                fwrite($stream, trim($header, "\r\n") . "\r\n");
            }
            fwrite($stream, "Connection: Close\r\n");
            if ($cookies) {
                fwrite($stream, 'Cookie: ' . implode('; ', $cookies) . "\r\n");
            }
            fwrite($stream, "\r\n");
            while ('' !== ($res = trim(fgets($stream)))) {
                // find redirect
                if (preg_match('/^Location: (.+)$/', $res, $m)) {
                    $data['url'] = $m[1];
                }
                // fetch cookie
                if (0 === mb_strpos($res, 'Set-Cookie:')) {
                    $domain = $url['host'];
                    if (preg_match('/^Set-Cookie:(.+)(?:domain=\s*([^ ;]+))?/i', $res, $c1)) {
                        if (!empty($c1[2])) {
                            $domain = trim($c1[2]);
                        }
                        if (preg_match('/([^ ]+=[^;]+)/', $c1[1], $c2)) {
                            $data['cookies'][$domain] = $c2[1];
                        }
                    }
                }
            }
            if ($data['url']) {
                ++$data['cnt'];
                fclose($stream);

                return self::getStreamByUrl($data, $redirectLimit);
            }

            return $stream;
        }

        return false;
    }

    /**
     * Gets the fetch cookie file for curl.
     *
     * @return string  The fetch cookie file.
     */
    public function getFetchCookieFile()
    {
        $file = '';
        if ($tmpDir = $this->getTempDir()) {
            $file = $tmpDir . '/.elFinderAnonymousCookie';
        }

        return $file;
    }

    /**
     * Call curl_exec() with supported redirect on `safe_mode` or `open_basedir`
     *
     * @param resource $curl
     * @param array    $options
     * @param array    $headers
     *
     * @throws \Exception
     * @return mixed
     * @author Naoki Sawada
     */
    public static function curlExec($curl, $options = [], $headers = [])
    {
        $followLocation = (!ini_get('safe_mode') && !ini_get('open_basedir'));
        if ($followLocation) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($options) {
            curl_setopt_array($curl, $options);
        }

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($curl);

        if (!$followLocation && $redirect = curl_getinfo($curl, CURLINFO_REDIRECT_URL)) {
            if ($stream = self::getStreamByUrl(['target' => $redirect, 'headers' => $headers])) {
                $result = stream_get_contents($stream);
            }
        }

        if (false === $result) {
            if (curl_errno($curl)) {
                throw new \Exception('curl_exec() failed: ' . curl_error($curl));
            }
            throw new \Exception('curl_exec(): empty response');
        }

        curl_close($curl);

        return $result;
    }

    /**
     * Return bool that current request was aborted by client side
     *
     * @return boolean
     */
    public static function aborted()
    {
        if ($file = self::$abortCheckFile) {
            (version_compare(PHP_VERSION, '5.3.0') >= 0) ? clearstatcache(true, $file) : clearstatcache();
            if (!is_file($file)) {
                // GC (expire 12h)
                list($ptn) = explode('elfreq', $file);
                self::GlobGC($ptn . 'elfreq*', 43200);

                return true;
            }
        }

        return false;
    }

    /**
     * Return array ["name without extention", "extention"] by filename
     *
     * @param string $name
     *
     * @return array
     */
    public static function splitFileExtention($name)
    {
        if (preg_match('/^(.+?)?\.((?:tar\.(?:gz|bz|bz2|z|lzo))|cpio\.gz|ps\.gz|xcf\.(?:gz|bz2)|[a-z0-9]{1,10})$/i', $name, $m)) {
            return [(string)$m[1], $m[2]];
        }

        return [$name, ''];
    }

    /**
     * Gets the memory size by imageinfo.
     *
     * @param      array $imgInfo array that result of getimagesize()
     *
     * @return     int  The memory size by imageinfo.
     */
    public static function getMemorySizeByImageInfo($imgInfo)
    {
        $width = $imgInfo[0];
        $height = $imgInfo[1];
        $bits = isset($imgInfo['bits']) ? $imgInfo['bits'] : 24;
        $channels = isset($imgInfo['channels']) ? $imgInfo['channels'] : 3;

        return round(($width * $height * $bits * $channels / 8 + pow(2, 16)) * 1.65);
    }

    /**
     * Auto expand memory for GD processing
     *
     * @param      array $imgInfos The image infos
     */
    public static function expandMemoryForGD($imgInfos)
    {
        if (0 != self::$memoryLimitGD && $imgInfos && is_array($imgInfos)) {
            if (!is_array($imgInfos[0])) {
                $imgInfos = [$imgInfos];
            }
            $limit = self::getIniBytes('', self::$memoryLimitGD);
            $memLimit = self::getIniBytes('memory_limit');
            $needs = 0;
            foreach ($imgInfos as $info) {
                $needs += self::getMemorySizeByImageInfo($info);
            }
            $needs += memory_get_usage();
            if ($needs > $memLimit && (-1 == $limit || $limit > $needs)) {
                ini_set('memory_limit', $needs);
            }
        }
    }

    /***************************************************************************/
    /*                                 callbacks                               */
    /***************************************************************************/

    /**
     * Get command name of binded "commandName.subName"
     *
     * @param string $cmd
     *
     * @return string
     */
    protected static function getCmdOfBind($cmd)
    {
        list($ret) = explode('.', $cmd);

        return trim($ret);
    }

    /**
     * Add subName to commandName
     *
     * @param string $cmd
     * @param string $sub
     *
     * @return string
     */
    protected static function addSubToBindName($cmd, $sub)
    {
        return $cmd . '.' . trim($sub);
    }

    /**
     * Remove a file if connection is disconnected
     *
     * @param string $file
     */
    public static function rmFileInDisconnected($file)
    {
        (connection_aborted() || CONNECTION_NORMAL !== connection_status()) && is_file($file) && unlink($file);
    }

    /**
     * Call back function on shutdown
     *  - delete files in $GLOBALS['elFinderTempFiles']
     */
    public static function onShutdown()
    {
        self::$abortCheckFile = null;
        if (!empty($GLOBALS['elFinderTempFiles'])) {
            foreach (array_keys($GLOBALS['elFinderTempFiles']) as $f) {
                is_file($f) && unlink($f);
            }
        }
    }

    /**
     * Garbage collection with glob
     *
     * @param string  $pattern
     * @param int $time
     */
    public static function GlobGC($pattern, $time)
    {
        $now = time();
        foreach (glob($pattern) as $file) {
            (filemtime($file) < ($now - $time)) && unlink($file);
        }
    }
} // END class

/**
 * Custom exception class for aborting request
 */
class elFinderAbortException extends Exception
{
}
