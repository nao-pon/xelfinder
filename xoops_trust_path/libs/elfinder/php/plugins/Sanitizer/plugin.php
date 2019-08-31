<?php

/**
 * elFinder Plugin Sanitizer
 * Sanitizer of file-name and file-path etc.
 * ex. binding, configure on connector options
 *    $opts = array(
 *        'bind' => array(
 *            'upload.pre mkdir.pre mkfile.pre rename.pre archive.pre ls.pre' => array(
 *                'Plugin.Sanitizer.cmdPreprocess'
 *            ),
 *            'upload.presave' => array(
 *                'Plugin.Sanitizer.onUpLoadPreSave'
 *            )
 *        ),
 *        // global configure (optional)
 *        'plugin' => array(
 *            'Sanitizer' => array(
 *                'enable' => true,
 *                'targets'  => array('\\','/',':','*','?','"','<','>','|'), // target chars
 *                'replace'  => '_', // replace to this
 *                'callBack' => null // Or @callable sanitize function
 *            )
 *        ),
 *        // each volume configure (optional)
 *        'roots' => array(
 *            array(
 *                'driver' => 'LocalFileSystem',
 *                'path'   => '/path/to/files/',
 *                'URL'    => 'http://localhost/to/files/'
 *                'plugin' => array(
 *                    'Sanitizer' => array(
 *                        'enable' => true,
 *                        'targets'  => array('\\','/',':','*','?','"','<','>','|'), // target chars
 *                        'replace'  => '_', // replace to this
 *                        'callBack' => null // Or @callable sanitize function
 *                    )
 *                )
 *            )
 *        )
 *    );
 *
 * @package elfinder
 * @author  Naoki Sawada
 * @license New BSD
 */
class elFinderPluginSanitizer extends elFinderPlugin
{
    private $replaced = [];
    private $keyMap = [
        'ls' => 'intersect',
        'upload' => 'renames',
        'mkdir' => ['name', 'dirs'],
    ];

    public function __construct($opts)
    {
        $defaults = [
            'enable' => true,  // For control by volume driver
            'targets' => ['\\', '/', ':', '*', '?', '"', '<', '>', '|'], // target chars
            'replace' => '_',   // replace to this
            'callBack' => null,   // Or callable sanitize function
        ];
        $this->opts = array_merge($defaults, $opts);
    }

    public function cmdPreprocess($cmd, &$args, $elfinder, $volume)
    {
        $opts = $this->getCurrentOpts($volume);
        if (!$opts['enable']) {
            return false;
        }
        $this->replaced[$cmd] = [];
        $key = (isset($this->keyMap[$cmd])) ? $this->keyMap[$cmd] : 'name';

        if (is_array($key)) {
            $keys = $key;
        } else {
            $keys = [$key];
        }
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                if (is_array($args[$key])) {
                    foreach ($args[$key] as $i => $name) {
                        if ('mkdir' === $cmd && 'dirs' === $key) {
                            // $name need '/' as prefix see #2607
                            $name = '/' . ltrim($name, '/');
                            $_names = explode('/', $name);
                            $_res = [];
                            foreach ($_names as $_name) {
                                $_res[] = $this->sanitizeFileName($_name, $opts);
                            }
                            $this->replaced[$cmd][$name] = $args[$key][$i] = implode('/', $_res);
                        } else {
                            $this->replaced[$cmd][$name] = $args[$key][$i] = $this->sanitizeFileName($name, $opts);
                        }
                    }
                } elseif ('' !== $args[$key]) {
                    $name = $args[$key];
                    $this->replaced[$cmd][$name] = $args[$key] = $this->sanitizeFileName($name, $opts);
                }
            }
        }
        if ('ls' === $cmd || 'mkdir' === $cmd) {
            if (!empty($this->replaced[$cmd])) {
                // un-regist for legacy settings
                $elfinder->unbind($cmd, [$this, 'cmdPostprocess']);
                $elfinder->bind($cmd, [$this, 'cmdPostprocess']);
            }
        }

        return true;
    }

    public function cmdPostprocess($cmd, &$result, $args, $elfinder, $volume)
    {
        if ('ls' === $cmd) {
            if (!empty($result['list']) && !empty($this->replaced['ls'])) {
                foreach ($result['list'] as $hash => $name) {
                    if ($keys = array_keys($this->replaced['ls'], $name)) {
                        if (1 === count($keys)) {
                            $result['list'][$hash] = $keys[0];
                        } else {
                            $result['list'][$hash] = $keys;
                        }
                    }
                }
            }
        } elseif ('mkdir' === $cmd) {
            if (!empty($result['hashes']) && !empty($this->replaced['mkdir'])) {
                foreach ($result['hashes'] as $name => $hash) {
                    if ($keys = array_keys($this->replaced['mkdir'], $name)) {
                        $result['hashes'][$keys[0]] = $hash;
                    }
                }
            }
        }
    }

    // NOTE: $thash is directory hash so it unneed to process at here
    public function onUpLoadPreSave(&$thash, &$name, $src, $elfinder, $volume)
    {
        $opts = $this->getCurrentOpts($volume);
        if (!$opts['enable']) {
            return false;
        }
        $name = $this->sanitizeFileName($name, $opts);

        return true;
    }

    protected function sanitizeFileName($filename, $opts)
    {
        if (!empty($opts['callBack']) && is_callable($opts['callBack'])) {
            return call_user_func_array($opts['callBack'], [$filename, $opts]);
        }

        return str_replace($opts['targets'], $opts['replace'], $filename);
    }
}
