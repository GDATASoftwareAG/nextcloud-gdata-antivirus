#!/bin/bash

sudo apt-get update 
sudo apt-get install -y bash-completion vim
sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
sudo bash -c "npm completion > /usr/share/bash-completion/completions/npm"

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrc

./install.sh