#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

mkdir -p /var/www/html/data/admin/files/BigFiles

for i in $(seq 1 5); do
    dd if=/dev/urandom of="/var/www/html/data/admin/files/BigFiles/bigfile_$i.txt" bs=268435457 count=1
done
