<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class ScanService {
	private TagService $tagService;
	private VerdictService $verdictService;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $verdictService, IAppConfig $appConfig) {
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
        $quantity = $this->appConfig->getValueInt(Application::APP_ID, 'scanQueueLength');
        
        $fileIds = $this->getFileIdsToScan($quantity);
		$this->logger->debug("Scanning " . count($fileIds) . " files");
        
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

    /**
     * @param int $quantity
     * @return array
     * @throws Exception
     */
    private function getFileIdsToScan(int $quantity): array {
        $unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');
        
        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $pupTag = $this->tagService->getTag(TagService::PUP);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);
        $wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);
        
        // TODO: If quantity is higher than the number of files available, this could loop forever
        $fileIdsAllowedToScan = [];
        if ($unscannedTagIsDisabled) {
            $excludedTagIds = [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];
            $limit = 50;
            $offset = 0;
            while (count($fileIdsAllowedToScan) < $quantity) {
                $fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $limit, $offset);
                foreach ($fileIds as $fileId) {
                    if ($this->verdictService->isAllowedByAllowAndBlocklist($fileId)) {
                        $fileIdsAllowedToScan[] = $fileId;
                    }
                }
                $offset += $limit;
            }
        } else {
            $this->tagService->getTag(TagService::UNSCANNED);
            $limit = 50;
            $offset = 0;
            while (count($fileIdsAllowedToScan) < $quantity) {
                $fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $limit, $offset);
                foreach ($fileIds as $fileId) {
                    if ($this->verdictService->isAllowedByAllowAndBlocklist($fileId)) {
                        $fileIdsAllowedToScan[] = $fileId;
                    }
                }
                $offset += $limit;
            }
        }
        
        return $fileIdsAllowedToScan;
    }
}
