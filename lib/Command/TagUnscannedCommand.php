<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\TagUnscannedService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagUnscannedCommand extends Command {
	private TagUnscannedService $tagUnscannedService;
	private LoggerInterface $logger;

	public function __construct(IAppConfig $appConfig, TagService $tagService, LoggerInterface $logger) {
		parent::__construct();
		$this->logger = $logger;

		$this->tagUnscannedService = new TagUnscannedService($logger, $tagService, $appConfig);
	}

    protected function configure(): void {
		$this->setName('gdatavaas:tag-unscanned');
		$this->setDescription('tags all files without tag from this app as unscanned');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->tagUnscannedService->setLogger(new ConsoleCommandLogger($this->logger, $output));
		$this->tagUnscannedService->run();
		return 0;
	}
}
