<?php
/**
 * SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('gdatavaas', 'style');
\OCP\Util::addScript('gdatavaas', 'gdatavaas-admin-settings');

?>

<div class="section section-auth">
	<fieldset class="personalblock">
		<h2>Administrator Settings</h2>
		<h6>You may use self registration and create a new username and password by yourself <a href="https://vaas.gdata.de/login" target="_blank">here</a> for free.</h6>
		<table class="basic_settings_table">
			<tr class="basic_settings">
				<td><div title="<?php p($l->t('If you have registered yourself with your e-mail address and a password, select "Resource Owner Password Flow" here, if you have received a client id and a client secret from G DATA CyberDefense AG, use "Client Credentials Flow". You can ignore the other fields.'));?>" class="visible"><label for="authMethod"><?php p($l->t('Authentication Method'));?></label></div></td>
				<td class="input_field">
					<select id="authMethod" name="authMethod">
						<option value="ClientCredentials" <?php if ($_['authMethod'] === 'ClientCredentials') {
							echo 'selected';
						} ?>>Client Credentials Flow</option>
						<option value="ResourceOwnerPassword" <?php if ($_['authMethod'] === 'ResourceOwnerPassword') {
							echo 'selected';
						} ?>>Resource Owner Password Flow</option>
					</select></td>
			</tr>
			<tr class="basic_settings">
				<td><label for="username"><?php p($l->t('Username'));?></label></td>
				<td class="input_field"><input id="username" type="text" name="username" value="<?php p($_['username']); ?>"/></td>
			</tr>
			<tr class="basic_settings">
				<td><label for="password"><?php p($l->t('Password'));?></label></td>
				<td class="input_field"><input id="password" type="password" name="password" value="<?php p($_['password']); ?>"/></td>
			</tr>
			<tr class="basic_settings">
				<td><label for="clientId">Client ID</label></td>
				<td class="input_field"><input id="clientId" type="text" name="clientId" value="<?php p($_['clientId']); ?>"/></td>
			</tr>
			<tr class="basic_settings">
				<td><label for="clientSecret">Client Secret</label></td>
				<td class="input_field"><input id="clientSecret" type="password" name="clientSecret" value="<?php p($_['clientSecret']); ?>"/></td>
			</tr>
			<tr class="max-scan-size">
				<td><div title="<?php p($l->t('The maximum scan size for files to be scanned in MB. Files above this limit are tagged as “Won\'t Scan”.'));?>" class="visible"><label for="max-scan-size"><?php p($l->t('Maximum scan size'));?></label></div></td>
				<td class="input_field"><input id="max-scan-size" type="number" min="0" name="max-scan-size" value="<?php p($_['maxScanSizeInMB']); ?>"/></td>
			</tr>
			<tr class="timeout">
				<td><div title="<?php p($l->t('The timeout determines how long a file scan may take in seconds before it is canceled. Please note: If the timeout is set too short, it will restrict the scanning of large files, which take a little longer.'));?>" class="visible"><label for="timeout"><?php p($l->t('Timeout'));?></label></div></td>
				<td class="input_field"><input id="timeout" type="number" min="0" name="timeout" value="<?php p($_['timeout']); ?>"/></td>
			</tr>
			<tr class="cache">
				<td><div title="<?php p($l->t('If this option is disabled, each file is always scanned again and no results are cached.'));?>" class="visible"><label for="cache"><?php p($l->t('Cache'));?></label></div></td>
				<td class="input_field"><input id="cache" type="checkbox" name="cache" <?php if ($_['cache']) {
					p('checked');
				} ?>/></td>
			</tr>
			<tr class="hashlookup">
				<td><div title="<?php p($l->t('During a hash lookup, the SHA256 checksum is transmitted to the G DATA Cloud before the scan to check whether a result is already available, thereby saving unnecessary network traffic, resource load, and time.'));?>" class="visible"><label for="hashlookup"><?php p($l->t('Hash lookup'));?></label></div></td>
				<td class="input_field"><input id="hashlookup" type="checkbox" name="hashlookup" <?php if ($_['hashlookup']) {
					p('checked');
				} ?>/></td>
			</tr>
		</table>
		<input class="submit-button" id="auth_submit" type="submit" value="<?php p($l->t('Save'));?>" />
		<span id="auth_save_msg"></span>
		<div id="advanced_settings">
			<h2><?php p($l->t('Advanced Settings'));?></h2>
			<h6><?php p($l->t('If you are not sure about this, you can just leave it blank.'));?></h6>
			<table>
				<tr class="token_endpoint">
					<td><label for="token_endpoint">Token Endpoint</label></td>
					<td class="input_field"><input type="text" id="token_endpoint" name="tokenEndpoint" value="<?php p($_['tokenEndpoint']); ?>"/></td>
				</tr>
				<tr class="vaas_url">
					<td><label for="vaas_url">Vaas URL</label></td>
					<td class="input_field"><input type="text" id="vaas_url" name="vaasUrl" value="<?php p($_['vaasUrl']); ?>"/></td>
				</tr>
			</table>
			<table>
				<tr id="advanced_buttons">
					<td><input id="test-settings" type="submit" value="<?php p($l->t('Test'));?>" /></td>
					<td><input id="auth_submit_advanced" type="submit" value="<?php p($l->t('Save'));?>" /></td>
					<td><div title="<?php p($l->t('Removes all tags set by this app.'));?>" class="visible"><input class="submit-button" id="reset" type="submit" value="<?php p($l->t('Reset all tags'));?>"/></div></td>
				</tr>
			</table>
			<span id="auth_save_msg_advanced"></span>
		</div>
	</fieldset>
</div>
