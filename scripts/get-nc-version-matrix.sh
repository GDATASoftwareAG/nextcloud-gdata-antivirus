#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

MIN_VERSION=$([[ $(cat appinfo/info.xml) =~ $(echo '<nextcloud[^>]*min-version=[^[0-9]]*([0-9]+).*') ]] && echo "${BASH_REMATCH[1]}")
MAX_VERSION=$([[ $(cat appinfo/info.xml) =~ $(echo '<nextcloud[^>]*max-version=[^[0-9]]*([0-9]+).*') ]] && echo "${BASH_REMATCH[1]}")

[ -z "$MIN_VERSION" ] && [ -z "$MAX_VERSION" ] && echo 'A version constraint should be set' && exit 1

MIN_VERSION=${MIN_VERSION:-$MAX_VERSION}
MAX_VERSION=${MAX_VERSION:-$MIN_VERSION}

[ "$MIN_VERSION" -gt "$MAX_VERSION" ] && echo 'Min version should be less or equal to max version' && exit 1

echo "["\"$(seq -s '","' "$MIN_VERSION" "$MAX_VERSION")\""]"
