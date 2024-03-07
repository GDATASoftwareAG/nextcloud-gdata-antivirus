<?php

namespace OCA\GDataVaas\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class VaasAdminSection implements IIconSection {
	public function __construct(private IURLGenerator $urlGenerator) {
	}

	public function getName(): string {
		return 'Verdict-as-a-Service';
	}

	public function getID(): string {
		return 'vaas';
	}

	public function getPriority(): int {
		return 40;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('gdatavaas', 'gdatalogo.svg');
	}
}
