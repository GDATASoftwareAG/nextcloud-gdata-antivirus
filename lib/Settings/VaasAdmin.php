<?php

namespace OCA\GDataVaas\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class VaasAdmin implements ISettings {
	private const APP_ID = 'gdatavaas';

	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$params = [
			'username' => $this->config->getAppValue(self::APP_ID, 'username'),
			'password' => $this->config->getAppValue(self::APP_ID, 'password'),
			'clientId' => $this->config->getAppValue(self::APP_ID, 'clientId'),
			'clientSecret' => $this->config->getAppValue(self::APP_ID, 'clientSecret'),
			'authMethod' => $this->config->getAppValue(self::APP_ID, 'authMethod', 'ClientCredentials'),
			'tokenEndpoint' => $this->config->getAppValue(self::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token'),
			'vaasUrl' => $this->config->getAppValue(self::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de'),
			'quarantineFolder' => $this->config->getAppValue(self::APP_ID, 'quarantineFolder', 'Quarantine'),
			'autoScanFiles' => $this->config->getAppValue(self::APP_ID, 'autoScanFiles', false),
			'scanOnlyNewFiles' => $this->config->getAppValue(self::APP_ID, 'scanOnlyNewFiles', true),
			'prefixMalicious' => $this->config->getAppValue(self::APP_ID, 'prefixMalicious', true),
			'disableUnscannedTag' => $this->config->getAppValue(self::APP_ID, 'disableUnscannedTag', false),
			'allowlist' => $this->config->getAppValue(self::APP_ID, 'allowlist'),
			'blocklist' => $this->config->getAppValue(self::APP_ID, 'blocklist'),
			'scanQueueLength' => $this->config->getAppValue(self::APP_ID, 'scanQueueLength', 5),
		];

		return new TemplateResponse(self::APP_ID, 'admin', $params);
	}

	public function getSection(): string {
		return 'vaas';
	}

	public function getPriority(): int {
		return 10;
	}
}
