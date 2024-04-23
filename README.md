<!--
SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
SPDX-License-Identifier: CC0-1.0
-->

# G DATA Antivirus for Nextcloud

![Image](img/example.gif)

## Introduction

Welcome to the G DATA Verdict-as-a-Service (VaaS) integration for Nextcloud. This project aims to provide an additional layer of security to your Nextcloud instance by enabling automatic and manual scanning of files for malicious content.

VaaS scans files and tags them with either `Clean` or `Malicious` verdicts, providing users with immediate feedback about the safety of their files. Unscanned files are tagged as `Unscanned` and queued for background scanning.

Verdict-as-a-Service is a cloud-based service provided by G DATA CyberDefense AG. It is designed to work on your own infrastructure as a self-hosted variant, ensuring a high level of security and privacy. If you are interested in using VaaS on-premise or have any questions, please contact vaas@gdata.de for more information or check out the [repository of our helm chart](https://github.com/GDATASoftwareAG/vaas-helm) for self-hosting the VaaS backend.

In the settings page of the Nextcloud app, you can create a free account to use G DATA's cloud-based service if self-hosting is not an option for you. No matter if you use the cloud-based service or the self-hosted variant, all files are scanned in a secure and privacy-friendly way. No file content is stored on the VaaS backend and all communication is encrypted. G DATA CyberDefense AG is a German company and therefore subject to the strict German and European data protection laws.

This project is licensed under the GNU Affero General Public License. For more details, please see the [LICENSES/AGPL-3.0-or-later.txt](LICENSES/AGPL-3.0-or-later.txt) file.

Please read on for information about setting up a development environment and contributing to the project.

## Setting up a development environment

Before you start, make sure you have the following tools installed:

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/en/)
- [npm](https://www.npmjs.com/)

Also, you need to make an ```npm install```, ```npm run build``` and ```composer install``` to install dependencies and build the node modules.
You always need to do this before you start the development environment or copy the app to your Nextcloud instance manually.
If you copy the app directory manually in your Nextcloud instance you have to rename the folder to ```gdatavaas```. 

### Windows
For Windows you can also just start the docker-compose.yaml or the powershell script ```start-dev-environment.ps1```

### Linux
* For a quick development environment you can use the provided ```start-dev-environment.sh``` script. Or you use the following steps:
* Make sure you have docker compose installed
* Run the following command with bash in the folder where you want your Nextcloud in
```bash
git clone https://github.com/juliushaertl/nextcloud-docker-dev
cd nextcloud-docker-dev
./bootstrap.sh
sudo sh -c "echo '127.0.0.1 nextcloud.local' >> /etc/hosts"
docker-compose up nextcloud proxy
```
The command may take a while and starts Nextcloud directly. Nextcloud can then be accessed with your browser at http://nextcloud.local.

In the future, Nextcloud can then be started again by changing to the
folder "nextcloud-docker-dev" and running ```docker compose up nextcloud proxy```. For more information see the [Nextcloud app development tutorials](https://cloud.nextcloud.com/s/iyNGp8ryWxc7Efa). These steps set up the official Nextcloud Dev Environment. It uses an SQLite databse. If you want to test on a Postgres you can set up a real Nextcloud Server using this [compose file](compose.yaml).
