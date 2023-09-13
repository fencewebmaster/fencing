<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd967a00b70e644ed253f0863b20a96ea
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd967a00b70e644ed253f0863b20a96ea::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd967a00b70e644ed253f0863b20a96ea::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd967a00b70e644ed253f0863b20a96ea::$classMap;

        }, null, ClassLoader::class);
    }
}
