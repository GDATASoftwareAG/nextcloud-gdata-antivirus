# SPDX-FileCopyrightText: Bernhard Posselt <dev@bernhard-posselt.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.
#
# The following npm scripts in your package.json install and update the bower
# and npm dependencies and use gulp as build system (notice how everything is
# run from the node_modules folder):
#
#    "scripts": {
#        "test": "node node_modules/gulp-cli/bin/gulp.js karma",
#        "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
#        "build": "node node_modules/gulp-cli/bin/gulp.js"
#    },

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

# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
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

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist --no-dev 
else
	composer install --prefer-dist --no-dev
endif

# Installs npm dependencies
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

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules
	rm -f composer.lock package-lock.json

# Builds the source package for the app store, ignores php tests, js tests
# and build related folders that are unnecessary for an appstore release
.PHONY: appstore
appstore: build
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	php-scoper add-prefix --output-dir=$(source_build_directory) --force
	mv $(source_build_directory)/vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php $(source_build_directory)/vendor/netresearch/jsonmapper/src/JsonMapper/JsonMapper_Exception.php
	mv $(source_build_directory)/vendor/netresearch/jsonmapper/src/JsonMapper.php $(source_build_directory)/vendor/netresearch/jsonmapper/src/JsonMapper/JsonMapper.php
	composer dump-autoload --working-dir $(source_build_directory) --classmap-authoritative
	tar czf $(appstore_package_name).tar.gz \
	--transform s%$(source_build_directory)%$(app_real_name)% \
	--exclude-vcs \
	--exclude="$(source_build_directory)/opcache-disabled.ini" \
	--exclude="$(source_build_directory)/opcache-blacklist.txt" \
	--exclude="$(source_build_directory)/artifacts" \
	--exclude="$(source_build_directory)/tmp*" \
	--exclude="$(source_build_directory)/Dockerfile*" \
	--exclude="$(source_build_directory)/nextcloud-server*" \
	--exclude="$(source_build_directory)/compose-install.yaml" \
	--exclude="$(source_build_directory)/empty-skeleton.config.php" \
	--exclude="$(source_build_directory)/get-matrix.sh" \
	--exclude="$(source_build_directory)/xdebug.*" \
	--exclude="$(source_build_directory)/build" \
	--exclude="$(source_build_directory)/tests" \
	--exclude="$(source_build_directory)/Makefile" \
	--exclude="$(source_build_directory)/*.log" \
	--exclude="$(source_build_directory)/phpunit*xml" \
	--exclude="$(source_build_directory)/composer.*" \
	--exclude="$(source_build_directory)/node_modules" \
	--exclude="$(source_build_directory)/js/node_modules" \
	--exclude="$(source_build_directory)/js/tests" \
	--exclude="$(source_build_directory)/js/test" \
	--exclude="$(source_build_directory)/js/*.log" \
	--exclude="$(source_build_directory)/js/package.json" \
	--exclude="$(source_build_directory)/js/bower.json" \
	--exclude="$(source_build_directory)/js/karma.*" \
	--exclude="$(source_build_directory)/js/protractor.*" \
	--exclude="$(source_build_directory)/package.json" \
	--exclude="$(source_build_directory)/bower.json" \
	--exclude="$(source_build_directory)/karma.*" \
	--exclude="$(source_build_directory)/protractor\.*" \
	--exclude="$(source_build_directory)/.*" \
	--exclude="$(source_build_directory)/js/.*" \
	--exclude="$(source_build_directory)/webpack.config.js" \
	--exclude="$(source_build_directory)/stylelint.config.js" \
	--exclude="$(source_build_directory)/CHANGELOG.md" \
	--exclude="$(source_build_directory)/README.md" \
	--exclude="$(source_build_directory)/package-lock.json" \
	--exclude="$(source_build_directory)/LICENSES" \
	--exclude="$(source_build_directory)/src" \
	--exclude="$(source_build_directory)/babel.config.js" \
	--exclude="$(source_build_directory)/devcontainer.yaml" \
	--exclude="$(source_build_directory)/psalm.xml" \
	--exclude="$(source_build_directory)/start-dev-environment.ps1" \
	--exclude="$(source_build_directory)/start-dev-environment.sh" \
	--exclude="$(source_build_directory)/dev-environment*" \
	--exclude="$(source_build_directory)/install.sh" \
	--exclude="$(source_build_directory)/renovate.json" \
	--exclude="$(source_build_directory)/get-matrix.sh" \
	--exclude="$(source_build_directory)/xdebug.ini" \
	--exclude="$(source_build_directory)/compose-install.yaml" \
	--exclude="$(source_build_directory)/Dockerfile.Nextcloud" \
	--exclude="$(source_build_directory)/empty-skeleton.config.php" \
	$(source_build_directory) \
