# SPDX-FileCopyrightText: 2026 G DATA CyberDefense AG
#
# SPDX-License-Identifier: AGPL-3.0-or-later

ARG NEXTCLOUD_VERSION
FROM nextcloud:${NEXTCLOUD_VERSION}

# Install and enable Xdebug for web and CLI PHP execution.
RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
