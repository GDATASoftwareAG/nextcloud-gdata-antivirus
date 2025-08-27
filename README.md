<!--
SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
SPDX-License-Identifier: CC0-1.0
-->

[![Tests](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/tests.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/tests.yml)
[![Static analysis](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/psalm-matrix.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/psalm-matrix.yml)
[![REUSE Compliance Check](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/reuse.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/reuse.yml)
[![Lint php-cs](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-php-cs.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-php-cs.yml)
[![Lint php](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-php.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-php.yml)
[![Lint info.xml](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-info-xml.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-info-xml.yml)
[![Lint eslint](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-eslint.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/lint-eslint.yml)
[![editorconfig-checker](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/editorconfig-checker.yml/badge.svg)](https://github.com/GDATASoftwareAG/nextcloud-gdata-antivirus/actions/workflows/editorconfig-checker.yml)

# G DATA Antivirus for Nextcloud

![Image](img/example.gif)

## Introduction

Welcome to the G DATA Verdict-as-a-Service (VaaS) integration for Nextcloud. This project aims to provide an additional layer of security to your Nextcloud instance by enabling automatic and manual scanning of files for malicious content.

VaaS scans files and tags them with either `Clean`, `Malicious` or `PUP (Potentially Unwanted Program)` verdicts, providing users with immediate feedback about the safety of their files. Unscanned files are tagged as `Unscanned` and queued for background scanning.

Verdict-as-a-Service is a cloud-based service provided by G DATA CyberDefense AG. It is designed to work on your own infrastructure as a self-hosted variant, ensuring a high level of security and privacy. If you are interested in using VaaS on-premise or have any questions, please contact vaas@gdata.de for more information or check out the [repository of our helm chart](https://github.com/GDATASoftwareAG/vaas-helm) for self-hosting the VaaS backend.

In the settings page of the Nextcloud app, you can create a free account to use G DATA's cloud-based service if self-hosting is not an option for you. No matter if you use the cloud-based service or the self-hosted variant, all files are scanned in a secure and privacy-friendly way. No file content is stored on the VaaS backend and all communication is encrypted. G DATA CyberDefense AG is a German company and therefore subject to the strict German and European data protection laws.

This project is licensed under the GNU Affero General Public License. For more details, please see the [LICENSES/AGPL-3.0-or-later.txt](LICENSES/AGPL-3.0-or-later.txt) file.

Please read on for information about setting up a development environment and contributing to the project.

## Maintenance and Release Schedule

The support and maintenance of the versions of this app is based on the official Nextcloud [Maintenance and Release Schedule](https://github.com/nextcloud/server/wiki/Maintenance-and-Release-Schedule).

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

The app offers a variety of settings to customize the behavior of the antivirus. The settings can be found in the Nextcloud admin settings page under the "G DATA Antivirus" section.

- **Authentication Method:** If you have created your own account on https://vaas.gdata.de/login, select 'Resource Owner Password Flow' here. If you have received access data from your provider (Client ID and Secret), select 'Client Credentials Flow'.
- **Scan only this:** Equivalent to an allowlist. If the values here are separated by commas, e.g. "Documents, .exe, Scan", only those containing the corresponding values in the path are scanned. In this example, *.exe files and the contents of the Documents/ and Scan/ folders would be scanned.
- **Do not scan this:** Equivalent to a blocklist. If there are values separated by commas, e.g. "Documents, .exe, Scan", these are not scanned.
- **Quarantine folder:** If an existing file is found to be malicious, it is moved to this folder in the user's home directory. If the folder does not exist, it is created automatically. If you do not want to use a quarantine folder, leave this field empty.
- **Notify mails:** If an email address is entered here (or multiple comma seperated), a notification is sent to this address when a user uploads a file that is found to be malicious.
- **Maximum scan size:** Files larger than this size (in MB) are not scanned and tagged as "Won't Scan". Recommended values are between 10 and 300 MB.
- **Timeout:** The time (in seconds) the app waits for a response from the VaaS backend before considering the scan as failed. Recommended values are between 10 and 300 seconds. Please note: If the timeout is set too short, it will restrict the scanning of large files, which take a little longer.
- **Cache:** If this option is disabled, each file is always scanned again and no results are cached.
- **Hash lookup:** During a hash lookup, the SHA256 checksum is transmitted to the G DATA Cloud before the scan to check whether a result is already available, thereby saving unnecessary network traffic, resource load, and time.
- **Advanced Settings:** The token endpoint and the VaaS URL determine the scan backend. By default, the public G DATA Cloud is used for scanning. If the VaaS backend is self-hosted, the corresponding values for the self-hosted instance must be entered here.

You can always hover over the settings name for more information.

## Self-hosting the scanning backend (VaaS)

If you want to self-host the scanning backend, take a look at the [repository of our helm chart](https://github.com/GDATASoftwareAG/vaas-helm).

## Nextcloud Commands

The following commands are available for managing and interacting with the G DATA VaaS app in your Nextcloud instance:

#### `gdatavaas:scan`

- **Description**: Scans files for malware.
- **Usage**: `php occ gdatavaas:scan`
- **Docker Usage**: `docker exec --user www-data nextcloud-container php occ gdatavaas:scan`
- **Details**: This command scans all files in the Nextcloud instance for malware and logs the results.

#### `gdatavaas:get-tags-for-file`

- **Description**: Retrieves tags for a specified file.
- **Usage**: `php occ gdatavaas:get-tags-for-file <file-path>`
- **Docker Usage**: `docker exec --user www-data nextcloud-container php occ gdatavaas:get-tags-for-file <file-path>`
- **Arguments**:
    - `<file-path>`: The path to the file (e.g., `username/files/filename`).
- **Details**: This command fetches and logs all tags associated with the specified file.

#### `gdatavaas:remove-tag`

- **Description**: Deletes a specified tag.
- **Usage**: `php occ gdatavaas:remove-tag <tag-name>`
- **Docker Usage**: `docker exec --user www-data nextcloud-container php occ gdatavaas:remove-tag <tag-name>`
- **Arguments**:
    - `<tag-name>`: The name of the tag to delete.
- **Details**: This command removes the specified tag from the system. If the tag does not exist, an error is logged.

#### `gdatavaas:tag-unscanned`

- **Description**: Tags all files without a tag from this app as unscanned.
- **Usage**: `php occ gdatavaas:tag-unscanned`
- **Docker Usage**: `docker exec --user www-data nextcloud-container php occ gdatavaas:tag-unscanned`
- **Details**: This command tags all files that have not been tagged by the G DATA VaaS app as "unscanned" and logs the results.

#### `gdatavaas:get-tag-id`

- **Description**: Gets the ID of a specified tag.
- **Usage**: `php occ gdatavaas:get-tag-id <tag-name>`
- **Docker Usage**: `docker exec --user www-data nextcloud-container php occ gdatavaas:get-tag-id <tag-name>`
- **Arguments**:
    - `<tag-name>`: The name of the tag to get the ID for.
- **Details**: This command retrieves and logs the ID of the specified tag. If the tag does not exist, an error is logged.

## Setting up a development environment

This project ships Make targets that set up and run a full Nextcloud dev instance in Docker.

The easiest way to get started is to use the Devcontainer with VSCode. It has all prerequisites installed and automatically mounts the app into the container.

But you can also set up the environment manually on your host machine:

### Prerequisites

Install these locally (or ensure your devcontainer provides them):

- Docker and Docker Compose
- GNU Make
- Node.js 20.x and npm (used by make npm)
- PHP CLI (8.1+)
- Optional for tests and packaging:
  - Bats (for make bats)
  - php-scoper (only for make appstore - if you want to execute bats tests or want the production app, not development version)

Note: The Makefile will download composer.phar if Composer isn’t available, but it still requires a local PHP CLI to run it.

### Quick start: run Nextcloud with the app mounted

```bash
# From the repository root
make local
```

What this does:
- Builds backend and frontend assets (make build → make composer + make npm).
- Starts a Nextcloud dev container on http://localhost:8080 and bind-mounts this app into /var/www/html/apps-extra/gdatavaas.
- Installs PHP dependencies on the host so local tooling works.

After the container is up, open Nextcloud at http://localhost:8080 and enable the app via the Apps UI:
- Find `G DATA Antivirus` in your apps and click `Enable`.
- Important: Enable only. Do not upgrade to the app store version. That overrides your local code in the container.

### Day-to-day workflow

- Edit PHP (server) code: changes are picked up immediately by the container via the bind mount; just refresh the page.
- Edit frontend (JS/CSS/Vue): rebuild assets explicitly (no hot reload) and refresh the page without cache (CTRL + F5):

```bash
make npm
```

### Available Make targets

- make build
  - Fetches PHP dependencies and builds frontend assets.
- make composer
  - Installs/upgrades PHP dependencies. If Composer isn’t installed, a local composer.phar is fetched and used.
- make npm
  - Installs Node dependencies and builds JS/CSS bundles. Re-run this whenever you change frontend code.
- make oc
  - Clones the Nextcloud server into ./nextcloud-server (used for local development that requires private OC namespaces).
- make local
  - Rebuilds the app and runs a Nextcloud dev container on http://localhost:8080 with this app mounted. Safe to re-run; it replaces any existing container named nextcloud-container.
- make clean
  - Removes the build/ directory.
- make distclean
  - Also removes vendor/, node_modules/, js/node_modules/, nextcloud-server/, composer.lock, etc.
- make unittests
  - Runs PHP unit tests via ./vendor/bin/phpunit using tests/unittests/bootstrap.php. Installs Composer deps first.
- make bats
  - Spins up a complete environment using Docker Compose and runs end-to-end Bats tests from tests/bats with the production build of the app.
  - Requirements: Bats installed locally and two environment variables set: CLIENT_ID and CLIENT_SECRET (valid VaaS credentials).
- make appstore
  - Builds a distributable tarball at build/artifacts/gdatavaas.tar.gz. Intended for releases; requires php-scoper available in PATH.

### Stopping and restarting

- Stop/remove the dev container manually if needed:

```bash
docker stop nextcloud-container || true
```

- Re-run make local to rebuild and restart the environment.

Notes:
- SMTP testing (smtp4dev) is available when using the Docker Compose-based test setup; it listens on http://localhost:8081. The simple make local flow runs a single Nextcloud container on port 8080.
- The helper script scripts/run-app.sh orchestrates CI and test flows; for local development, stick to the Make targets above.

### Useful commands

| Description               | Command                                                                                                  |
|---------------------------|----------------------------------------------------------------------------------------------------------|
| Trigger cronjobs manually | `docker exec --user www-data nextcloud-container php /var/www/html/cron.php`                             |
| Upgrade Nextcloud via CLI | `docker exec --user www-data nextcloud-container php occ upgrade`                                        |
| Watch logs                | `docker exec --user www-data nextcloud-container php occ log:watch`                                      |
| Watch raw logs            | `docker exec --user www-data nextcloud-container php occ log:watch --raw \| jq .message`                 |
| Set log level to debug    | `docker exec --user www-data nextcloud-container php occ log:manage --level DEBUG`                       |


### Smtp4Dev

When developing locally, SMTP4Dev is launched on port 8081 to simulate sending emails through the app.

For more information about Smtp4Dev, please refer to the [official README](https://github.com/rnwood/smtp4dev/blob/master/README.md).

### Configuring via the command line

In addition to the graphical configuration via the VaaS settings page in Nextcloud, configuration is possible via PHP OCC commands:

```
# The authentication flow to use (depends on available credentials). Default: ResourceOwnerPassword
php occ config:app:set gdatavaas authMethod <ResourceOwnerPassword|ClientCredentials>

# Username + Password are used only in ResourceOwnerPassword authMethod
php occ config:app:set gdatavaas username <string>
php occ config:app:set gdatavaas password <string>

# ClientID + ClientSecret are used only in ClientCredentials authMethod
php occ config:app:set gdatavaas clientId <string>
php occ config:app:set gdatavaas clientSecret <string>

# VaaS server address. Default: https://gateway.staging.vaas.gdatasecurity.de
php occ config:app:set gdatavaas vaasUrl <URL>
# Authentication server. Default: https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token
php occ config:app:set gdatavaas tokenEndpoint <URL>

# Name of quarantine folder. Default: Quarantine
php occ config:app:set gdatavaas quarantineFolder <string>
# Whether to enable the automatic file scan. Default: false
php occ config:app:set gdatavaas autoScanFiles <true|false>
# Whether to add a prefix to malicious files. Default: false
php occ config:app:set gdatavaas prefixMalicious <true|false>
# Whether to disable the unscanned tag. Default: false
php occ config:app:set gdatavaas disableUnscannedTag <true|false>
# Comma-separated list of files/folders that should be scanned. Default: Empty string (all files)
php occ config:app:set gdatavaas scanOnlyThis <string>
# Comma-separated list of files/folders that should **not** be scanned. Default: Empty string (no files excluded)
php occ config:app:set gdatavaas doNotScanThis <string>
# Email address to send notifications to, when infected files are uploaded. Default: None
php occ config:app:set gdatavaas notifyMail <email>
# Whether to send email notifications on upload, when files are infected. Default: false
php occ config:app:set gdatavaas sendMailOnVirusUpload <true|false>
# Maximum file size (in MB) to scan. Default: 256
php occ config:app:set gdatavaas maxScanSizeInMB <int>
# Timeout (in seconds) for the VaaS backend. Default: 300
php occ config:app:set gdatavaas timeout <int>
# Whether to cache scan results. Default: true
php occ config:app:set gdatavaas cache <true|false>
# Whether to perform a hash lookup before uploading the file. Default: true
php occ config:app:set gdatavaas hashlookup <true|false>
```

You can also install and/or update the app via OCC:

```
# Install
php occ app:install gdatavaas
# Upgrade
php occ app:update gdatavaas
```
