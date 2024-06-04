#!/bin/bash

docker stop nextcloud-container
sleep 1
docker run -d --name nextcloud-container --rm --publish 80:80 --publish 8080:8080 --publish 8443:8443 nextcloud:stable
echo "Waiting for sunrise..."

until docker exec --user www-data -it nextcloud-container php occ maintenance:install --admin-user=admin --admin-pass=admin >/dev/null
do
  echo "Try again waiting 2 seconds"
  sleep 2
done

make appstore
tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts
docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/
docker exec -it nextcloud-container chown -R www-data:www-data /var/www/html/apps/gdatavaas
docker exec --user www-data -it nextcloud-container php occ app:update --all
docker exec --user www-data -it nextcloud-container php occ app:enable gdatavaas

docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas clientId --value=$CLIENT_ID
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas clientSecret --value=$CLIENT_SECRET
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas authMethod --value=ClientCredentials
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas autoScanFiles --value=true
docker exec --user www-data -it nextcloud-container php occ config:app:set gdatavaas scanQueueLength --value=100

docker exec --user www-data -it nextcloud-container php occ log:manage --level DEBUG
docker exec --user www-data -it nextcloud-container php occ app:disable firstrunwizard


# docker exec --user www-data -it nextcloud-container php cron.php
