<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCA\GDataVaas\Service\TagService;
use OCP\SystemTag\TagNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetTagIdCommand extends Command {
	public const TAG_NAME = 'tag-name';

	private LoggerInterface $logger;
	private TagService $tagService;

	public function __construct(TagService $tagService, LoggerInterface $logger) {
		parent::__construct();
		$this->logger = $logger;
		$this->tagService = $tagService;
	}

	/**
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('gdatavaas:get-tag-id');
		$this->setDescription('Gets the ID of a tag');
		$this->addArgument(self::TAG_NAME, InputArgument::REQUIRED, "Tag name you want to get the id for.");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$logger = new ConsoleCommandLogger($this->logger, $output);
		$tagName = $input->getArgument('tag-name');
		try {
			$tag = $this->tagService->getTag($tagName, false);
		} catch (TagNotFoundException $e) {
			$logger->error("Tag not found: ".$tagName." ".$e->getMessage());
			return 1;
		}
		$logger->info("tag: ".$tag->getId());
		return 0;
	}
}
