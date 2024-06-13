<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\ScanService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\DB\Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command {
	private ScanService $scanService;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $scanService, IAppConfig $appConfig) {
		parent::__construct();

		$this->logger = $logger;
		$this->scanService = new ScanService($logger, $tagService, $scanService, $appConfig);
	}

    protected function configure(): void {
		$this->setName('gdatavaas:scan');
		$this->setDescription('scan files for malware');
	}

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->scanService->setLogger(new ConsoleCommandLogger($this->logger, $output));
		$this->scanService->run();
		return 0;
	}
}
