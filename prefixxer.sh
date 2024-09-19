#!/bin/bash

if [ ! "vendor/bin/php-scoper" ]; then
    echo "php-scoper is not installed in vendor. Please run 'composer install' first."
    exit 1
fi

vendor/bin/php-scoper add-prefix --force --output-dir=build/prefixed

rm -rf vendor/amphp
rm -rf vendor/gdata
rm -rf vendor/netresearch

mkdir -p lib/Vendor/Amp/Http
mv build/prefixed/amphp/amp/src/* lib/Vendor/Amp
mv build/prefixed/amphp/http/src/* lib/Vendor/Amp/Http
mv build/prefixed/amphp/http-client/src lib/Vendor/Amp/Http/Client
mv build/prefixed/amphp/file/src lib/Vendor/Amp/File
mv build/prefixed/amphp/byte-stream/src lib/Vendor/Amp/ByteStream
mv build/prefixed/gdata/vaas lib/Vendor/VaasSdk
mv build/prefixed/netresearch/jsonmapper/src/JsonMapper.php lib/Vendor/
mv build/prefixed/netresearch/jsonmapper/src/JsonMapper/Exception.php lib/Vendor/JsonMapper_Exception.php

rm -rf build

composer dump-autoload -o