<?php

use Nadi\Shipper\Exceptions\ShipperException;
use Nadi\WordPress\Shipper;

if (! function_exists('sendNadiLog')) {
    function sendNadiLog()
    {
        try {
            Shipper::sendRecords();
        } catch (ShipperException $e) {
            error_log('[Nadi Shipper] Failed to send logs: '.$e->getMessage());
        }
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
        $lockPath = nadiLockPath();
        if (file_exists($lockPath)) {
            unlink($lockPath);
        }
    }
}

if (! function_exists('sendNadiLogHandler')) {
    function sendNadiLogHandler()
    {
        if (! isNadiLockFileExists()) {
            createNadiLockFile();
            try {
                sendNadiLog();
            } finally {
                deleteNadiLockFile();
            }
        } else {
            error_log('[Nadi Shipper] Shipper is already running, skipping this execution.');
        }
    }
}

if (! function_exists('nadiLockPath')) {
    function nadiLockPath()
    {
        return dirname(__DIR__).'/log/nadi.lock';
    }
}
