#!/bin/bash

bash -i -c 'nvm install 20'
bash -i -c 'nvm use 20'

echo "setup php-scoper"
composer global require humbug/php-scoper
$(composer config home)/vendor/bin/php-scoper completion bash >> $HOME.bash_completion
echo "export PATH=$(composer config home)/vendor/bin/:\$PATH" >> $HOME/.bashrc
export PATH=$(composer config home)/vendor/bin/:$PATH

sudo cp memory.ini /usr/local/etc/php/conf.d/memory.ini

if [[ "$IS_CI" == "true" ]]; then
    exit 0
fi

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"
sudo cp xdebug.local.ini /usr/local/etc/php/conf.d/xdebug.ini

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrc

NEXTCLOUD_VERSION=$(grep -oP -m 1 "[0-9]+\.[0-9]+\.[0-9]+" install.sh)

mkdir -p ~/.ssh/

rm -rf nextcloud-server/
git clone --depth 1 --recurse-submodules --single-branch --branch v$NEXTCLOUD_VERSION https://github.com/nextcloud/server.git ./nextcloud-server
cd nextcloud-server
git submodule update --init
cd -
./install.sh