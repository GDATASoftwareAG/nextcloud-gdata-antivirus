# SPDX-FileCopyrightText: Bernhard Posselt <dev@bernhard-posselt.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

app_directory_name=$(notdir $(CURDIR))
app_real_name=gdatavaas
build_tools_directory=$(CURDIR)/build/tools
source_build_directory=./build/artifacts/source
source_package_name=$(source_build_directory)/$(app_directory_name)
appstore_build_directory=$(CURDIR)/build/artifacts
appstore_package_name=$(appstore_build_directory)/$(app_real_name)
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)

all: build

# Fetches dependencies and builds it
.PHONY: build
build:
ifneq (,$(wildcard $(CURDIR)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(CURDIR)/package.json))
	make npm
endif
ifneq (,$(wildcard $(CURDIR)/js/package.json))
	make npm
endif

# Clone Nextcloud server to know private OC namespace
.PHONY: oc
oc:
ifeq (,$(wildcard nextcloud-server))
	./scripts/get-nc-server.sh
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (,$(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist --no-dev
else
	composer install --prefer-dist --no-dev
endif

# Installs npm dependencies and builds the js code
.PHONY: npm
npm:
ifeq (,$(wildcard $(CURDIR)/package.json))
ifeq (,$(wildcard $(CURDIR)/package-lock.json))
	npm install --no-audit --progress=false
else
	npm ci
endif
	cd js && $(npm) run build
else
ifeq (,$(wildcard $(CURDIR)/package-lock.json))
	npm install --no-audit --progress=false
else
	npm ci
endif
	npm run build
endif

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build

# Run unit tests
.PHONY: unittests
unittests:
	composer install
	./vendor/bin/phpunit --bootstrap tests/unittests/bootstrap.php tests/unittests/ --testdox

# Run bats tests
.PHONY: bats
bats:
	./scripts/run-app.sh "31.0.8" 1
	bats --verbose-run --timing --trace ./tests/bats

# Complete production like but static Nextcloud and app setup
.PHONY: prod
prod: oc
	./scripts/run-app.sh "31.0.8" 1

# Same as clean but also removes dependencies and build related folders
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules
	rm -rf nextcloud-server
	rm -rf build
	rm -f composer.lock

# Builds the app in a Docker container and serves it on localhost:8080
.PHONY: local
local: build
	docker compose kill || true
	docker stop nextcloud-container || true
	docker container rm nextcloud-container || true
	docker run --rm -d -p 8080:80 --name nextcloud-container -e SERVER_BRANCH="v31.0.8" -v .:/var/www/html/apps-extra/gdatavaas ghcr.io/juliusknorr/nextcloud-dev-php84:latest
	composer install

# Builds the app for production and prepares it for the appstore under ./build/artifacts
.PHONY: appstore
appstore: build
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	php-scoper add-prefix --output-dir=$(source_build_directory) --force
	composer dump-autoload --working-dir $(source_build_directory) --classmap-authoritative
	tar czf $(appstore_package_name).tar.gz \
	--transform s%$(source_build_directory)%$(app_real_name)% \
	--exclude-vcs \
	--exclude="$(source_build_directory)/.git" \
	--exclude="$(source_build_directory)/.github" \
	--exclude="$(source_build_directory)/composer.json" \
	--exclude="$(source_build_directory)/composer.json.license" \
	--exclude="$(source_build_directory)/babel.config.js" \
	--exclude="$(source_build_directory)/.editorconfig" \
	--exclude="$(source_build_directory)/.gitignore" \
	--exclude="$(source_build_directory)/.php-cs-fixer.dist.php" \
	--exclude="$(source_build_directory)/.php-cs-fixer.cache" \
	--exclude="$(source_build_directory)/Makefile" \
	--exclude="$(source_build_directory)/package.json" \
	--exclude="$(source_build_directory)/package-lock.json" \
	--exclude="$(source_build_directory)/package-lock.json.license" \
	--exclude="$(source_build_directory)/package.json.license" \
	--exclude="$(source_build_directory)/psalm.xml" \
	--exclude="$(source_build_directory)/webpack.config.json" \
	--exclude="$(source_build_directory)/stylelint.config.json" \
	--exclude="$(source_build_directory)/empty-skeleton.config.php" \
	--exclude="$(source_build_directory)/scoper.inc.php" \
	--exclude="$(source_build_directory)/renovate.json" \
	--exclude="$(source_build_directory)/renovate.json.license" \
	--exclude="$(source_build_directory)/scripts" \
	--exclude="$(source_build_directory)/src" \
	--exclude="$(source_build_directory)/docker-compose.yaml" \
	--exclude="$(source_build_directory)/composer.lock" \
	$(source_build_directory) \
