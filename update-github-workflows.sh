#!/bin/sh

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

# Update GitHub workflows from the Nextcloud template repository.
# This script is meant to be run from the root of the repository.

# Sanity check
[ ! -d ./.github/workflows/ ] && echo "Error: .github/workflows does not exist" && exit 1

# Clone template repository
temp="$(mktemp -d)"
git clone --depth=1 https://github.com/nextcloud/.github.git "$temp"

# Update workflows
rsync -vr \
    --existing \
    --include='*/' \
    --include='*.yml' \
    --exclude='*' \
    "$temp/workflow-templates/" \
    ./.github/workflows/

# Make every ubuntu-latest-low to ubuntu-latest due to problems with the low version
find ./.github/workflows/ -type f -name '*.yml' -exec sed -i 's/ubuntu-latest-low/ubuntu-latest/g' {} +

# Remove concurrency blocks from workflows
find ./.github/workflows/ -type f -name '*.yml' -exec sed -i '/concurrency:/,/true/d' {} +

# Insert missing symbol
find ./.github/workflows/ -type f -name 'psalm-matrix.yml' -exec sed -i 's/phpVersion="${{ steps.versions.outputs.php-min }}/phpVersion="${{ steps.versions.outputs.php-min }}"/g' {} +

# Insert './get-nc-server.sh' into the psalm-matrix.yml at line 65:10 -> As we use Nextcloud private classes Psalm needs to know them
find ./.github/workflows/ -type f -name 'psalm-matrix.yml' -exec sed -i '65i\          ./get-nc-server.sh' {} +

# Cleanup
rm -rf "$temp"
