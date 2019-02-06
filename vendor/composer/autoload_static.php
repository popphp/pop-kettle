<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit86c83a7e2b56c86229f55bf5b8a6703e
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Pop\\Kettle\\' => 11,
            'Pop\\Db\\' => 7,
            'Pop\\Console\\' => 12,
            'Pop\\Code\\' => 9,
            'Pop\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Pop\\Kettle\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Pop\\Db\\' => 
        array (
            0 => __DIR__ . '/..' . '/popphp/pop-db/src',
        ),
        'Pop\\Console\\' => 
        array (
            0 => __DIR__ . '/..' . '/popphp/pop-console/src',
        ),
        'Pop\\Code\\' => 
        array (
            0 => __DIR__ . '/..' . '/popphp/pop-code/src',
        ),
        'Pop\\' => 
        array (
            0 => __DIR__ . '/..' . '/popphp/popphp/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit86c83a7e2b56c86229f55bf5b8a6703e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit86c83a7e2b56c86229f55bf5b8a6703e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
