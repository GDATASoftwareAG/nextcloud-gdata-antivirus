#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

BRANCH_NAME=${1:-php-connection-gets-lost-when-idle-after-connectasdfs}
GIT_REPO=${2:-git@github.com:GDATASoftwareAG/vaas.git}

if git ls-remote --heads $GIT_REPO refs/heads/$BRANCH_NAME; then
  echo "Branch $BRANCH_NAME exists in $GIT_REPO"
else
    echo "Branch $BRANCH_NAME does not exist in $GIT_REPO"
    exit 1
fi

git clone --no-checkout --depth 1 --recurse-submodules --single-branch --branch $BRANCH_NAME $GIT_REPO ./gdata
cd ./gdata
git sparse-checkout init
git sparse-checkout set ./php
git checkout
cd -

rm -rf composer.lock
rm -rf vendor/

mv composer.json composer.packagist.vaas.json
if [ -f composer.local.vaas.json ]; then
    jq -s '.[0] * .[1]' composer.packagist.vaas.json composer.local.vaas.json > composer.json
fi
