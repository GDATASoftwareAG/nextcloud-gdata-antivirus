// SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry['admin-settings'] = path.join(__dirname, 'src', 'admin-settings.js')
webpackConfig.entry['files-action'] = path.join(__dirname, 'src', 'files-action.js')

module.exports = webpackConfig
