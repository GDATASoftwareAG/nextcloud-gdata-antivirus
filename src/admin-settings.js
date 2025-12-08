// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later
import { t } from '@nextcloud/l10n'

document.addEventListener('DOMContentLoaded', async () => {
	async function postData(url = '', data = {}) {
		const response = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				requesttoken: oc_requesttoken,
			},
			body: JSON.stringify(data),
		})
		return response.json()
	}

	async function getData(url = '') {
		const response = await fetch(url, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				requesttoken: OC.requesttoken,
			},
		})
		return response.json()
	}

	function hideUnneccessaryFields(selectedFlow) {
		let style = selectedFlow == 'ResourceOwnerPassword' ? 'table-row' : 'none'
		document.querySelector('tr.basic_settings:has(#username)').style.display = style
		document.querySelector('tr.basic_settings:has(#password)').style.display = style
		style = selectedFlow == 'ClientCredentials' ? 'table-row' : 'none'
		document.querySelector('tr.basic_settings:has(#clientId)').style.display = style
		document.querySelector('tr.basic_settings:has(#clientSecret)').style.display = style
	}

	const authSubmit = document.querySelector('#auth_submit')
	const authSubmitAdvanced = document.querySelector('#auth_submit_advanced')
	const testSettings = document.querySelector('#test-settings')
	const resetAllTags = document.querySelector('#reset')
	const authMethod = document.querySelector('#authMethod')

	hideUnneccessaryFields(authMethod.value)

	authMethod.addEventListener('change', (e) => {
		hideUnneccessaryFields(e.target.value)
	})

	authSubmit.addEventListener('click', async (e) => {
		e.preventDefault()
		const username = document.querySelector('#username').value
		const password = document.querySelector('#password').value
		const clientId = document.querySelector('#clientId').value
		const clientSecret = document.querySelector('#clientSecret').value
		const maxScanSize = document.querySelector('#max-scan-size').value
		const timeout = document.querySelector('#timeout').value
		const cache = document.querySelector('#cache').checked
		const hashlookup = document.querySelector('#hashlookup').checked

		const response = await postData(OC.generateUrl('apps/gdatavaas/adminSettings'), {
			username: username,
			password: password,
			clientId: clientId,
			clientSecret: clientSecret,
			authMethod: authMethod.value,
			maxScanSize,
			timeout,
			cache,
			hashlookup,
		})
		const msgElement = document.querySelector('#auth_save_msg')

		console.log('TEST L10N:', t('gdatavaas', 'Data saved successfully.'))

		if (response.status === 'success') {
			msgElement.textContent = t('gdatavaas', 'Data saved successfully.')
		} else {
			if (response.message) {
				msgElement.textContent = response.message
			} else {
				msgElement.textContent = t('gdatavaas', 'An error occurred when saving the data.')
			}
		}
	})

	testSettings.addEventListener('click', async (e) => {
		e.preventDefault()
		const tokenEndpoint = document.querySelector('#token_endpoint').value
		const vaasUrl = document.querySelector('#vaas_url').value

		const response = await postData(OC.generateUrl('apps/gdatavaas/testsettings'), {
			tokenEndpoint,
			vaasUrl,
		})
		const msgElement = document.querySelector('#auth_save_msg_advanced')

		if (response.status === 'success') {
			msgElement.textContent = t('gdatavaas', 'Authentication successful and VaaS backend reachable.')
		} else {
			msgElement.textContent = response.message || t('gdatavaas', 'An error occurred during the test.')
		}
	})

	authSubmitAdvanced.addEventListener('click', async (e) => {
		e.preventDefault()
		const tokenEndpoint = document.querySelector('#token_endpoint').value
		const vaasUrl = document.querySelector('#vaas_url').value

		const response = await postData(OC.generateUrl('apps/gdatavaas/setAdvancedConfig'), {
			tokenEndpoint,
			vaasUrl,
		})
		const msgElement = document.querySelector('#auth_save_msg_advanced')

		if (response.status === 'success') {
			msgElement.textContent = t('gdatavaas', 'Data saved successfully.')
		} else {
			msgElement.textContent = t('gdatavaas', 'An error occurred when saving the data.')
		}
	})

	resetAllTags.addEventListener('click', async (e) => {
		e.preventDefault()
		const response = await postData(OC.generateUrl('apps/gdatavaas/resetalltags'), {})
		const msgElement = document.querySelector('#auth_save_msg_advanced')

		if (response.status === 'success') {
			msgElement.textContent = t('gdatavaas', 'All tags have been reset successfully.')
		} else {
			msgElement.textContent = t('gdatavaas', 'An error occurred when resetting the tags.')
		}
	})

	document.querySelector('#cache').checked = (await getData(OC.generateUrl('apps/gdatavaas/getCache'))).status
	document.querySelector('#hashlookup').checked = (
		await getData(OC.generateUrl('apps/gdatavaas/getHashlookup'))
	).status
})
