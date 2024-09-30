#!/bin/bash

sudo apt-get update 
sudo apt-get install -y bash-completion vim iputils-ping telnet
sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"
sudo cp xdebug.local.ini /usr/local/etc/php/conf.d/xdebug.ini
sudo cp memory.ini /usr/local/etc/php/conf.d/memory.ini
sudo curl -sS https://webi.sh/gh | sh

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrc

NEXTCLOUD_VERSION=$(grep -oP "[0-9]+\.[0-9]+\.[0-9]+" install.sh)
mkdir -p ~/.ssh/
ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts
git clone --depth 1 --recurse-submodules --single-branch --branch v$NEXTCLOUD_VERSION git@github.com:nextcloud/server.git ./nextcloud-server
cd nextcloud-server
git submodule update --init
cd -
composer global require humbug/php-scoper
/home/vscode/.composer/vendor/bin/php-scoper completion bash >> /home/vscode/.bash_completion
echo 'export PATH=/home/vscode/.composer/vendor/bin/:$PATH' >>~/.bashrc
./install.sh