#!/bin/bash

# SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
#
# SPDX-License-Identifier: AGPL-3.0-or-later

for d in "/usr/local/etc/php/conf.d" "${PHP_INI_DIR}/conf.d"; do
    if [ -n "$d" ] && [ -d "$d" ]; then
        echo "Setting PHP CLI memory_limit=-1 in $d/99-memory-limit.ini"
        echo "memory_limit = -1" | sudo tee "$d/99-memory-limit.ini" >/dev/null || true
    fi
done

export COMPOSER_MEMORY_LIMIT=-1

bash -i -c 'nvm install 20'
bash -i -c 'nvm use 20'

echo "setup php-scoper"
composer global require humbug/php-scoper
echo "export PATH=$(composer config home)/vendor/bin/:\$PATH" >> "$HOME"/.bashrc
COMPOSER_HOME=$(composer config home)
export PATH=$COMPOSER_HOME/vendor/bin/:$PATH

if [[ "$IS_CI" == 1 ]]; then
    echo "Skipping bash completion setup in CI environment"
    exit 0
fi

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrc

./scripts/run-app.sh
