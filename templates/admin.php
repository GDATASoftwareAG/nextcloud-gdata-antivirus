<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?php script('gdatavaas', 'gdatavaas-admin-settings');?>"></script>
    <script src="<?php style('gdatavaas', 'style');?>"></script>
    <title>G DATA Verdict-as-a-Service</title>
</head>
<body>
<div class="section section-auth">
    <fieldset class="personalblock">
        <h2>G DATA Verdict-as-a-Service</h2>
        <h3>You may use self registration and create a new username and password by yourself <a href="https://vaas.gdata.de/login" target="_blank">here</a> for free.</h3>
        <table class="basic_settings_table">
            <tr class="basic_settings">
                <td><div title="<?php p($l->t('If you have registered yourself with your e-mail address and a password, select "Resource Owner Password Flow" here, if you have received a client id and a client secret from G DATA CyberDefense AG, use "Client Credentials Flow". You can ignore the other fields.'));?>" class="visible"><label for="auth_method"><?php p($l->t('Authentication Method'));?></label></div></td>
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
            <tr class="basic_settings">
                <td><div title="<?php p($l->t('Files scanned as "Malicious" are moved to this folder. They can still be downloaded etc. there, but this helps to prevent accidental use.'));?>" class="visible"><label for="quarantine_folder"><?php p($l->t('Quarantine folder'));?></label></div></td>
                <td class="input_field"><input id="quarantine_folder" type="text" name="quarantineFolder" value="<?php p($_['quarantineFolder']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><div title="<?php p($l->t('Comma-separated allow list values. Can be paths, folders, file names or file types. Wildcards are not supported.'));?>" class="visible"><label for="allowlist"><?php p($l->t('Scan only this'));?></label></div></td>
                <td class="input_field"><input id="allowlist" type="text" name="allowlist" value="<?php p($_['allowlist']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><div title="<?php p($l->t('Comma-separated block list values. Can be paths, folders, file names or file types. Wildcards are not supported.'));?>" class="visible"><label for="blocklist"><?php p($l->t('Do not scan this'));?></label></div></td>
                <td class="input_field"><input id="blocklist" type="text" name="blocklist" value="<?php p($_['blocklist']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><div title="<?php p($l->t('If you have activated "Automatic file scanning", the number of files set here will be scanned every five minutes. This setting should help to influence the server load. '));?>" class="visible"><label for="scan_queue_length"><?php p($l->t('Scan queue length'));?></label></div></td>
                <td class="input_field"><input id="scan_queue_length" type="text" name="scan_queue_length" value="<?php p($_['scanQueueLength']); ?>"/></td>
            </tr>
            <tr class="notify_mails">
                <td><div title="<?php p($l->t('Mail addresses for notifications when malicious files are found or a user tries to upload them. Must be comma-separated.'));?>" class="visible"><label for="notify_mails"><?php p($l->t('Notify Mails'));?></label></div></td>
                <td class="input_field"><input id="notify_mails" type="text" name="notify_mails" value="<?php p($_['notifyMail']); ?>"/></td>
            </tr>
        </table>
        <input class="submit-button" id="auth_submit" type="submit" value="<?php p($l->t('Save'));?>" />
        <span id="auth_save_msg"></span>
        <div id="advanced_settings">
            <h3><?php p($l->t('Advanced Settings'));?></h3>
            <h4><?php p($l->t('If you are not sure about this, you can just leave it blank.'));?></h4>
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
                    <td><input id="auth_submit_advanced" type="submit" value="<?php p($l->t('Save'));?>" /></td>
                    <td><div title="<?php p($l->t('Removes all tags set by this app.'));?>" class="visible"><input class="submit-button" id="reset" type="submit" value="<?php p($l->t('Reset all tags'));?>"/></div></td>
                    <td><span id="auth_save_msg_advanced"></span></td>
                </tr>
            </table>
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
</body>
</html>
