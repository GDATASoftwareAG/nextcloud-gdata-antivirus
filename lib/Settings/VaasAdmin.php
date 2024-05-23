<?php

namespace OCA\GDataVaas\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\Settings\ISettings;

class VaasAdmin implements ISettings
{
    private const APP_ID = 'gdatavaas';

    private IAppConfig $config;

    public function __construct(IAppConfig $config)
    {
        $this->config = $config;
    }

    public function getForm(): TemplateResponse
    {
        $params = [
            'username' => $this->config->getValueString(self::APP_ID, 'username'),
            'password' => $this->config->getValueString(self::APP_ID, 'password'),
            'clientId' => $this->config->getValueString(self::APP_ID, 'clientId'),
            'clientSecret' => $this->config->getValueString(self::APP_ID, 'clientSecret'),
            'authMethod' => $this->config->getValueString(self::APP_ID, 'authMethod', 'ClientCredentials'),
            'tokenEndpoint' => $this->config->getValueString(self::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token'),
            'vaasUrl' => $this->config->getValueString(self::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de'),
            'quarantineFolder' => $this->config->getValueString(self::APP_ID, 'quarantineFolder', 'Quarantine'),
            'autoScanFiles' => $this->config->getValueBool(self::APP_ID, 'autoScanFiles'),
            'scanOnlyNewFiles' => $this->config->getValueBool(self::APP_ID, 'scanOnlyNewFiles', true),
            'prefixMalicious' => $this->config->getValueBool(self::APP_ID, 'prefixMalicious', true),
            'disableUnscannedTag' => $this->config->getValueBool(self::APP_ID, 'disableUnscannedTag'),
            'allowlist' => $this->config->getValueString(self::APP_ID, 'allowlist'),
            'blocklist' => $this->config->getValueString(self::APP_ID, 'blocklist'),
            'scanQueueLength' => $this->config->getValueInt(self::APP_ID, 'scanQueueLength', 50),
        ];

        return new TemplateResponse(self::APP_ID, 'admin', $params);
    }

    public function getSection(): string
    {
        return 'vaas';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
