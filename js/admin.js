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

	const authSubmit = document.querySelector('#auth_submit');
	const authSubmitAdvanced = document.querySelector('#auth_submit_advanced');
	const modifyValues = document.querySelector('#modify_values');
	const modifyValuesAdvanced = document.querySelector('#modify_values_advanced');
	const autoScanFiles = document.querySelector('#auto_scan_files');
	const scanOnlyNew = document.querySelector('#scan_only_new');
	const prefixMalicious = document.querySelector('#prefixMalicious');
	const authMethod = document.querySelector('#authMethod');

	const toggleReadOnly = (ids, readOnly) => {
		ids.forEach(id => document.querySelector(`#${id}`).toggleAttribute('readonly', readOnly));
	}

	authSubmit.addEventListener('click', async (e) => {
		e.preventDefault();
		const username = document.querySelector('#username').value;
		const password = document.querySelector('#password').value;
		const clientId = document.querySelector('#clientId').value;
		const clientSecret = document.querySelector('#clientSecret').value;
		const quarantineFolder = document.querySelector('#quarantine_folder').value;

		const response = await postData(OC.generateUrl('apps/gdatavaas/setconfig'), {
			username: username,
			password: password,
			clientId: clientId,
			clientSecret: clientSecret,
			authMethod: authMethod.value,
			quarantineFolder
		});
		const msgElement = document.querySelector('#auth_save_msg');

		if (response.status === "success") {
			toggleReadOnly(['username', 'password', 'clientId', 'clientSecret', 'authMethod', 'quarantine_folder'], true);
			msgElement.textContent = 'Data saved successfully.';
		} else {
			msgElement.textContent = 'An error occurred when saving the data.';
		}
	});

	authSubmitAdvanced.addEventListener('click', async (e) => {
		e.preventDefault();
		const tokenEndpoint = document.querySelector('#token_endpoint').value;
		const vaasUrl = document.querySelector('#vaas_url').value;

		const response = await postData(OC.generateUrl('apps/gdatavaas/setadvancedconfig'), {tokenEndpoint, vaasUrl});
		const msgElement = document.querySelector('#auth_save_msg_advanced');

		if (response.status === "success") {
			toggleReadOnly(['token_endpoint', 'vaas_url'], true);
			msgElement.textContent = 'Data saved successfully.';
		} else {
			msgElement.textContent = 'An error occurred when saving the data.';
		}
	});

	modifyValues.addEventListener('click', (e) => {
		e.preventDefault();
		toggleReadOnly(['username', 'password', 'clientId', 'clientSecret', 'authMethod', 'quarantine_folder'], false);
	});

	modifyValuesAdvanced.addEventListener('click', (e) => {
		e.preventDefault();
		toggleReadOnly(['token_endpoint', 'vaas_url'], false);
	});

	autoScanFiles.addEventListener('click', async () => {
		await toggleAutoScan(autoScanFiles.checked);
	});

	scanOnlyNew.addEventListener('click', async () => {
		await toggleScanOnlyNew(scanOnlyNew.checked);
	});

	prefixMalicious.addEventListener('click', async () => {
		await postData(OC.generateUrl('apps/gdatavaas/setPrefixMalicious'), {prefixMalicious: prefixMalicious.checked});
	});

	// Activate or deactivate scanning only for new files
	const toggleScanOnlyNew = async (enable) => {
		scanOnlyNew.checked = enable;
		scanOnlyNew.disabled = !autoScanFiles.checked;
		const response = await postData(OC.generateUrl('apps/gdatavaas/setScanOnlyNewFiles'), {scanOnlyNewFiles: enable});
		if (response.status !== "success") {
			OC.Notification.showTemporary(`An Error occurred when ${enable ? 'activating' : 'deactivating'} scanning only for new files.`);
		}
	};

	// Activate or deactivate automatic file scanning
	const toggleAutoScan = async (enable) => {
		autoScanFiles.checked = enable;
		const response = await postData(OC.generateUrl('apps/gdatavaas/setAutoScan'), {autoScanFiles: enable});
		await toggleScanOnlyNew(enable);
		if (response.status !== "success") {
			OC.Notification.showTemporary(`An Error occurred when ${enable ? 'activating' : 'deactivating'} automatic file scanning.`);
		}
	}

	// Set checkbox values on page load
	const autoScanResponse = await getData(OC.generateUrl('apps/gdatavaas/getAutoScan'));
	if (autoScanResponse.status) {
		autoScanFiles.checked = true;
		scanOnlyNew.disabled = false;
		const scanOnlyNewResponse = await getData(OC.generateUrl('apps/gdatavaas/getScanOnlyNewFiles'));
		scanOnlyNew.checked = scanOnlyNewResponse.status;
	} else {
		autoScanFiles.checked = false;
		await toggleScanOnlyNew(false);
	}
	prefixMalicious.checked = (await getData(OC.generateUrl('apps/gdatavaas/getPrefixMalicious'))).status;
});
