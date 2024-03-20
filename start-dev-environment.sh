#!/bin/bash

if [ -z "$1" ]; then
    echo "No server branch supplied. Using 28.0.3"
    version="28.0.3"
else
    version=$1
fi

docker run -p 8080:80 -e SERVER_BRANCH=v"$version" -v "$(pwd)":/var/www/html/apps-extra/gdatavaas -v ./dev-environment:/var/www/html ghcr.io/juliushaertl/nextcloud-dev-php82:latest
