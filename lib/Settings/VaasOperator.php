<?php

// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Settings;

use OCA\GDataVaas\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class VaasOperator implements IDelegatedSettings {

	private IAppConfig $config;
	private IL10N $l;

	public function __construct(IAppConfig $config, IL10N $l) {
		$this->config = $config;
		$this->l = $l;
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$params = [
			'quarantineFolder'
				=> $this->config->getValueString(Application::APP_ID, 'quarantineFolder', 'Quarantine'),
			'autoScanFiles' => $this->config->getValueBool(Application::APP_ID, 'autoScanFiles'),
			'prefixMalicious'
				=> $this->config->getValueBool(Application::APP_ID, 'prefixMalicious', true),
			'disableUnscannedTag' => $this->config->getValueBool(Application::APP_ID, 'disableUnscannedTag'),
			'scanOnlyThis' => $this->config->getValueString(Application::APP_ID, 'scanOnlyThis'),
			'doNotScanThis' => $this->config->getValueString(Application::APP_ID, 'doNotScanThis'),
			'notifyMail' => $this->config->getValueString(Application::APP_ID, 'notifyMails'),
			'sendMailOnVirusUpload'
				=> $this->config->getValueBool(Application::APP_ID, 'sendMailOnVirusUpload'),
		];

		return new TemplateResponse(Application::APP_ID, 'operator', $params);
	}

	#[\Override]
	public function getSection(): string {
		return 'vaas';
	}

	#[\Override]
	public function getPriority(): int {
		return 20;
	}

	#[\Override]
	public function getName(): ?string {
		return $this->l->t('Operator Settings');
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
