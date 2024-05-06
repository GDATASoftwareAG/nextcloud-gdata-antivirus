<?php

namespace OCA\GDataVaas\BackgroundJobs;

use Exception;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ScanJob extends QueuedJob
{
    private const APP_ID = "gdatavaas";

    private TagService $tagService;
    private VerdictService $scanService;
    private IConfig $appConfig;
    private IJobList $jobList;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, ITimeFactory $time, TagService $tagService, VerdictService $scanService, IConfig $appConfig, IJobList $jobList)
    {
        parent::__construct($time);

        $this->logger = $logger;
        $this->tagService = $tagService;
        $this->scanService = $scanService;
        $this->appConfig = $appConfig;
        $this->jobList = $jobList;
        
        $this->setAllowParallelRuns(false);
    }

    /**
     * @param $argument
     * @return void
     * @throws \OCP\DB\Exception if the database platform is not supported
     */
    protected function run($argument): void
    {
        $unscannedTagIsDisabled = $this->appConfig->getAppValue(self::APP_ID, 'disableUnscannedTag');
        $autoScan = $this->appConfig->getAppValue(self::APP_ID, 'autoScanFiles');
        if (!$autoScan) {
            return;
        }
        $autoScanOnlyNewFiles = $this->appConfig->getAppValue(self::APP_ID, 'scanOnlyNewFiles');
        $quantity = $this->appConfig->getAppValue(self::APP_ID, 'scanQueueLength');
        try {
            $quantity = intval($quantity);
        }
        catch (Exception) {
            $quantity = 5;
        }
        $quantity++;

        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $pupTag = $this->tagService->getTag(TagService::PUP);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);
        $unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);

        if ($unscannedTagIsDisabled) {
            if ($autoScanOnlyNewFiles) {
                $excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId()];
            } else {
                $excludedTagIds = [$unscannedTag->getId()];
            }
            $fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $quantity);
        } else {
            if ($autoScanOnlyNewFiles) {
                $fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $quantity, 0);
            } else {
                $fileIds = $this->tagService->getRandomTaggedFileIds([$maliciousTag->getId(), $cleanTag->getId(), $unscannedTag->getId(), $pupTag->getId()], $quantity, $unscannedTag);
            }
        }
        
        $moreFilesToScan = $quantity == count($fileIds);
        if ($moreFilesToScan) {
            array_pop($fileIds);
        }

        foreach ($fileIds as $fileId) {
            try {
                $this->scanService->scanFileById($fileId);
            } catch (Exception) {
                // Do nothing
            }
        }
        
        if ($moreFilesToScan){
            $scanJob = new ScanJob($this);
            $this->jobList->add($scanJob);
        }
    }
}
