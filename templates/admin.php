<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?php script('gdatavaas', 'admin');?>"></script>
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
                <td><label for="auth_method"><?php p($l->t('Authentication Method'));?></label></td>
                <td class="input_field">
                    <select id="authMethod" name="authMethod">
                        <option value="ClientCredentials" <?php if ($_['authMethod'] === 'ClientCredentials') { echo 'selected'; } ?>>Client Credentials Flow</option>
                        <option value="ResourceOwnerPassword" <?php if ($_['authMethod'] === 'ResourceOwnerPassword') { echo 'selected'; } ?>>Resource Owner Password Flow</option>
                    </select>
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
                <td><label for="quarantine_folder"><?php p($l->t('Quarantine folder'));?></label></td>
                <td class="input_field"><input id="quarantine_folder" type="text" name="quarantineFolder" value="<?php p($_['quarantineFolder']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><label for="allowlist">Allowlist</label></td>
                <td class="input_field"><input id="allowlist" type="text" name="allowlist" value="<?php p($_['allowlist']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><label for="blocklist">Blocklist</label></td>
                <td class="input_field"><input id="blocklist" type="text" name="blocklist" value="<?php p($_['blocklist']); ?>"/></td>
            </tr>
            <tr class="basic_settings">
                <td><label for="scan_queue_length"><?php p($l->t('Scan queue length'));?></label></td>
                <td class="input_field"><input id="scan_queue_length" type="text" name="scan_queue_length" value="<?php p($_['scanQueueLength']); ?>"/></td>
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
            <input class="submit-button" id="reset" type="submit" value="<?php p($l->t('Reset all tags'));?>" />
            <input id="auth_submit_advanced" type="submit" value="<?php p($l->t('Save'));?>" />
            <span id="auth_save_msg_advanced"></span>
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
        <tr id="scan_option_only_new">
            <td>
                <input id="scan_only_new" class="toggle-round" type="checkbox">
                <label for="scan_only_new"></label>
            </td>
            <td><label for="scan_new"><?php p($l->t('Scan only new files'));?></label></td>
        </tr>
        <tr>
            <td>
                <input id="prefixMalicious" class="toggle-round" type="checkbox">
                <label for="prefixMalicious"></label>
            </td>
            <td><label><?php p($l->t('Set prefix for malicious files'));?></label></td>
        </tr>
        <tr>
            <td>
                <input id="disable_tag_unscanned" class="toggle-round" type="checkbox">
                <label for="disable_tag_unscanned"></label>
            </td>
            <td><label><?php p($l->t('Disable Unscanned tag'));?></label></td>
        </tr>
    </table>
</div>
</body>
</html>
