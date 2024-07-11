<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\SystemTag\TagNotFoundException;
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
	
	/**
	 * @param LoggerInterface $logger
	 * @return ScanService
	 */
	public function withLogger(LoggerInterface $logger): ScanService {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * @return int how many files where actually processed
	 * @throws Exception if the database platform is not supported
	 */
	public function run(): int {
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
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		$tagIds = [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];
		if ($unscannedTagIsDisabled) {
			try {
				array_push($tagIds, ($this->tagService->getTag(TagService::UNSCANNED, false))->getId());
			} catch (TagNotFoundException) {
			}
			$excludedTagIds = [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];
			$fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $quantity);
		} else {
			$this->tagService->getTag(TagService::UNSCANNED);
			$fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $quantity);
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
		return count($fileIds);
	}
}
