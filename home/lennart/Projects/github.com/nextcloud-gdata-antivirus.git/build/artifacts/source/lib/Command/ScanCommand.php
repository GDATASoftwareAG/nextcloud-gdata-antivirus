<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\ScanService;
use OCA\GDataVaas\Service\TagUnscannedService;
use OCP\DB\Exception;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Command\Command;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Input\InputInterface;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Output\OutputInterface;
class ScanCommand extends Command
{
    private ScanService $scanService;
    private TagUnscannedService $tagUnscannedService;
    private LoggerInterface $logger;
    public function __construct(ScanService $scanService, TagUnscannedService $tagUnscannedService, LoggerInterface $logger)
    {
        parent::__construct();
        $this->scanService = $scanService;
        $this->tagUnscannedService = $tagUnscannedService;
        $this->logger = $logger;
    }
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('gdatavaas:scan');
        $this->setDescription('scan files for malware');
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleCommandLogger($this->logger, $output);
        $logger->info("scanning files");
        $start = microtime(\true);
        $this->tagUnscannedService->withLogger($logger)->run();
        $scannedFilesCount = $this->scanService->withLogger($logger)->run();
        $time_elapsed_secs = microtime(\true) - $start;
        $logger->info("Scanned {$scannedFilesCount} files in {$time_elapsed_secs} seconds");
        return 0;
    }
}
