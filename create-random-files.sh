#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

for i in $(seq 1 1000); do
  tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 10 > "/var/www/html/data/admin/files/RandomFiles/file_$i.txt"
done
