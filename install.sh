#!/bin/bash

source .env-local || echo "No .env-local file found."

if [  -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
  echo "Please set CLIENT_ID and CLIENT_SECRET"
  exit 1
fi

docker stop nextcloud-container || echo "No container to stop"
sleep 1
docker run -d --name nextcloud-container --rm --publish 80:80 nextcloud:28

until docker exec --user www-data -it nextcloud-container php occ maintenance:install --admin-user=admin --admin-pass=admin >/dev/null
do
  echo "Trying installation"
  sleep 2
done

make appstore
tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts
docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/
docker exec -it nextcloud-container chown -R www-data:www-data /var/www/html/apps/gdatavaas

until docker exec --user www-data -it nextcloud-container php occ app:update --all
do
  echo "Trying app update"
  sleep 2
done

docker exec --user www-data -it nextcloud-container php occ app:enable gdatavaas

docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas clientId --value="$CLIENT_ID"
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas authMethod --value=ClientCredentials
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas autoScanFiles --value=true
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas scanQueueLength --value=100

docker exec --user www-data -it nextcloud-container php occ log:manage --level DEBUG
docker exec --user www-data -it nextcloud-container php occ app:disable firstrunwizard

source install.local || echo "No additional install script found."
# docker exec --user www-data -it nextcloud-container php cron.php
