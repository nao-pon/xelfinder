<?php
require XOOPS_TRUST_PATH . '/libs/elfinder/php/elFinderSessionInterface.php';
require XOOPS_TRUST_PATH . '/libs/elfinder/php/elFinderSession.php';

class xelFinderSession extends elFinderSession
{
    /**
     * {@inheritdoc}
     */
    public function __construct($opts)
    {
        parent::__construct($opts);
        $this->fixCookieRegist = false;
        return $this;
    }
}
