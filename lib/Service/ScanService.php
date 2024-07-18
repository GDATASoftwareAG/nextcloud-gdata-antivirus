<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
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
        $this->fileService = $fileService;
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
     * @return int
     * @throws Exception
     * @throws NotFoundException
     * @throws NotPermittedException
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
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    private function getFileIdsToScan(int $quantity): array {
        $unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');
        
        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $pupTag = $this->tagService->getTag(TagService::PUP);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);
        $wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

        $limit = 50;
        $offset = 0;
        $tagParam = $unscannedTagIsDisabled ? [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()] : TagService::UNSCANNED;
        $fileIdsAllowedToScan = [];
        while (count($fileIdsAllowedToScan) < $quantity) {
            $fileIds = $unscannedTagIsDisabled ? $this->tagService->getFileIdsWithoutTags($tagParam, $limit, $offset) : $this->tagService->getFileIdsWithTag($tagParam, $limit, $offset);
            if (empty($fileIds)) {
                break;
            }
            foreach ($fileIds as $fileId) {
                $node = $this->fileService->getNodeFromFileId($fileId);
                $filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
                if ($this->verdictService->isAllowedByAllowAndBlocklist($filePath)) {
                    $fileIdsAllowedToScan[] = $fileId;
                }
            }
            $offset += $limit;
        }
        
        return $fileIdsAllowedToScan;
    }
}
