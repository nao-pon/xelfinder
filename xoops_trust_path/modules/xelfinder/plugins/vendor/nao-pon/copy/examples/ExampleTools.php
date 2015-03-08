<?php

function humanFileSize($size)
{
    if (!$size) {
        return "";
    } elseif (($size >= 1 << 30)) {
        return number_format($size / (1 << 30), 2) . "GB";
    } elseif (($size >= 1 << 20)) {
        return number_format($size / (1 << 20), 2) . "MB";
    } elseif (($size >= 1 << 10)) {
        return number_format($size / (1 << 10),2) . "kB";
    } else {
    return number_format($size) . "B";
    }
}
