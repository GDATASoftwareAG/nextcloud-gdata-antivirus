<?php
/**
 * SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('gdatavaas', 'style');
\OCP\Util::addScript('gdatavaas', 'gdatavaas-operator-settings');

?>

<div class="section section-auth">
	<fieldset class="personalblock">
		<h2>Operator Settings</h2>
		<table class="basic_settings_table">
			<tr class="basic_settings">
				<td><div title="<?php p($l->t('Files scanned as "Malicious" are moved to this folder. They can still be downloaded etc. there, but this helps to prevent accidental use.'));?>" class="visible"><label for="quarantine_folder"><?php p($l->t('Quarantine folder'));?></label></div></td>
				<td class="input_field"><input id="quarantine_folder" type="text" name="quarantineFolder" value="<?php p($_['quarantineFolder']); ?>"/></td>
			</tr>
			<tr class="basic_settings">
				<td><div title="<?php p($l->t('Comma-separated allow list values. Can be paths, folders, file names or file types. Wildcards are not supported.'));?>" class="visible"><label for="scanOnlyThis"><?php p($l->t('Scan only this'));?></label></div></td>
				<td class="input_field"><input id="scanOnlyThis" type="text" name="scanOnlyThis" value="<?php p($_['scanOnlyThis']); ?>"/></td>
			</tr>
			<tr class="basic_settings">
				<td><div title="<?php p($l->t('Comma-separated block list values. Can be paths, folders, file names or file types. Wildcards are not supported.'));?>" class="visible"><label for="doNotScanThis"><?php p($l->t('Do not scan this'));?></label></div></td>
				<td class="input_field"><input id="doNotScanThis" type="text" name="doNotScanThis" value="<?php p($_['doNotScanThis']); ?>"/></td>
			</tr>
			<tr class="notify_mails">
				<td><div title="<?php p($l->t('Mail addresses for notifications when malicious files are found or a user tries to upload them. Must be comma-separated.'));?>" class="visible"><label for="notify_mails"><?php p($l->t('Notify Mails'));?></label></div></td>
				<td class="input_field"><input id="notify_mails" type="text" name="notify_mails" value="<?php p($_['notifyMail']); ?>"/></td>
			</tr>
		</table>
		<input class="submit-button" id="operator_submit" type="submit" value="<?php p($l->t('Save'));?>" />
		<span id="operator_save_msg"></span>
		<div class="warning">
			<strong>Caution:</strong> The use of the <em>"Scan only this"</em> and <em>"Do not scan this"</em> settings should be approached with caution. Using these settings allows malicious users to upload and distribute malicious content via the Nextcloud instance. It is recommended that you carefully consider the implications of these settings and use them in a way that does not jeopardize the security of your system and data.
		</div>
	</fieldset>
</div>
<div class="section section-scan">
	<table class="file_scan_options">
		<tr id="scan_option_auto_scan">
			<td>
				<input id="auto_scan_files" class="toggle-round" type="checkbox" name="autoScanFiles"/>
				<label for="auto_scan_files"></label>
			</td>
			<td><label for="auto_scan"><?php p($l->t('Automatic file scanning'));?></label></td>
		</tr>
		<tr>
			<td>
				<input id="prefixMalicious" class="toggle-round" type="checkbox">
				<label for="prefixMalicious"></label>
			</td>
			<td><div title="<?php p($l->t('If the scan result is "Malicious", this is added to the front of the file name. Increases the visibility of malicious content.'));?>" class="visible"><label><?php p($l->t('Set prefix for malicious files'));?></label></div></td>
		</tr>
		<tr>
			<td>
				<input id="disable_tag_unscanned" class="toggle-round" type="checkbox">
				<label for="disable_tag_unscanned"></label>
			</td>
			<td><div title="<?php p($l->t('Files that have not yet been scanned will no longer be tagged "Unscanned", but they will still be scanned if "Automatic file scanning" is switched on.'));?>" class="visible"><label><?php p($l->t('Disable Unscanned tag'));?></label></div></td>
		</tr>
		<tr>
			<td>
				<input id="send_mail_on_virus_upload" class="toggle-round" type="checkbox">
				<label for="send_mail_on_virus_upload"></label>
			</td>
			<td><div title="<?php p($l->t('If a user tries to upload an infected file an email is send to all \'Notify Mails\' receiver'));?>" class="visible"><label><?php p($l->t('Send mails on infected file upload'));?></label></div></td>
		</tr>
	</table>
	<h3>
		<label><?php p($l->t('Files scanned: '));?></label>
		<label id="scan_counter"></label>
	</h3>
</div>
