<?php

namespace dacoto\LaravelWizardInstaller;

use Illuminate\Support\Facades\File;

class InstallHelper
{
    public static function checkPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, '8', '>');
    }

    public static function checkPdo(): bool
    {
        return extension_loaded('pdo_mysql');
    }

    public static function checkMbstring(): bool
    {
        return extension_loaded('mbstring');
    }

    public static function checkFileinfo(): bool
    {
        return extension_loaded('fileinfo');
    }

    public static function checkOpenssl(): bool
    {
        return extension_loaded('openssl');
    }

    public static function checkTokenizer(): bool
    {
        return extension_loaded('tokenizer');
    }

    public static function checkJson(): bool
    {
        return extension_loaded('json');
    }

    public static function checkCurl(): bool
    {
        return extension_loaded('curl');
    }

    public static function checkZip(): bool
    {
        return extension_loaded('zip');
    }

    public static function checkStorageFramework(): bool
    {
        return (int)File::chmod(base_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework') >= 755;
    }

    public static function checkStorageLogs(): bool
    {
        return (int)File::chmod(base_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs') >= 755;
    }

    public static function checkBootstrapCache(): bool
    {
        return (int)File::chmod(base_path() . DIRECTORY_SEPARATOR . 'bootstrap/cache') >= 755;
    }
}
