<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class TagUnscannedCommand extends Command {
	private const APP_ID = "gdatavaas";

	private TagService $tagService;
	private IConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(IConfig $appConfig, TagService $tagService, LoggerInterface $logger) {
		parent::__construct();

		$this->appConfig = $appConfig;
		$this->tagService = $tagService;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('gdatavaas:tag-unscanned');
		$this->setDescription('tags all files without tag from this app as unscanned');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$unscannedTagIsDisabled = $this->appConfig->getAppValue(self::APP_ID, 'disableUnscannedTag');
		if ($unscannedTagIsDisabled) {
			$this->tagService->removeTag(TagService::UNSCANNED);
			$this->logger->info("Unscanned Tag is disabled, exiting.");
			return 0;
		}

		$this->logger->debug("Tagging unscanned files");

		$unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
		$maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
		$pupTag = $this->tagService->getTag(TagService::PUP);
		$cleanTag = $this->tagService->getTag(TagService::CLEAN);
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		$excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];

		$fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, 10000);
		$this->logger->info("Found " . count($fileIds) . " files without tags from this app");

		foreach ($fileIds as $fileId) {
			if ($this->tagService->hasAnyButUnscannedTag($fileId)) {
				continue;
			}
			$this->tagService->setTag($fileId, TagService::UNSCANNED);
		}

		$this->logger->debug("Tagged " . count($fileIds) . " unscanned files");

		return 0;
	}
}
