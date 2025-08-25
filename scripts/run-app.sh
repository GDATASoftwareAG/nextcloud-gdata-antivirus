#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

set -e

export NEXTCLOUD_VERSION=${1:-31.0.8}
export IS_CI=${2:-0}

if [ "$IS_CI" -eq 0 ]; then
  make oc
  make local
  exit 0
fi

source .env || echo "No .env file found."

setup_nextcloud () {
  docker stop nextcloud-container || true
  docker container rm nextcloud-container || true
  docker compose -f docker-compose.yaml kill
  docker compose -f docker-compose.yaml rm --force --stop --volumes
  docker compose -f docker-compose.yaml up --build --quiet-pull --wait -d --force-recreate --renew-anon-volumes --remove-orphans

  until docker exec --user www-data -i nextcloud-container php occ status | grep "installed: false"
  do
    echo "Waiting for Nextcloud to be initialized..."
    sleep 2
  done

  echo "copy config for empty skeleton"
  docker cp ./empty-skeleton.config.php nextcloud-container:/var/www/html/config/config.php
  docker exec -i nextcloud-container chown www-data:www-data /var/www/html/config/config.php

  until docker exec --user www-data -i nextcloud-container php occ maintenance:install --admin-user=admin --admin-pass=admin | grep "Nextcloud was successfully installed"
  do
    echo "Waiting for installation to finish..."
    sleep 2
  done

  docker exec --user www-data -i nextcloud-container php occ log:manage --level DEBUG
  docker exec --user www-data -i nextcloud-container php occ app:disable firstrunwizard
  docker exec --user www-data -i nextcloud-container php occ app:disable weather_status
  docker exec --user www-data -i nextcloud-container php occ config:system:set trusted_domains 3 --value=nextcloud-container

  echo "Setup Nextcloud finished."
}

build_app () {
  echo "Building G DATA Antivirus App for Nextcloud..."
  make distclean
  make appstore
  tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts
  echo "Building G DATA Antivirus App for Nextcloud finished."
}

if [  -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
  echo "Please set environment variables CLIENT_ID and CLIENT_SECRET."
  exit 1
fi

setup_nextcloud &
build_app &
wait %2 || exit 1
wait %1 || exit 1

docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/
docker exec -i nextcloud-container chown -R www-data:www-data /var/www/html/apps/gdatavaas

until docker exec --user www-data -i nextcloud-container php occ app:enable gdatavaas
do
  echo "Trying to enable G DATA Antivirus App for Nextcloud..."
  sleep 2
done
echo "G DATA Antivirus App for Nextcloud enabled."

# Configure the app for scanning
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientId --value="$CLIENT_ID"
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas clientSecret --value="$CLIENT_SECRET"
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas authMethod --value=ClientCredentials
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas autoScanFiles --value=true

# Configure Nextcloud to send emails
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas notifyMails --value="test@example.com"
docker exec --user www-data -i nextcloud-container php occ config:app:set gdatavaas sendMailOnVirusUpload --value=true
docker exec --user www-data -i nextcloud-container php occ config:system:set mail_smtpmode --value="smtp"
docker exec --user www-data -i nextcloud-container php occ config:system:set mail_smtphost --value="smtp"
docker exec --user www-data -i nextcloud-container php occ config:system:set mail_smtpport --value="25"
docker exec --user www-data -i nextcloud-container php occ config:system:set mail_from_address --value="test@example.com"
docker exec --user www-data -i nextcloud-container php occ config:system:set mail_domain --value="example.com"
docker exec --user www-data -i nextcloud-container php occ user:setting admin settings email test@example.com

composer install

echo
echo "Nextcloud setup and G DATA Antivirus App installation completed successfully."
echo "Visit http://localhost:8080 to access your Nextcloud instance."
