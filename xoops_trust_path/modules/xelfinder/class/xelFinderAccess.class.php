<?php

class xelFinderAccess
{
    private $readRegexs = [];
    private $writeRegexs = [];
    private $hiddenRegexs = [];
    private $unlockRegexs = [];
    private $readDirRegexs = [];
    private $writeDirRegexs = [];
    private $hiddenDirRegexs = [];
    private $unlockDirRegexs = [];

    public function setReadExtention($str)
    {
        $regs = $this->makeRegexExtention($str);
        $this->readRegexs[] = $regs[0];
        $this->readDirRegexs[] = $regs[1];
    }

    public function setWriteExtention($str)
    {
        $regs = $this->makeRegexExtention($str);
        $this->writeRegexs[] = $regs[0];
        $this->writeDirRegexs[] = $regs[1];
    }

    public function setUnlockExtention($str)
    {
        $regs = $this->makeRegexExtention($str);
        $this->unlockRegexs[] = $regs[0];
        $this->unlockDirRegexs[] = $regs[1];
    }

    public function setHiddenExtention($str)
    {
        $regs = $this->makeRegexExtention($str);
        $this->hiddenRegexs[] = $regs[0];
        $this->hiddenDirRegexs[] = $regs[1];
    }

    private function makeRegexExtention($str)
    {
        $str = trim($str, ' ,');
        if ($str) {
            $_exts = $_dirs = [];
            //$str = preg_quote($str, '/');
            $exts = array_map('trim', explode(',', $str));
            foreach ($exts as $ext) {
                if ('' === $ext) {
                    continue;
                }
                if ('/' === mb_substr($ext, -1)) {
                    if ('/' === $ext) {
                        $_dirs[] = '(?=)';
                    } else {
                        $_dirs[] = preg_quote(rtrim($ext, '/'), '/');
                    }
                } else {
                    $_exts[] = preg_quote($ext, '/');
                }
            }
            $extReg = $_exts ? '/(?:' . implode('|', $_exts) . ')$/i' : '/(?!)/';
            $dirReg = $_dirs ? '/(?:' . implode('|', $_dirs) . ')$/i' : '/(?!)/';
        } else {
            $extReg = $dirReg = '/(?!)/';
        }

        return [$extReg, $dirReg];
    }

    private function regMatch($regs, $str)
    {
        foreach ($regs as $reg) {
            if (preg_match($reg, $str)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     *
     * @param  string  $attr  attribute name (read|write|locked|hidden)
     * @param  string  $path  file path relative to volume root directory started with directory separator
     * @param mixed $data
     * @param mixed $volume
     * @param mixed $isDir
     * @return bool|null
     **/
    public function access($attr, $path, $data, $volume, $isDir)
    {
        switch ($attr) {
            case 'read':
                if ($this->readRegexs) {
                    return $this->regMatch($isDir ? $this->readDirRegexs : $this->readRegexs, basename($path));
                }
                break;
            case 'write':
                if ($this->writeRegexs) {
                    return $this->regMatch($isDir ? $this->writeDirRegexs : $this->writeRegexs, basename($path));
                }
                break;
            case 'hidden':
                if ($this->hiddenRegexs) {
                    return $this->regMatch($isDir ? $this->hiddenDirRegexs : $this->hiddenRegexs, basename($path));
                }
                break;
            case 'locked':
                if ($this->unlockRegexs) {
                    return !$this->regMatch($isDir ? $this->unlockDirRegexs : $this->unlockRegexs, basename($path));
                }
                break;
        }

        return null;
    }
}
