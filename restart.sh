#!/bin/bash

docker stop $(docker ps -aq)
docker container rm $(docker ps -aq)
docker volume prune -a -f

make distclean

composer install --no-dev
composer install --no-dev

./prefixxer.sh

./install.sh