#!/bin/bash

sudo apt-get update 
sudo apt-get install -y bash-completion vim iputils-ping telnet
sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrc

NEXTCLOUD_VERSION=$(grep -oP "[0-9]+\.[0-9]+\.[0-9]+" install.sh)
git clone --depth 1 --recurse-submodules --single-branch --branch v$NEXTCLOUD_VERSION git@github.com:nextcloud/server.git ./nextcloud-server
cd nextcloud-server
git submodule update --init
cd -

./install.sh