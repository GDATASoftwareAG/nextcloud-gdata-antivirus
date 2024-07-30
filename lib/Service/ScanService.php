<?php

namespace OCA\GDataVaas\Service;

use Coduo\PHPHumanizer\NumberHumanizer;
use GuzzleHttp\Exception\ServerException;
use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;

class ScanService {
	private TagService $tagService;
	private VerdictService $verdictService;
	private FileService $fileService;
	private IConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $verdictService, FileService $fileService, IConfig $appConfig) {
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
        $quantity = $this->appConfig->getAppValue(Application::APP_ID, 'scanQueueLength');
        try {
            $quantity = intval($quantity);
        } catch (\Exception) {
            $quantity = 5;
        }
		
		$fileIds = $this->getFileIdsToScan($quantity);
		$this->logger->debug("Scanning " . count($fileIds) . " files");
		
		foreach ($fileIds as $fileId) {
			try {
				$this->verdictService->scanFileById($fileId);
			} catch (EntityTooLargeException) {
                $this->logger->error("File $fileId is larger than " . NumberHumanizer::binarySuffix(VerdictService::MAX_FILE_SIZE, 'de'));
            } catch (FileDoesNotExistException) {
                $this->logger->error("File $fileId does not exist.");
            } catch (InvalidSha256Exception) {
                $this->logger->error("Invalid SHA256 for file with ID $fileId");
            } catch (NotFoundException) {
                $this->logger->error("File $fileId not found");
            } catch (NotPermittedException) {
                $this->logger->error("Current settings do not permit scanning file wit ID $fileId.");
            } catch (TimeoutException) {
                $this->logger->error("Scanning timed out for file $fileId");
            } catch (UploadFailedException|ServerException) {
                $this->logger->error("File $fileId could not be scanned with GData VaaS because there was a temporary upstream server error");
            } catch (VaasAuthenticationException) {
                $this->logger->error("Authentication for VaaS scan failed. Please check your credentials.");
            } catch (\Exception $e) {
				$this->logger->error("Unexpected error while scanning file with id " . $fileId . ": " . $e->getMessage());
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
		$unscannedTagIsDisabled = $this->appConfig->getAppValue(Application::APP_ID, 'disableUnscannedTag');
		
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
				if ($this->verdictService->isAllowedToScan($filePath)) {
					$fileIdsAllowedToScan[] = $fileId;
				}
			}
			$offset += $limit;
		}
		
		return $fileIdsAllowedToScan;
	}
}
