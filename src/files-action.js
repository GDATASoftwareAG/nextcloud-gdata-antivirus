import { showError, showSuccess } from '@nextcloud/dialogs'
import {DefaultType, FileAction, Permission, registerFileAction} from '@nextcloud/files'

if (parseInt(OC.config.version.split('.')[0]) >= 28) {
	showSuccess('G DATA BAntivirus scan file action registered');
	registerFileAction(new FileAction({
		id: "gdatavaas-filescan",
		displayName: () => t('gdatavaas', 'Antivirus scan'),
		default: DefaultType.DEFAULT,
		enabled: (nodes) => {
			const node = nodes[0];
			return node.mime !== 'httpd/unix-directory' && (node.permissions & Permission.READ);
		},
		iconSvgInline: () => "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n\n<svg\n   version=\"1.1\"\n   id=\"svg2\"\n   width=\"446\"\n   height=\"616.76001\"\n   viewBox=\"0 0 446 616.76001\"\n   sodipodi:docname=\"G DATA Logo 2017 1C.eps\"\n   xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"\n   xmlns:sodipodi=\"http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd\"\n   xmlns=\"http://www.w3.org/2000/svg\"\n   xmlns:svg=\"http://www.w3.org/2000/svg\">\n  <defs\n     id=\"defs6\" />\n  <sodipodi:namedview\n     id=\"namedview4\"\n     pagecolor=\"#ffffff\"\n     bordercolor=\"#000000\"\n     borderopacity=\"0.25\"\n     inkscape:showpageshadow=\"2\"\n     inkscape:pageopacity=\"0.0\"\n     inkscape:pagecheckerboard=\"0\"\n     inkscape:deskcolor=\"#d1d1d1\" />\n  <g\n     id=\"g8\"\n     inkscape:groupmode=\"layer\"\n     inkscape:label=\"ink_ext_XXXXXX\"\n     transform=\"matrix(1.3333333,0,0,-1.3333333,0,616.76)\">\n    <g\n       id=\"g10\"\n       transform=\"scale(0.1)\">\n      <path\n         d=\"M 29.1367,3557.51 84.332,2187.98 C 127.273,993.301 1699.26,0 1699.26,0 c 0,0 1565.17,993.262 1600.37,2187.98 l 9.1,358.22 c -408.53,189.15 -1098.06,253.78 -1616.75,254 v -790.22 c 143.62,-3 379.05,-3.86 492.84,-28.56 151.21,-32.79 135.66,-111.57 132.82,-141.31 -31.59,-331.58 -439,-598.51 -618.09,-706.66 -181.05,120.61 -712.421,519.97 -723.382,1057.1 l -21.066,727.55 c 227.818,46.01 474.788,71.04 732.948,71.04 704.09,0 1248.73,-104.18 1610.81,-249.43 l 16.08,-6.51 30.08,824.31 c -397.34,191.97 -1107.89,288.54 -1659.28,288.54 -553.94,0 -1264.463,-96.57 -1656.6033,-288.54\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path12\" />\n      <path\n         d=\"m 2865.5,4071.79 94.98,221.73 93.51,-272.43 c -75.05,21.79 -112.81,31.93 -188.49,50.7 z m 475.77,-337.62 c -102.56,268.72 -157.84,410.88 -269.91,682.51 -2.36,5.7 -7.2,10.08 -13.12,11.81 -77.55,22.73 -122.16,34.74 -202.19,54.3 -4.05,1.01 -10.73,-2.05 -12.65,-5.79 -107.85,-211.84 -164.44,-324.02 -272.49,-540.52 -3.72,-7.45 -0.22,-14.51 7.97,-16.03 74.31,-13.66 117.02,-22.45 192.48,-39.66 4.31,-1 10.88,2.29 12.7,6.35 l 37.03,83.19 c 109.97,-26.19 165.53,-41.11 279.62,-79.93 l 40.39,-101.48 c 2.17,-5.44 6.72,-9.63 12.32,-11.37 67.9,-21.14 108.66,-34.73 173.63,-57.91 11.2,-4.01 18.47,3.41 14.22,14.53\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path14\" />\n      <path\n         d=\"m 626.109,4234 c -81.554,-17.56 -128.211,-28.73 -208.882,-50.08 -7.325,-1.96 -10.516,-8.89 -4.133,-22.09 4.961,-10.92 9.836,-25.35 9.836,-39.91 V 3990.4 l -57.762,-15.42 c -116.129,-31.9 -152.594,36.4 -152.594,118.66 0,111.3 49.082,179.39 153.539,208.09 l 56.817,15.22 c 83.355,21.59 126.617,31.94 207.847,49.4 4.227,0.9 8.836,6.61 8.836,10.95 l 0.024,125.09 c 0,7.75 -5.965,12.59 -13.528,10.96 -131.32,-28.26 -200.898,-45.99 -334.668,-84.96 C 83.125,4367.68 0,4172.07 0,4030.7 c 0,-144.18 92.6367,-280.47 330.09,-213.35 120.109,33.95 182.019,49.38 300.715,74.89 4.222,0.92 8.867,6.64 8.867,10.96 v 319.85 c 0,7.74 -6,12.56 -13.563,10.95\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path16\" />\n      <path\n         d=\"m 1102.9,4118.35 -58,-7.06 0.03,326.56 58.92,7.22 c 118.78,13.82 156.87,-65.44 156.87,-147.6 0,-111.3 -50.91,-166.69 -157.82,-179.12 z m 36.88,478.48 c -122.98,-13.39 -185.862,-22.16 -304.999,-42.85 -4.551,-0.77 -9.308,-6.41 -9.308,-11.02 v -599.41 c 0,-7.37 5.843,-12.29 13.117,-11.03 132.433,22.93 203.33,32.53 340.9,46.4 214,21.51 302.19,182.56 302.19,323.94 0,144.17 -98.51,320.47 -341.9,293.97\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path18\" />\n      <path\n         d=\"m 2679.46,4366.86 c 7.54,-1.57 13.51,3.27 13.51,10.95 v 133.08 c 0,4.35 -4.61,10.08 -8.9,10.97 -232.49,48.7 -354.9,66.53 -589.58,86.18 -6.65,0.53 -12.11,-4.52 -12.11,-11.17 v -133.41 c 0,-5.3 4.96,-10.71 10.26,-11.16 71.97,-6.04 111.22,-10.2 186.18,-19.41 v -456.26 c 0,-4.98 4.84,-10.5 9.81,-11.13 77.27,-9.57 120.73,-15.79 196.7,-28.51 7.27,-1.22 13.09,3.7 13.09,11.05 l -0.03,452.87 c 73.37,-12.4 112.2,-19.65 181.07,-34.05\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path20\" />\n      <path\n         d=\"m 1730.72,4216.94 97.28,244.19 96.2,-250.19 c -77.33,3.9 -116.04,5.12 -193.48,6 z m 492.37,-226.71 c -108.3,247.24 -165.73,376.23 -275.7,620.12 -2.45,5.45 -7.98,8.44 -14.1,8.77 -82.73,4.43 -128.79,5.84 -212.08,6.53 -4.07,0.03 -9.64,-1.44 -12.74,-8.11 -110.15,-237.13 -167.68,-362.61 -276.57,-603.43 -4.11,-9.09 0.8,-16.3 10.78,-15.79 73.58,3.59 116.84,4.73 193.6,5.27 3.08,0.02 9.1,4.08 10.28,6.95 l 40.34,94.66 c 112.79,-0.25 169.34,-2.03 284.35,-10.5 l 38.2,-95.9 c 1.04,-2.55 6.89,-6.8 9.61,-7 76.53,-5.27 119.67,-9.09 192.5,-17.19 10.34,-1.16 15.67,6.09 11.53,15.62\"\n         style=\"fill:#141316;fill-opacity:1;fill-rule:nonzero;stroke:none\"\n         id=\"path22\" />\n    </g>\n  </g>\n</svg>\n",
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