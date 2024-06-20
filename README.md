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

## Features

- **Automatic file scanning:** Files from users are automatically scanned 24/7 in the background.
- **Protection during upload:** Files are scanned during upload and tagged with a verdict.
- **Manual scanning:** Users can manually scan files at any time.
- **Nextcloud Activities:** The behavior of the antivirus can be tracked in the Activities app through smart logging.
- **File tagging:** Files are tagged with a verdict, providing immediate feedback to users.
- **No additional software required:** The app works out of the box with the G DATA VaaS cloud service.
- **Scanning rules:** The app offers both a block list and an allow list to easily set what should and should not be scanned.
- **Quarantine:**  If malicious files are already found in an existing Nextcloud environment, they can be moved to a quarantine area of the affected user.

## Tags

- **Clean:** The scanners did not find any malicious content in the file.
- **Malicious:** The scanners found a virus or other malicious content in the file.
- **Pup:** The scanners found a potentially unwanted program in the file. Could be adware, spyware, etc.
- **Unscanned:** The file has not been scanned yet.
- **Won't Scan:** The file is not scanned because it is too large or in a format that cannot be scanned.

## Settings

The app offers a variety of settings to customize the behavior of the antivirus. The settings can be found in the Nextcloud admin settings page under the "Verdict-as-a-Service" section.

- **Authentication Method:** If you have created your own account on https://vaas.gdata.de/login, select 'Resource Owner Password Flow' here. If you have received access data from your provider (Client ID and Secret), select 'Client Credentials Flow'. You can simply leave the other fields empty.
- **Scan only this:** Equivalent to an allowlist. If the values here are separated by commas, e.g. "Documents, .exe, Scan", only those containing the corresponding values in the path are scanned. In this example, *.exe files and the contents of the Documents/ and Scan/ folders would be scanned.
- **Do not scan this:** Equivalent to a blocklist. If there are values separated by commas, e.g. "Documents, .exe, Scan", these are not scanned.
- **Scan queue length:** Determines the number of files to be scanned per background job of the Nextcloud instance, see Basic settings. Helps to balance the server load and the time until all files are scanned. Recommended are values between 10 and 100.
- **Advanced Settings:** The token endpoint and the VaaS URL determine the scan backend. By default, the public G DATA Cloud is used for scanning. If the VaaS backend is self-hosted, the corresponding values for the self-hosted instance must be entered here.

## Self-hosting the scanning backend (VaaS)

If you want to self-host the scanning backend, take a look at the [repository of our helm chart](https://github.com/GDATASoftwareAG/vaas-helm).

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
folder "nextcloud-docker-dev" and running ```docker compose up nextcloud proxy```. For more information see the [Nextcloud app development tutorials](https://cloud.nextcloud.com/s/iyNGp8ryWxc7Efa). These steps set up the official Nextcloud Dev Environment. It uses an SQLite databse. If you want to test on a production like instance you can set up a real Nextcloud Server using this [compose file](compose.yaml).

### Useful commands

| Description               | Command                                                                                                  |
|---------------------------|----------------------------------------------------------------------------------------------------------|
| Trigger cronjobs manually | `docker exec --user www-data nextcloud-container php /var/www/html/cron.php`                             |
| Upgrade Nextcloud via CLI | `docker exec --user www-data nextcloud-container php occ upgrade`                                        |
| Watch logs                | `docker exec --user www-data nextcloud-container php occ log:watch`                                      |
| Watch raw logs            | `docker exec --user www-data nextcloud-container php occ log:watch --raw \| jq .message`                 |
| Set log level to debug    | `docker exec --user www-data nextcloud-container php occ log:manage --level DEBUG`                       |
