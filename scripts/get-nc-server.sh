#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

rm -rf nextcloud-server/
if [ -z "${NEXTCLOUD_VERSION:-}" ]; then
	source "$SCRIPT_DIR/../nextcloud.env"
fi

export NEXTCLOUD_VERSION=${1:-${NEXTCLOUD_VERSION}}
git clone --depth 1 --recurse-submodules --single-branch --branch v"$NEXTCLOUD_VERSION" https://github.com/nextcloud/server.git ./nextcloud-server
cd nextcloud-server || exit 1
git submodule update --init
cd - || exit 1
