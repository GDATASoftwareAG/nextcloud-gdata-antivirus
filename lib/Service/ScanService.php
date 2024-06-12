<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ScanService {
	private TagService $tagService;
	private VerdictService $verdictService;
	private IConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $verdictService, IConfig $appConfig) {
		$this->logger = $logger;
		$this->tagService = $tagService;
		$this->verdictService = $verdictService;
		$this->appConfig = $appConfig;
	}

	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function run() {
		$unscannedTagIsDisabled = $this->appConfig->getAppValue(Application::APP_ID, 'disableUnscannedTag');
		$quantity = $this->appConfig->getAppValue(Application::APP_ID, 'scanQueueLength');
		try {
			$quantity = intval($quantity);
		} catch (\Exception) {
			$quantity = 5;
		}

		$maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
		$pupTag = $this->tagService->getTag(TagService::PUP);
		$cleanTag = $this->tagService->getTag(TagService::CLEAN);
		$unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		if ($unscannedTagIsDisabled) {
			$excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];
			$fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $quantity);
		} else {
			$fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $quantity, 0);
		}

		$this->logger->debug("Scanning files");

		foreach ($fileIds as $fileId) {
			try {
				$this->verdictService->scanFileById($fileId);
			} catch (\Exception $e) {
				$this->logger->error("Failed to scan file with id " . $fileId . ": " . $e->getMessage());
			}
		}

		$this->logger->debug("Scanned " . count($fileIds) . " files");
	}
}
