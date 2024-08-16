#!/bin/bash

rm -rf composer.lock
rm -rf vendor/

if [ -f composer.packagist.vaas.json ]; then
    mv composer.packagist.vaas.json composer.json
fi