// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

import {showError, showSuccess, showWarning} from '@nextcloud/dialogs'
import {FileType, Permission, registerFileAction} from '@nextcloud/files'
import {getRequestToken} from '@nextcloud/auth'
import {t} from '@nextcloud/l10n'
import {generateUrl} from '@nextcloud/router'
import Magnifier from '@mdi/svg/svg/magnify.svg?raw'

registerFileAction({
	id: "gdatavaas-filescan",
	displayName: () => t('gdatavaas', 'Antivirus scan'),
	enabled: ({nodes}) => {
		if (!Array.isArray(nodes) || nodes.length === 0) {
			return false
		}

		return nodes.every((node) => {
			if (!node) {
				return false
			}

			return node.type !== FileType.Folder && Boolean(node.permissions & Permission.READ)
		})
	},
	iconSvgInline: () => Magnifier,
	async exec({nodes}) {
		try {
			const file = nodes[0]
			const fileId = file.id
			let response = await fetch(generateUrl('/apps/gdatavaas/scan'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'requesttoken': getRequestToken()
				},
				body: JSON.stringify({
					fileId: fileId
				})
			})
			let vaasVerdict = await response.json()
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
})
