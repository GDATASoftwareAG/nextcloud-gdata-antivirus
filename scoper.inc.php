<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'OCA\\GDataVaas\\Vendor',

//    'exclude-namespaces' => [
//        'League',
//        'PSR',
//        'Ramsey',
//        'Revolt',
//        'Websocket'
//    ],

//    'exclude-classes' => [
//        'JsonMapper',
//        'JsonMapper_Exception'
//    ],

    'finders' => [
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/amphp/amp'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/amphp/http'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/amphp/http-client'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/amphp/file'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/amphp/byte-stream'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/netresearch'),
        Finder::create()->files()
            ->exclude([
                'test',
                'composer',
                'bin',
            ])
            ->notName('autoload.php')
            ->in('vendor/gdata'),
    ],
];