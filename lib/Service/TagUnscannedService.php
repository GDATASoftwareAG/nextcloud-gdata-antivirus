<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class TagUnscannedService {
	private TagService $tagService;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, IAppConfig $appConfig) {
		$this->logger = $logger;
		$this->tagService = $tagService;
		$this->appConfig = $appConfig;
	}

	/**
	 * @param LoggerInterface $logger
	 * @return TagUnscannedService
	 */
	public function withLogger(LoggerInterface $logger): TagUnscannedService {
		$this->logger = $logger;
		return $this;
	}
	
	/**
	 * @return int how many files where actually processed
	 * @throws \OCP\DB\Exception if the database platform is not supported
	 */
	public function run(): int {
		$unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');
		if ($unscannedTagIsDisabled) {
			$this->tagService->removeTag(TagService::UNSCANNED);
			return 0;
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
		return count($fileIds);
	}

}
