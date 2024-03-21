// SPDX-FileCopyrightText: Lennart Dohmann <lennart.dohmann@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')

webpackRules.RULE_SVG = {
    resourceQuery: /raw/,
    type: 'asset/source',
}

webpackConfig.module.rules = Object.values(webpackRules)

webpackConfig.entry['admin-settings'] = path.join(__dirname, 'src', 'admin-settings.js')
webpackConfig.entry['files-action'] = path.join(__dirname, 'src', 'files-action.js')

module.exports = webpackConfig
