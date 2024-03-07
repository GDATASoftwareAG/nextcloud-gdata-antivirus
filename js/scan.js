OCA.Files.fileActions.registerAction({
	name: 'gdataFileScan',
	displayName: t('gdatavaas', 'G DATA Antivirus scan'),
	mime: 'file',
	permissions: OC.PERMISSION_READ,
	iconClass: 'icon-search',
	actionHandler: async (name, context) => {
		 const fileId = context.fileInfoModel.id;
		 let response = await fetch(OC.generateUrl('/apps/gdatavaas/scan'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'requesttoken': oc_requesttoken
				},
				body: JSON.stringify({
					fileId: fileId
				})
			});
		let vaasVerdict = await response.json();
		if (vaasVerdict === 'Malicious' || vaasVerdict === 'Clean') {
			OC.Notification.showTemporary('The file "' + name + '" has been scanned with G DATA as verdict ' + vaasVerdict);
		} else {
			try {
				OC.Notification.showTemporary('Sorry, something went wrong: ' + vaasVerdict['error']);
			} catch (e) {
				OC.Notification.showTemporary('Sorry, something went wrong. Please contact your administrator or check the settings.');
			}
		}
	},
})
