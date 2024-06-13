<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\ScanService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class ScanJob extends TimedJob {
	private ScanService $scanService;
    private IAppConfig $appConfig;

	public function __construct(LoggerInterface $logger, ITimeFactory $time, TagService $tagService, VerdictService $scanService, IAppConfig $appConfig) {
		parent::__construct($time);

		$this->scanService = new ScanService($logger, $tagService, $scanService, $appConfig);

        $this->appConfig = $appConfig;
		$this->setInterval(60);
		$this->setAllowParallelRuns(false);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

    /**
     * @param $argument
     * @return void
     * @throws Exception
     */
	protected function run($argument): void {
		$autoScan = $this->appConfig->getValueBool(Application::APP_ID, 'autoScanFiles');
		if (!$autoScan) {
			return;
		}
		$this->scanService->run();
	}
}
