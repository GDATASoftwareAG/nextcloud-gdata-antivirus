#!/bin/bash

docker stop nextcloud-container
docker run -d --name nextcloud-container --rm --publish 80:80 --publish 8080:8080 --publish 8443:8443 nextcloud:stable
echo "Waiting for sunrise..."
sleep 15
docker exec --user www-data -it nextcloud-container php occ maintenance:install --admin-user=admin --admin-pass=admin
make appstore
tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts
docker exec --user www-data -it nextcloud-container mkdir apps/gdatavaas
docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/gdatavaas
docker exec --user www-data -it nextcloud-container php occ app:update --all
docker exec --user www-data -it nextcloud-container php occ app:enable gdatavaas

docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas username --value=vaas-integration-test

docker exec --user www-data -it nextcloud-container php cron.php
