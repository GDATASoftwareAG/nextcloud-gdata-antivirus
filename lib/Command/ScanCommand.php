<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\ScanService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command {
	private ScanService $scanService;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $scanService, IConfig $appConfig) {
		parent::__construct();

		$this->logger = $logger;
		$this->scanService = new ScanService($logger, $tagService, $scanService, $appConfig);
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('gdatavaas:scan');
		$this->setDescription('scan files for malware');
	}

	/**
	 * @param $argument
	 * @return void
	 * @throws \OCP\DB\Exception if the database platform is not supported
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->scanService->setLogger(new ConsoleCommandLogger($this->logger, $output));
		$this->scanService->run();
		return 0;
	}
}
