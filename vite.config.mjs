// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig({
	'admin-settings-vue': 'src/admin-settings-vue.js',
	'operator-settings-vue': 'src/operator-settings-vue.js',
	'files-action': 'src/files-action.js',
})
