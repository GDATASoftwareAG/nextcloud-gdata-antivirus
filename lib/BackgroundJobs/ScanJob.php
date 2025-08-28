<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\ScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IAppConfig;

class ScanJob extends TimedJob {
	private ScanService $scanService;
	private IAppConfig $appConfig;

	public function __construct(ITimeFactory $time, ScanService $scanService, IAppConfig $appConfig) {
		parent::__construct($time);

		$this->scanService = $scanService;
		$this->appConfig = $appConfig;

		$this->setInterval(60);
		$this->setAllowParallelRuns(false);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @param $argument
	 * @return void
	 * @throws Exception if the database platform is not supported
	 */
	#[\Override]
	protected function run($argument): void {
		$autoScan = $this->appConfig->getValueBool(Application::APP_ID, 'autoScanFiles');
		if (!$autoScan) {
			return;
		}
		$this->scanService->run();
	}
}
