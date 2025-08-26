// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

import {showError, showSuccess, showWarning} from '@nextcloud/dialogs'
import {FileAction, Permission, registerFileAction} from '@nextcloud/files'
import Magnifier from '@mdi/svg/svg/magnify.svg?raw'

registerFileAction(new FileAction({
	id: "gdatavaas-filescan",
	displayName: () => t('gdatavaas', 'Antivirus scan'),
	enabled: (nodes) => {
		const node = nodes[0];
		return node.mime !== 'httpd/unix-directory' && (node.permissions & Permission.READ);
	},
	iconSvgInline: () => Magnifier,
	async exec(file) {
		try {
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
			if (response.status === 200) {
				switch (vaasVerdict['verdict']) {
					case 'Malicious':
						showError(t('gdatavaas', 'The file "' + file.basename + '" has been scanned with G DATA as verdict Malicious'));
						break;
					case 'Clean':
						showSuccess(t('gdatavaas', 'The file "' + file.basename + '" has been scanned with G DATA as verdict Clean'));
						break;
					case 'Pup':
						showWarning(t('gdatavaas', 'The file "' + file.basename + '" has been scanned with G DATA as ' +
							'verdict PUP (Potentially unwanted program)'));
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
		catch (e) {
			showError(t('gdatavaas', 'An error occurred while trying to scan the file: ') + e);
		}
	},
}))
