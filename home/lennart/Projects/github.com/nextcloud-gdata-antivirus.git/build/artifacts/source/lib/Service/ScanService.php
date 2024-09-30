<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\Vendor\Coduo\PHPHumanizer\NumberHumanizer;
use OCA\GDataVaas\Vendor\GuzzleHttp\Exception\ServerException;
use Generator;
use OCA\GDataVaas\AppInfo\Application;
use OCP\DB\Exception;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use OCA\GDataVaas\Vendor\VaasSdk\Exceptions\FileDoesNotExistException;
use OCA\GDataVaas\Vendor\VaasSdk\Exceptions\InvalidSha256Exception;
use OCA\GDataVaas\Vendor\VaasSdk\Exceptions\TimeoutException;
use OCA\GDataVaas\Vendor\VaasSdk\Exceptions\UploadFailedException;
use OCA\GDataVaas\Vendor\VaasSdk\Exceptions\VaasAuthenticationException;
class ScanService
{
    private const SCAN_TIME_SECONDS = 120;
    private \OCA\GDataVaas\Service\TagService $tagService;
    private \OCA\GDataVaas\Service\VerdictService $verdictService;
    private \OCA\GDataVaas\Service\FileService $fileService;
    private IAppConfig $appConfig;
    private LoggerInterface $logger;
    public function __construct(LoggerInterface $logger, \OCA\GDataVaas\Service\TagService $tagService, \OCA\GDataVaas\Service\VerdictService $verdictService, \OCA\GDataVaas\Service\FileService $fileService, IAppConfig $appConfig)
    {
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
    public function withLogger(LoggerInterface $logger): \OCA\GDataVaas\Service\ScanService
    {
        $this->logger = $logger;
        return $this;
    }
    /**
     * @return int
     * @throws Exception
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    public function run(): int
    {
        $startTime = time();
        $scanned = 0;
        foreach ($this->getFileIdsToScan() as $fileId) {
            try {
                $this->verdictService->scanFileById($fileId);
                $scanned += 1;
            } catch (EntityTooLargeException) {
                $this->logger->error("File {$fileId} is larger than " . NumberHumanizer::binarySuffix(\OCA\GDataVaas\Service\VerdictService::MAX_FILE_SIZE, 'de'));
            } catch (FileDoesNotExistException) {
                $this->logger->error("File {$fileId} does not exist.");
            } catch (InvalidSha256Exception) {
                $this->logger->error("Invalid SHA256 for file with ID {$fileId}");
            } catch (NotFoundException) {
                $this->logger->error("File {$fileId} not found");
            } catch (NotPermittedException) {
                $this->logger->error("Current settings do not permit scanning file wit ID {$fileId}.");
            } catch (TimeoutException) {
                $this->logger->error("Scanning timed out for file {$fileId}");
            } catch (UploadFailedException|ServerException) {
                $this->logger->error("File {$fileId} could not be scanned with GData VaaS because there was a temporary upstream server error");
            } catch (VaasAuthenticationException) {
                $this->logger->error("Authentication for VaaS scan failed. Please check your credentials.");
            } catch (\Exception $e) {
                $this->logger->error("Unexpected error while scanning file with id " . $fileId . ": " . $e->getMessage());
            }
            $elapsed = time() - $startTime;
            if ($elapsed > self::SCAN_TIME_SECONDS) {
                break;
            }
        }
        $this->logger->debug("Successfully scanned " . $scanned . " files");
        return $scanned;
    }
    /**
     * @param int $quantity
     * @return array
     * @throws Exception
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    private function getFileIdsToScan(): Generator
    {
        $unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');
        $maliciousTag = $this->tagService->getTag(\OCA\GDataVaas\Service\TagService::MALICIOUS);
        $pupTag = $this->tagService->getTag(\OCA\GDataVaas\Service\TagService::PUP);
        $cleanTag = $this->tagService->getTag(\OCA\GDataVaas\Service\TagService::CLEAN);
        $wontScanTag = $this->tagService->getTag(\OCA\GDataVaas\Service\TagService::WONT_SCAN);
        $limit = 50;
        $offset = 0;
        $tagParam = $unscannedTagIsDisabled ? [$maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()] : \OCA\GDataVaas\Service\TagService::UNSCANNED;
        while (\true) {
            $fileIds = $unscannedTagIsDisabled ? $this->tagService->getFileIdsWithoutTags($tagParam, $limit, $offset) : $this->tagService->getFileIdsWithTag($tagParam, $limit, $offset);
            if (empty($fileIds)) {
                break;
            }
            foreach ($fileIds as $fileId) {
                $node = $this->fileService->getNodeFromFileId($fileId);
                $filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
                if ($this->verdictService->isAllowedToScan($filePath)) {
                    yield $fileId;
                }
            }
            $offset += $limit;
        }
    }
}
