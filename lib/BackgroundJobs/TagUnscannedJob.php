<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCA\GDataVaas\Service\TagService;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;

class TagUnscannedJob extends TimedJob {
    private TagService $tagService;

    public function __construct(ITimeFactory $time, TagService $tagService) {
        parent::__construct($time);
        
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
        $unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
        $maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
        $cleanTag = $this->tagService->getTag(TagService::CLEAN);
        
        $excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId()];
        
        $fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds);
        
        if (count($fileIds) == 0) {
            return;
        }
        
        foreach ($fileIds as $fileId) {
            if ($this->tagService->hasCleanOrMaliciousTag($fileId)) {
                continue;
            }
            $this->tagService->setTag($fileId, TagService::UNSCANNED);
        }
    }
}
