<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\TagUnscannedService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class TagUnscannedJob extends TimedJob {
	private TagUnscannedService $tagUnscannedService;
	public function __construct(ITimeFactory $time, IAppConfig $appConfig, TagService $tagService, LoggerInterface $logger) {
		parent::__construct($time);

		$this->tagUnscannedService = new TagUnscannedService($logger, $tagService, $appConfig);

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
		$this->tagUnscannedService->run();
	}
}
