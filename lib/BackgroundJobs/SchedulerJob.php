<?php

namespace OCA\GDataVaas\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class SchedulerJob extends TimedJob
{
    private const APP_ID = "gdatavaas";
    private IConfig $appConfig;
    private IJobList $jobList;

    public function __construct(ITimeFactory $time, IConfig $appConfig, IJobList $jobList)
    {
        parent::__construct($time);

        $this->appConfig = $appConfig;
        $this->jobList = $jobList;
        
        $this->setInterval(5);
        $this->setAllowParallelRuns(false);
        $this->setTimeSensitivity(self::TIME_SENSITIVE);
    }

    /**
     * @param $argument
     * @return void
     */
    protected function run($argument): void
    {
        $autoScan = $this->appConfig->getAppValue(self::APP_ID, 'autoScanFiles');
        if (!$autoScan) {
            return;
        }
        
        $this->jobList->add(ScanJob::class);
    }
}