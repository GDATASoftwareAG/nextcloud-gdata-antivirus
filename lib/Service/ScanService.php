<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Service;

use Coduo\PHPHumanizer\NumberHumanizer;
use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use VaasSdk\Exceptions\VaasAuthenticationException;

class ScanService {

	private const SCAN_TIME_SECONDS = 120;

	private TagService $tagService;
	private VerdictService $verdictService;
	private FileService $fileService;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(
		LoggerInterface $logger,
		TagService $tagService,
		VerdictService $verdictService,
		FileService $fileService,
		IAppConfig $appConfig,
	) {
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
	 * Run the scan service to scan files.
	 * @return int
	 * @throws Exception
	 */
	public function run(): int {
		$startTime = time();
		$scanned = 0;

		$fileIds = $this->getFileIdsToScan();
		foreach ($fileIds as $fileId) {
			try {
				$this->verdictService->scanFileById($fileId);
				$scanned += 1;
			} catch (EntityTooLargeException) {
				$this->logger->error(
					"File $fileId is larger than
					" . NumberHumanizer::binarySuffix(VerdictService::MAX_FILE_SIZE, 'de'));
			} catch (NotFoundException) {
				$this->logger->error("File $fileId not found");
			} catch (NotPermittedException) {
				$this->logger->error("Current settings do not permit scanning file wit ID $fileId.");
			} catch (VaasAuthenticationException) {
				$this->logger->error('Authentication for VaaS scan failed. Please check your credentials.');
			} catch (\Exception $e) {
				$this->logger->error('Unexpected error while scanning file with id ' . $fileId . ': ' . $e->getMessage());
			}

			$elapsed = time() - $startTime;
			if ($elapsed > self::SCAN_TIME_SECONDS) {
				break;
			}
		}

		$this->logger->debug('Successfully scanned ' . $scanned . ' files');
		return $scanned;
	}

	/**
	 * Get file IDs that need to be scanned.
	 * @return array
	 * @throws Exception
	 */
	private function getFileIdsToScan(): array {
		$unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');

		$maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
		$pupTag = $this->tagService->getTag(TagService::PUP);
		$cleanTag = $this->tagService->getTag(TagService::CLEAN);
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		$limit = 50;
		$offset = 0;
		$tagParam = $unscannedTagIsDisabled
			? [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()]
			: TagService::UNSCANNED;
		$startTime = time();
		$fileIdsToScan = [];
		while (true) {
			$fileIds
				= $unscannedTagIsDisabled
					? $this->tagService->getFileIdsWithoutTags($tagParam, $limit, $offset)
					: $this->tagService->getFileIdsWithTag($tagParam, $limit, $offset);
			if (empty($fileIds)) {
				break;
			}
			foreach ($fileIds as $fileId) {
				try {
					$node = $this->fileService->getNodeFromFileId($fileId);
					$filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
					if (is_readable($filePath) && $this->verdictService->isAllowedToScan($filePath)) {
						$fileIdsToScan[] = $fileId;
					} else {
						$this->logger->debug("File with ID $fileId is not readable or not allowed to scan, skipping.");
					}
				} catch (NotFoundException $e) {
					$this->logger->error("File with ID $fileId not found, skipping: " . $e->getMessage());
				} catch (NotPermittedException $e) {
					$this->logger->error(
						"Current settings do not permit scanning file with ID $fileId, skipping: " . $e->getMessage()
					);
				} catch (\Exception $e) {
					$this->logger->error("Unexpected error while processing file with ID $fileId: " . $e->getMessage());
				}
			}
			$offset += $limit;
			$elapsed = time() - $startTime;
			if (($elapsed > (self::SCAN_TIME_SECONDS / 2)) && count($fileIdsToScan) > 0) {
				break;
			}
		}

		$this->logger->debug('Found ' . count($fileIdsToScan) . ' files to scan');
		return $fileIdsToScan;
	}
}
