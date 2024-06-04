<?php

require_once './vendor/autoload.php';

use Nextcloud\CodingStandard\Config;


$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'node_modules',
        'build'
    ])
;

return (new Config())
    ->setFinder($finder)
;