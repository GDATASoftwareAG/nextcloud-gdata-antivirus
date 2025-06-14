#!/bin/bash
set -e

export NEXTCLOUD_VERSION=${1:-31.0.5}
export INSTALL_XDEBUG=${2:-1}
export XDEBUG_MODE=${XDEBUG_MODE:-develop}

source .env-local || echo "No .env-local file found."

setup_nextcloud () {
  echo "setup nextcloud"
  docker compose -f compose-install.yaml kill
  docker compose -f compose-install.yaml rm --force --stop --volumes
  docker compose -f compose-install.yaml up --build --quiet-pull --wait -d --force-recreate --renew-anon-volumes --remove-orphans

  docker exec -i nextcloud-container bash -c 'ulimit -c unlimited'
  docker exec -i nextcloud-container bash -c 'echo "/tmp/apache2-coredump/core-%e-%s-%u-%g-%p-%t" > /proc/sys/kernel/core_pattern'

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
  docker exec --user www-data -i nextcloud-container php occ app:disable weather_status
  docker exec --user www-data -i nextcloud-container php occ config:system:set trusted_domains 2 --value=192.168.5.80
  docker exec --user www-data -i nextcloud-container php occ config:system:set trusted_domains 3 --value=nextcloud-container

  echo "setup nextcloud finished"
}

build_app () {
  echo "build app"
  make distclean
  make appstore
  tar -xf ./build/artifacts/gdatavaas.tar.gz -C ./build/artifacts 
  echo "build app finished"
}

if [  -z "$CLIENT_ID" ] || [ -z "$CLIENT_SECRET" ]; then
  echo "Please set CLIENT_ID and CLIENT_SECRET"
  exit 1
fi

setup_nextcloud &
setup_nextcloud_pid=$!
build_app &
wait %2 || exit 1
wait %1 || exit 1

docker cp ./build/artifacts/gdatavaas nextcloud-container:/var/www/html/apps/
docker exec -i nextcloud-container chown -R www-data:www-data /var/www/html/apps/gdatavaas

until docker exec --user www-data -i nextcloud-container php occ app:enable gdatavaas
do
  echo "Trying app enable"
  sleep 2
done

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

source install.local || echo "No additional install script found."

# Has to be done, to get the dev-requirements installed again
composer install --quiet &

composer info