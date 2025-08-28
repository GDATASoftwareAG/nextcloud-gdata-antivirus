<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class VaasAdminSection implements IIconSection {
	public function __construct(
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return 'G DATA Antivirus';
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
