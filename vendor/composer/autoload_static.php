<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit44e11d3eaba8dac12302900a4950c6bc
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'Jdvorak23\\Bootstrap5FormRenderer\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Jdvorak23\\Bootstrap5FormRenderer\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit44e11d3eaba8dac12302900a4950c6bc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit44e11d3eaba8dac12302900a4950c6bc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit44e11d3eaba8dac12302900a4950c6bc::$classMap;

        }, null, ClassLoader::class);
    }
}
