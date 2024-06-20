<?php

namespace OCA\GDataVaas\Settings;

use OCA\GDataVaas\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\Settings\ISettings;

class VaasAdmin implements ISettings {

	private IAppConfig $config;

	public function __construct(IAppConfig $config) {
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$params = [
			'username' => $this->config->getValueString(Application::APP_ID, 'username'),
			'password' => $this->config->getValueString(Application::APP_ID, 'password'),
			'clientId' => $this->config->getValueString(Application::APP_ID, 'clientId'),
			'clientSecret' => $this->config->getValueString(Application::APP_ID, 'clientSecret'),
			'authMethod' => $this->config->getValueString(Application::APP_ID, 'authMethod', 'ResourceOwnerPassword'),
			'tokenEndpoint' => $this->config->getValueString(Application::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token'),
			'vaasUrl' => $this->config->getValueString(Application::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de'),
			'quarantineFolder' => $this->config->getValueString(Application::APP_ID, 'quarantineFolder', 'Quarantine'),
			'autoScanFiles' => $this->config->getValueBool(Application::APP_ID, 'autoScanFiles'),
			'scanOnlyNewFiles' => $this->config->getValueBool(Application::APP_ID, 'scanOnlyNewFiles', true),
			'prefixMalicious' => $this->config->getValueBool(Application::APP_ID, 'prefixMalicious', true),
			'disableUnscannedTag' => $this->config->getValueBool(Application::APP_ID, 'disableUnscannedTag'),
			'allowlist' => $this->config->getValueString(Application::APP_ID, 'allowlist'),
			'blocklist' => $this->config->getValueString(Application::APP_ID, 'blocklist'),
			'scanQueueLength' => $this->config->getValueInt(Application::APP_ID, 'scanQueueLength', 50),
		];

		return new TemplateResponse(Application::APP_ID, 'admin', $params);
	}

	public function getSection(): string {
		return 'vaas';
	}

	public function getPriority(): int {
		return 10;
	}
}
