#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

rm -rf composer.lock
rm -rf vendor/

if [ -f composer.packagist.vaas.json ]; then
    mv composer.packagist.vaas.json composer.json
fi
