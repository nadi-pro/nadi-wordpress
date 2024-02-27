<?php

use Nadi\WordPress\Shipper;

if (! function_exists('sendNadiLog')) {
    function sendNadiLog()
    {
        Shipper::send();
    }
}

if (! function_exists('isNadiLockFileExists')) {
    function isNadiLockFileExists()
    {
        return file_exists(nadiLockPath());
    }
}

if (! function_exists('createNadiLockFile')) {
    function createNadiLockFile()
    {
        touch(nadiLockPath());
    }
}

if (! function_exists('deleteNadiLockFile')) {
    function deleteNadiLockFile()
    {
        unlink(nadiLockPath());
    }
}

if (! function_exists('sendNadiLogHandler')) {
    function sendNadiLogHandler()
    {
        if (! isNadiLockFileExists()) {
            createNadiLockFile();
            sendNadiLog();
            deleteNadiLockFile();
        } else {
            error_log('Nadi Shipper is already running.');
        }
    }
}

if (! function_exists('nadiLockPath')) {
    function nadiLockPath()
    {
        return dirname(dirname(__FILE__)).'/log/nadi.lock';
    }
}
