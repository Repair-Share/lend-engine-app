<?php

namespace AppBundle\Helpers;

class UnitTestHelper
{
    public static function isUnitTestEnvironment()
    {
        return getenv('APP_ENV') === 'test';
    }

    public static function isCommandLine()
    {
        return php_sapi_name() === 'cli';
    }
}