#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

rm -rf nextcloud-server/
export NEXTCLOUD_VERSION=${1:-32.0.0}
git clone --depth 1 --recurse-submodules --single-branch --branch v"$NEXTCLOUD_VERSION" https://github.com/nextcloud/server.git ./nextcloud-server
cd nextcloud-server || exit 1
git submodule update --init
cd - || exit 1
