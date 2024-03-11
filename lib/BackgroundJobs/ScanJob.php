<?php

namespace OCA\GDataVaas\BackgroundJobs;

use Exception;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;

class ScanJob extends TimedJob {
    
    private const APP_ID = "gdatavaas";

    private TagService $tagService;
	private VerdictService $scanService;
	private IConfig $appConfig;

	public function __construct(ITimeFactory $time, TagService $tagService, VerdictService $scanService, IConfig $appConfig) {
		parent::__construct($time);
        
        $this->tagService = $tagService;
        $this->scanService = $scanService;
        $this->appConfig = $appConfig;

		$this->setInterval(5);
		$this->setAllowParallelRuns(false);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
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
		if ($quantity == "") {$quantity = 5;}
        
        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);
        $unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);

        if ($unscannedTagIsDisabled) {
            if ($autoScanOnlyNewFiles) {
                $excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId()];
            } else {
                $excludedTagIds = [$unscannedTag->getId()];
            }
            $fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $quantity);
        }
        else {
            if ($autoScanOnlyNewFiles) {
                $fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $quantity, 0);
            } else {
                $fileIds = $this->tagService->getRandomTaggedFileIds([$maliciousTag->getId(), $cleanTag->getId(), $unscannedTag->getId()], $quantity, $unscannedTag);
            }
        }

		foreach ($fileIds as $fileId) {
            try {
                $this->scanService->scanFileById($fileId);
            } catch (Exception) {
                // Do nothing
            }
        }
	}
}
