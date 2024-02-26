<?php

namespace Nadi\WordPress;

use Exception as BaseException;

class Exception extends BaseException
{
    public static function missingConfigFile()
    {
        throw new self('Missing Nadi Configuration file');
    }
}
