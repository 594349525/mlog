<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitac074a6f5b7b2f1bbdb6fe9a721ee8d7
{
    public static $prefixLengthsPsr4 = array (
        'X' => 
        array (
            'Xiangxin\\Logger\\' => 16,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Xiangxin\\Logger\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitac074a6f5b7b2f1bbdb6fe9a721ee8d7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitac074a6f5b7b2f1bbdb6fe9a721ee8d7::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
