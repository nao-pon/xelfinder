<?php

/**
 * elFinder Plugin Abstract
 *
 * @package elfinder
 * @author  Naoki Sawada
 * @license New BSD
 */
class elFinderPlugin
{
    /**
     * This plugin's options
     *
     * @var array
     */
    protected $opts = [];

    /**
     * Get current volume's options
     *
     * @param object $volume
     *
     * @return array options
     */
    protected function getCurrentOpts($volume)
    {
        $name = mb_substr(get_class($this), 14); // remove "elFinderPlugin"
        $opts = $this->opts;
        if (is_object($volume)) {
            $volOpts = $volume->getOptionsPlugin($name);
            if (is_array($volOpts)) {
                $opts = array_merge($opts, $volOpts);
            }
        }

        return $opts;
    }

    /**
     * Is enabled with options
     *
     * @param array    $opts
     * @param elFinder $elfinder
     *
     * @return boolean
     */
    protected function iaEnabled($opts, $elfinder = null)
    {
        if (!$opts['enable']) {
            return false;
        }

        // check post var 'contentSaveId' to disable this plugin
        if ($elfinder && !empty($opts['disableWithContentSaveId'])) {
            $session = $elfinder->getSession();
            $urlContentSaveIds = $session->get('urlContentSaveIds', []);
            if (!empty(elFinder::$currentArgs['contentSaveId']) && ($contentSaveId = elFinder::$currentArgs['contentSaveId'])) {
                if (!empty($urlContentSaveIds[$contentSaveId])) {
                    $elfinder->removeUrlContentSaveId($contentSaveId);

                    return false;
                }
            }
        }

        if (isset($opts['onDropWith']) && null !== $opts['onDropWith']) {
            // plugin disabled by default, enabled only if given key is pressed
            if (isset($_REQUEST['dropWith']) && $_REQUEST['dropWith']) {
                $onDropWith = $opts['onDropWith'];
                $action = (int)$_REQUEST['dropWith'];
                if (!is_array($onDropWith)) {
                    $onDropWith = [$onDropWith];
                }
                foreach ($onDropWith as $key) {
                    $key = (int)$key;
                    if (($action & $key) === $key) {
                        return true;
                    }
                }
            }

            return false;
        }

        if (isset($opts['offDropWith']) && null !== $opts['offDropWith'] && isset($_REQUEST['dropWith'])) {
            // plugin enabled by default, disabled only if given key is pressed
            $offDropWith = $opts['offDropWith'];
            $action = (int)$_REQUEST['dropWith'];
            if (!is_array($offDropWith)) {
                $offDropWith = [$offDropWith];
            }
            $res = true;
            foreach ($offDropWith as $key) {
                $key = (int)$key;
                if (0 === $key) {
                    if (0 === $action) {
                        $res = false;
                        break;
                    }
                } else {
                    if (($action & $key) === $key) {
                        $res = false;
                        break;
                    }
                }
            }
            if (!$res) {
                return false;
            }
        }

        return true;
    }
}
