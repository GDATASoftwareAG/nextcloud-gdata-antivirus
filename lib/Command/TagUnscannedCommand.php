<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\TagUnscannedService;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagUnscannedCommand extends Command {
	public function __construct(
		private TagUnscannedService $tagUnscannedService,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	/**
	 * @return void
	 */
	#[\Override]
	protected function configure(): void {
		$this->setName('gdatavaas:tag-unscanned');
		$this->setDescription('tags all files without tag from this app as unscanned');
	}

	/**
	 * @throws Exception
	 */
	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$logger = new ConsoleCommandLogger($this->logger, $output);
		$logger->info('tagging files as unscanned');
		$start = microtime(true);
		$taggedFilesCount = $this->tagUnscannedService
			->withLogger($logger)
			->run();
		$time_elapsed_secs = microtime(true) - $start;
		$logger->info("Tagged $taggedFilesCount files in $time_elapsed_secs seconds");
		return 0;
	}
}
