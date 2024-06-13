<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\ScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class ScanJob extends TimedJob {
	private ScanService $scanService;
	private IConfig $appConfig;

	public function __construct(ITimeFactory $time, ScanService $scanService, IConfig $appConfig) {
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
	 * @throws \OCP\DB\Exception if the database platform is not supported
	 */
	protected function run($argument): void {
		$autoScan = $this->appConfig->getAppValue(Application::APP_ID, 'autoScanFiles');
		if (!$autoScan) {
			return;
		}
		$this->scanService->run();
	}
}
