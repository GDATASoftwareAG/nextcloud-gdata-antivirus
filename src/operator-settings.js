// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
				requesttoken: oc_requesttoken,
			},
		})
		return response.json()
	}

	const operatorSubmit = document.querySelector('#operator_submit')
	const autoScanFiles = document.querySelector('#auto_scan_files')
	const prefixMalicious = document.querySelector('#prefixMalicious')
	const disableUnscannedTag = document.querySelector('#disable_tag_unscanned')
	const scanCounter = document.querySelector('#scan_counter')
	const sendMailOnVirusUpload = document.querySelector('#send_mail_on_virus_upload')

	operatorSubmit.addEventListener('click', async (e) => {
		e.preventDefault()
		const quarantineFolder = document.querySelector('#quarantine_folder').value
		const scanOnlyThis = document.querySelector('#scanOnlyThis').value
		const doNotScanThis = document.querySelector('#doNotScanThis').value
		const notifyMails = document.querySelector('#notify_mails').value

		const response = await postData(OC.generateUrl('apps/gdatavaas/operatorSettings'), {
			quarantineFolder,
			scanOnlyThis,
			doNotScanThis,
			notifyMails,
		})
		const msgElement = document.querySelector('#operator_save_msg')

		if (response.status === 'success') {
			msgElement.textContent = 'Data saved successfully.'
		} else {
			if (response.message) {
				msgElement.textContent = response.message
			} else {
				msgElement.textContent = 'An error occurred when saving the data.'
			}
		}
	})

	autoScanFiles.addEventListener('click', async () => {
		await toggleAutoScan(autoScanFiles.checked)
	})

	prefixMalicious.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setPrefixMalicious'), {
			prefixMalicious: prefixMalicious.checked,
		})
	})

	disableUnscannedTag.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setDisableUnscannedTag'), {
			disableUnscannedTag: disableUnscannedTag.checked,
		})
	})

	sendMailOnVirusUpload.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setSendMailOnVirusUpload'), {
			sendMailOnVirusUpload: sendMailOnVirusUpload.checked,
		})
	})

	// Activate or deactivate automatic file scanning
	const toggleAutoScan = async (enable) => {
		autoScanFiles.checked = enable
		const response = await postData(OC.generateUrl('apps/gdatavaas/setAutoScan'), { autoScanFiles: enable })
		if (response.status !== 'success') {
			OC.Notification.showTemporary(
				`An Error occurred when ${enable ? 'activating' : 'deactivating'} automatic file scanning.`
			)
		}
	}

	// Set values on page load
	const autoScanResponse = await getData(OC.generateUrl('apps/gdatavaas/getAutoScan'))
	if (autoScanResponse.status) {
		autoScanFiles.checked = true
	} else {
		autoScanFiles.checked = false
	}
	prefixMalicious.checked = (await getData(OC.generateUrl('apps/gdatavaas/getPrefixMalicious'))).status
	disableUnscannedTag.checked = (await getData(OC.generateUrl('apps/gdatavaas/getDisableUnscannedTag'))).status
	sendMailOnVirusUpload.checked = (await getData(OC.generateUrl('apps/gdatavaas/getSendMailOnVirusUpload'))).status

	let filesCounter = await getData(OC.generateUrl('apps/gdatavaas/getCounters'))
	if (filesCounter['status'] === 'success') {
		scanCounter.textContent = filesCounter['scanned'] + ' / ' + filesCounter['all']
	} else {
		scanCounter.textContent = ' N/A'
		console.log('Error getting files counter:', filesCounter['message'])
	}
})
