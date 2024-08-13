document.addEventListener('DOMContentLoaded', async () => {

	async function postData(url = '', data = {}) {
		const response = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': oc_requesttoken
			},
			body: JSON.stringify(data)
		});
		return response.json();
	}

	async function getData(url = '') {
		const response = await fetch(url, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': oc_requesttoken
			}
		});
		return response.json();
	}

	function hideUnneccessaryFields(selectedFlow) {
		let style = selectedFlow == "ResourceOwnerPassword" ? "table-row" : "none";
		document.querySelector('tr.basic_settings:has(#username)').style.display = style;
		document.querySelector('tr.basic_settings:has(#password)').style.display = style;
		style = selectedFlow == "ClientCredentials" ? "table-row" : "none";
		document.querySelector('tr.basic_settings:has(#clientId)').style.display = style;
		document.querySelector('tr.basic_settings:has(#clientSecret)').style.display = style;
	}

	const authSubmit = document.querySelector('#auth_submit');
	const authSubmitAdvanced = document.querySelector('#auth_submit_advanced');
	const resetAllTags = document.querySelector('#reset');
	const autoScanFiles = document.querySelector('#auto_scan_files');
	const prefixMalicious = document.querySelector('#prefixMalicious');
	const authMethod = document.querySelector('#authMethod');
	const disableUnscannedTag = document.querySelector('#disable_tag_unscanned');
	const scanCounter = document.querySelector('#scan_counter');
	const sendMailOnVirusUpload = document.querySelector('#send_mail_on_virus_upload');
	const sendMailSummaryOfMaliciousFiles = document.querySelector('#send_summary_mail_for_malicious_files');

	hideUnneccessaryFields(authMethod.value);

	authMethod.addEventListener('change', (e) => {
		hideUnneccessaryFields(e.target.value);
	});

	authSubmit.addEventListener('click', async (e) => {
		e.preventDefault();
		const username = document.querySelector('#username').value;
		const password = document.querySelector('#password').value;
		const clientId = document.querySelector('#clientId').value;
		const clientSecret = document.querySelector('#clientSecret').value;
		const quarantineFolder = document.querySelector('#quarantine_folder').value;
		const scanOnlyThis = document.querySelector('#scanOnlyThis').value;
		const doNotScanThis = document.querySelector('#doNotScanThis').value;
		const scanQueueLength = document.querySelector('#scan_queue_length').value;
		const notifyMails = document.querySelector('#notify_mails').value;

		const response = await postData(OC.generateUrl('apps/gdatavaas/setconfig'), {
			username: username,
			password: password,
			clientId: clientId,
			clientSecret: clientSecret,
			authMethod: authMethod.value,
			quarantineFolder,
			scanOnlyThis,
			doNotScanThis,
			scanQueueLength,
			notifyMails
		});
		const msgElement = document.querySelector('#auth_save_msg');

		if (response.status === "success") {
			msgElement.textContent = 'Data saved successfully.';
		} else {
			if (response.message) {
				msgElement.textContent = response.message;
			}
			else {
				msgElement.textContent = 'An error occurred when saving the data.';
			}
		}
	});

	authSubmitAdvanced.addEventListener('click', async (e) => {
		e.preventDefault();
		const tokenEndpoint = document.querySelector('#token_endpoint').value;
		const vaasUrl = document.querySelector('#vaas_url').value;

		const response = await postData(OC.generateUrl('apps/gdatavaas/setadvancedconfig'), { tokenEndpoint, vaasUrl });
		const msgElement = document.querySelector('#auth_save_msg_advanced');

		if (response.status === "success") {
			msgElement.textContent = 'Data saved successfully.';
		} else {
			msgElement.textContent = 'An error occurred when saving the data.';
		}
	});

	resetAllTags.addEventListener('click', async (e) => {
		e.preventDefault();
		const response = await postData(OC.generateUrl('apps/gdatavaas/resetalltags'), {});
		const msgElement = document.querySelector('#auth_save_msg_advanced');

		if (response.status === "success") {
			msgElement.textContent = 'All tags have been reset successfully.';
		} else {
			msgElement.textContent = 'An error occurred when resetting the tags.';
		}
	});

	autoScanFiles.addEventListener('click', async () => {
		await toggleAutoScan(autoScanFiles.checked);
	});

	prefixMalicious.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setPrefixMalicious'), { prefixMalicious: prefixMalicious.checked });
	});

	disableUnscannedTag.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setDisableUnscannedTag'), { disableUnscannedTag: disableUnscannedTag.checked });
	});

	sendMailOnVirusUpload.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setSendMailOnVirusUpload'), { sendMailOnVirusUpload: sendMailOnVirusUpload.checked });
	});

	sendMailSummaryOfMaliciousFiles.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setSendMailSummaryOfMaliciousFiles'), { sendMailSummaryOfMaliciousFiles: sendMailSummaryOfMaliciousFiles.checked });
	});

	// Activate or deactivate automatic file scanning
	const toggleAutoScan = async (enable) => {
		autoScanFiles.checked = enable;
		const response = await postData(OC.generateUrl('apps/gdatavaas/setAutoScan'), { autoScanFiles: enable });
		if (response.status !== "success") {
			OC.Notification.showTemporary(`An Error occurred when ${enable ? 'activating' : 'deactivating'} automatic file scanning.`);
		}
	}

	// Set values on page load
	const autoScanResponse = await getData(OC.generateUrl('apps/gdatavaas/getAutoScan'));
	if (autoScanResponse.status) {
		autoScanFiles.checked = true;
	} else {
		autoScanFiles.checked = false;
	}
	prefixMalicious.checked = (await getData(OC.generateUrl('apps/gdatavaas/getPrefixMalicious'))).status;
	disableUnscannedTag.checked = (await getData(OC.generateUrl('apps/gdatavaas/getDisableUnscannedTag'))).status;
	sendMailOnVirusUpload.checked = (await getData(OC.generateUrl('apps/gdatavaas/getSendMailOnVirusUpload'))).status;
	sendMailSummaryOfMaliciousFiles.checked = (await getData(OC.generateUrl('apps/gdatavaas/getSendMailSummaryOfMaliciousFiles'))).status;

	let filesCounter = await getData(OC.generateUrl('apps/gdatavaas/getCounters'));
	if (filesCounter['status'] === 'success') {
		scanCounter.textContent = filesCounter["scanned"] + ' / ' + filesCounter["all"];
	}
	else {
		scanCounter.textContent = ' N/A';
		console.log('Error getting files counter:', filesCounter['message']);
	}
});
