<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class TagUnscannedJob extends TimedJob {
	private const APP_ID = "gdatavaas";

	private TagService $tagService;
	private IConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $time, IConfig $appConfig, TagService $tagService, LoggerInterface $logger) {
		parent::__construct($time);

		$this->appConfig = $appConfig;
		$this->tagService = $tagService;
		$this->logger = $logger;

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
		$unscannedTagIsDisabled = $this->appConfig->getAppValue(self::APP_ID, 'disableUnscannedTag');
		if ($unscannedTagIsDisabled) {
			$this->tagService->removeTag(TagService::UNSCANNED);
			return;
		}

		$this->logger->debug("Tagging unscanned files");

		$unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
		$maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
		$pupTag = $this->tagService->getTag(TagService::PUP);
		$cleanTag = $this->tagService->getTag(TagService::CLEAN);
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		$excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];

		$fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, 10000);

		foreach ($fileIds as $fileId) {
			if ($this->tagService->hasAnyButUnscannedTag($fileId)) {
				continue;
			}
			$this->tagService->setTag($fileId, TagService::UNSCANNED);
		}

		$this->logger->debug("Tagged " . count($fileIds) . " unscanned files");
	}
}
