<?php
require XOOPS_TRUST_PATH . '/libs/elfinder/php/elFinderSessionInterface.php';
require XOOPS_TRUST_PATH . '/libs/elfinder/php/elFinderSession.php';

class xelFinderSession extends elFinderSession
{
    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if (version_compare(PHP_VERSION, '5.3', '<')) {
            if (!session_id()) {
                @session_start();
            }
        } else {
            @session_start();
        }
        $this->started = session_id() ? true : false;

        return $this;
    }
}
