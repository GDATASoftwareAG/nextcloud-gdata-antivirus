// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createApp } from 'vue'
import OperatorSettings from './OperatorSettings.vue'

const parseInitial = (element) => {
	try {
		return JSON.parse(element.dataset.initial || '{}')
	} catch {
		return {}
	}
}

const mountOperatorSettings = () => {
	const operatorSettingsElement = document.querySelector('#gdatavaas-operator-settings')
	if (operatorSettingsElement) {
		const initial = parseInitial(operatorSettingsElement)
		createApp(OperatorSettings, { initial }).mount(operatorSettingsElement)
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mountOperatorSettings)
} else {
	mountOperatorSettings()
}
