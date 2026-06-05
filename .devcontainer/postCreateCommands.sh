#!/bin/bash

set -euo pipefail

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

# for d in "/usr/local/etc/php/conf.d" "${PHP_INI_DIR}/conf.d"; do
#     if [ -n "$d" ] && [ -d "$d" ]; then
#         echo "Setting PHP CLI memory_limit=-1 in $d/99-memory-limit.ini"
#         echo "memory_limit = -1" | sudo tee "$d/99-memory-limit.ini" >/dev/null || true
#     fi
# done

export COMPOSER_MEMORY_LIMIT=-1

echo "setup php-scoper"
composer global require --no-interaction humbug/php-scoper
PATH_EXPORT="export PATH=$(composer config home)/vendor/bin/:\$PATH"
if ! grep -qxF "$PATH_EXPORT" "$HOME"/.bashrc; then
    echo "$PATH_EXPORT" >> "$HOME"/.bashrc
fi
COMPOSER_HOME=$(composer config home)
export PATH=$COMPOSER_HOME/vendor/bin/:$PATH

if [[ "${IS_CI:-0}" == 1 ]]; then
    echo "Skipping bash completion setup in CI environment"
    exit 0
fi

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"

if ! grep -qxF ". /usr/share/bash-completion/bash_completion" "$HOME"/.bashrc; then
    echo ". /usr/share/bash-completion/bash_completion" >> "$HOME"/.bashrc
fi

./scripts/run-app.sh
