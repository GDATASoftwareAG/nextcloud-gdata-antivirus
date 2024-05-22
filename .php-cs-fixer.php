<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'node_modules',
        'build'
    ])
;

return (new PhpCsFixer\Config())
    ->setFinder($finder)
;