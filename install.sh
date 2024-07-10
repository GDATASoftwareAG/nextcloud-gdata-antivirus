#!/bin/bash

NEXTCLOUD_VERSION=${1:-29}

source .env-local || echo "No .env-local file found."

setup_nextcloud () {
  echo "setup nextcloud"
  docker stop nextcloud-container || echo "No container to stop"
  sleep 1
  docker run --quiet -d --name nextcloud-container --rm --publish 80:80 nextcloud:$NEXTCLOUD_VERSION

  until docker exec --user www-data -i nextcloud-container php occ status | grep "installed: false"
  do
    echo "waiting for nextcloud to be initialized"
    sleep 2
  done

  echo "copy config for empty skeleton"
  docker cp ./empty-skeleton.config.php nextcloud-container:/var/www/html/config/config.php
  docker exec -i nextcloud-container chown www-data:www-data /var/www/html/config/config.php

  until docker exec --user www-data -i nextcloud-container php occ maintenance:install --admin-user=admin --admin-pass=admin | grep "Nextcloud was successfully installed"
  do
    echo "waiting for installation to finish"
    sleep 2
  done

  docker exec --user www-data -i nextcloud-container php occ log:manage --level DEBUG
  docker exec --user www-data -i nextcloud-container php occ app:disable firstrunwizard

  echo "setup nextcloud finished"
}

build_app () {
  echo "build app"
  make appstore
  tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts
  echo "build app finished"
}

if [  -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
  echo "Please set CLIENT_ID and CLIENT_SECRET"
  exit 1
fi

setup_nextcloud &
build_app &
wait

docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/
docker exec -i nextcloud-container chown -R www-data:www-data /var/www/html/apps/gdatavaas

until docker exec --user www-data -i nextcloud-container php occ app:enable gdatavaas
do
  echo "Trying app enable"
  sleep 2
done

docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientId --value="$CLIENT_ID"
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas authMethod --value=ClientCredentials
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas autoScanFiles --value=true
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas scanQueueLength --value=100

source install.local || echo "No additional install script found."

# has to be done, to get the dev-requirements installed again
composer install