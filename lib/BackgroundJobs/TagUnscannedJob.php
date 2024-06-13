<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\Service\TagUnscannedService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;

class TagUnscannedJob extends TimedJob {
	private TagUnscannedService $tagUnscannedService;
	public function __construct(ITimeFactory $time, TagUnscannedService $tagUnscannedService) {
		parent::__construct($time);

		$this->tagUnscannedService = $tagUnscannedService;

		$this->setInterval(60);
		$this->setAllowParallelRuns(false);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @param $argument
	 * @return void
	 * @throws Exception if the database platform is not supported
	 */
	protected function run($argument): void {
		$this->tagUnscannedService->run();
	}
}
