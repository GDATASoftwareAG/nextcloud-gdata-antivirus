<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\Service\TagService;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IConfig;

class TagUnscannedJob extends TimedJob
{
    private const APP_ID = "gdatavaas";

    private TagService $tagService;
    private IConfig $appConfig;

    public function __construct(ITimeFactory $time, IConfig $appConfig, TagService $tagService)
    {
        parent::__construct($time);

        $this->appConfig = $appConfig;
        $this->tagService = $tagService;

        $this->setInterval(5 * 60);
        $this->setAllowParallelRuns(false);
        $this->setTimeSensitivity(self::TIME_INSENSITIVE);
    }

    /**
     * @param $argument
     * @return void
     * @throws Exception if the database platform is not supported
     */
    protected function run($argument): void
    {
        $unscannedTagIsDisabled = $this->appConfig->getAppValue(self::APP_ID, 'disableUnscannedTag');
        if ($unscannedTagIsDisabled) {
            $this->tagService->removeTag(TagService::UNSCANNED);
            return;
        }

        $unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $pupTag = $this->tagService->getTag(TagService::PUP);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);

        $excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId()];

        $fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, 1000);

        if (count($fileIds) == 0) {
            return;
        }

        foreach ($fileIds as $fileId) {
            if ($this->tagService->hasCleanMaliciousOrPupTag($fileId)) {
                continue;
            }
            $this->tagService->setTag($fileId, TagService::UNSCANNED);
        }
    }
}
