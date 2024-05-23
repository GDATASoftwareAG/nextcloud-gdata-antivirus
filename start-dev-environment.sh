#!/bin/bash

if [ -z "$1" ]; then
    echo "No server branch supplied. Using 29.0.0"
    version="29.0.0"
else
    version=$1
fi

if [ -z "$2" ]; then
    echo "No port supplied. Using 8080"
    serverPort="8080"
else
    serverPort=$2
fi

make build && docker run -p "$serverPort":80 -e SERVER_BRANCH=v"$version" -v "$(pwd)":/var/www/html/apps-extra/gdatavaas ghcr.io/juliushaertl/nextcloud-dev-php82:latest
