<?php
/**
 * SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

\OCP\Util::addScript('gdatavaas', 'gdatavaas-admin-settings-vue');
\OCP\Util::addStyle('gdatavaas', 'gdatavaas-admin-settings-vue');

?>

<div id="gdatavaas-admin-settings" data-initial="<?php p(json_encode($_, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>"></div>
