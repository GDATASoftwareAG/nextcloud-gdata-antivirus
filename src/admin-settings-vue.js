// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createApp } from 'vue'
import AdminSettings from './AdminSettings.vue'

const parseInitial = (element) => {
	try {
		return JSON.parse(element.dataset.initial || '{}')
	} catch {
		return {}
	}
}

const mountAdminSettings = () => {
	const adminSettingsElement = document.querySelector('#gdatavaas-admin-settings')
	if (adminSettingsElement) {
		const initial = parseInitial(adminSettingsElement)
		createApp(AdminSettings, { initial }).mount(adminSettingsElement)
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mountAdminSettings)
} else {
	mountAdminSettings()
}
