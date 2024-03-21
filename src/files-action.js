import { showError, showSuccess } from '@nextcloud/dialogs'
import {FileAction, Permission, registerFileAction} from '@nextcloud/files'
import Magnifier from '@mdi/svg/svg/magnify.svg?raw'

if (parseInt(OC.config.version.split('.')[0]) >= 28) {
	registerFileAction(new FileAction({
		id: "gdatavaas-filescan",
		displayName: () => t('gdatavaas', 'Antivirus scan'),
		enabled: (nodes) => {
			const node = nodes[0];
			return node.mime !== 'httpd/unix-directory' && (node.permissions & Permission.READ);
		},
		iconSvgInline: () => Magnifier,
		async exec(file) {
			const fileId = file.fileid;
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
				switch (vaasVerdict) {
					case 'Malicious':
						showError(t('gdatavaas', 'The file "' + file.basename + '" has been scanned with G DATA as verdict ' + vaasVerdict));
						break;
					case 'Clean':
						showSuccess(t('gdatavaas', 'The file "' + file.basename + '" has been scanned with G DATA as verdict ' + vaasVerdict));
						break;
				}
			} else {
				try {
					showError(t('gdatavaas', vaasVerdict.error));
				} catch (e) {
					showError(t('gdatavaas', 'An unknown error occurred while scanning the file'));
				}
			}
		},
	}))
} else {
	window.addEventListener('DOMContentLoaded', () => {
		if (OCA.Files && OCA.Files.fileActions) {
			OCA.Files.fileActions.registerAction({
				name: "gdatavaas-filescan",
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
						switch (vaasVerdict) {
							case 'Malicious':
								showError(t('gdatavaas', 'The file "' + name + '" has been scanned with G DATA as verdict ' + vaasVerdict));
								break;
							case 'Clean':
								showSuccess(t('gdatavaas', 'The file "' + name + '" has been scanned with G DATA as verdict ' + vaasVerdict));
								break;
						}
					} else {
						try {
							showError(t('gdatavaas', vaasVerdict.error));
						} catch (e) {
							showError(t('gdatavaas', 'An unknown error occurred while scanning the file'));
						}
					}
				}
			})
			return;
		}
		console.error('Unable to register G DATA Antivirus scan file action because the OCA.Files.fileActions object is not available');
	})
}