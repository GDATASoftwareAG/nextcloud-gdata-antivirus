<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Db\DbFileMapper;
use OCA\GDataVaas\Service\FileService;
use OCA\GDataVaas\Service\MailService;
use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\SystemTag\TagNotFoundException;
use Psr\Log\LoggerInterface;

class NotifyAdminJob extends TimedJob {
    private IAppConfig $appConfig;
    private TagService $tagService;
    private DbFileMapper $dbFileMapper;
    private MailService $mailService;
    private LoggerInterface $logger;
    private FileService $fileService;
    
    public function __construct(ITimeFactory    $time,
                                IAppConfig      $appConfig,
                                TagService      $tagService,
                                DbFileMapper    $dbFileMapper,
                                LoggerInterface $logger,
                                MailService     $mailService,
                                FileService     $fileService)
    {
        parent::__construct($time);
        
        $this->appConfig = $appConfig;
        $this->tagService = $tagService;
        $this->dbFileMapper = $dbFileMapper;
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->fileService = $fileService;

        $this->setInterval(7 * 24 * 3600);
        $this->setAllowParallelRuns(false);
        $this->setTimeSensitivity(self::TIME_INSENSITIVE);
    }

    /**
     * @param $argument
     * @return void
     * @throws Exception
     * @throws NotFoundException
     * @throws NotPermittedException
     * @throws \Exception
     */
    protected function run($argument): void {
        $notifyAdminEnabled = $this->appConfig->getValueBool(Application::APP_ID, "notifyAdminEnabled");
        if (!$notifyAdminEnabled) {
            return;
        }
        
        try {
            $maliciousTagId = $this->tagService->getTag(TagService::MALICIOUS, false)->getId();
        }
        catch (TagNotFoundException){
            return;
        }
        $allFiles = $this->dbFileMapper->getFilesCount();
        $maliciousFiles = $this->dbFileMapper->getFileIdsWithTags([$maliciousTagId], $allFiles);
        
        $this->logger->info("Found " . count($maliciousFiles) . " malicious files out of " . $allFiles . " total files");
        
        if (count($maliciousFiles) > 0) {
            $this->logger->debug("Sending notification to admin");
            $this->mailService->notifyWeeklySummary($this->getFilesFromFileIds($maliciousFiles));
        }
        else
        {
            $this->logger->info("No malicious files found, no weekly summary sent");
        }
    }

    /**
     * @param array $fileIds
     * @return array
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    private function getFilesFromFileIds(array $fileIds): array {
        $files = [];
        foreach ($fileIds as $fileId) {
            $file = $this->fileService->getNodeFromFileId($fileId);
            if ($file instanceof File) {
                $files[] = $file;
            }
        }
        return $files;
    }
}
