<?php

namespace OCA\GDataVaas\Command;

use Exception;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command {
	private const APP_ID = "gdatavaas";

	private TagService $tagService;
	private VerdictService $scanService;
	private IConfig $appConfig;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $scanService, IConfig $appConfig) {
		parent::__construct();

		$this->logger = $logger;
		$this->tagService = $tagService;
		$this->scanService = $scanService;
		$this->appConfig = $appConfig;
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
		$unscannedTagIsDisabled = $this->appConfig->getAppValue(self::APP_ID, 'disableUnscannedTag');
		$quantity = $this->appConfig->getAppValue(self::APP_ID, 'scanQueueLength');
		try {
			$quantity = intval($quantity);
		} catch (Exception) {
			$quantity = 5;
		}

		$maliciousTag = $this->tagService->getTag(TagService::MALICIOUS);
		$pupTag = $this->tagService->getTag(TagService::PUP);
		$cleanTag = $this->tagService->getTag(TagService::CLEAN);
		$unscannedTag = $this->tagService->getTag(TagService::UNSCANNED);
		$wontScanTag = $this->tagService->getTag(TagService::WONT_SCAN);

		if ($unscannedTagIsDisabled) {
			$excludedTagIds = [$unscannedTag->getId(), $maliciousTag->getId(), $cleanTag->getId(), $pupTag->getId(), $wontScanTag->getId()];
			$fileIds = $this->tagService->getFileIdsWithoutTags($excludedTagIds, $quantity);
		} else {
			$fileIds = $this->tagService->getFileIdsWithTag(TagService::UNSCANNED, $quantity, 0);
		}

		$this->logger->debug("Scanning files");

		foreach ($fileIds as $fileId) {
			try {
				$this->scanService->scanFileById($fileId);
			} catch (Exception $e) {
				$output->writeln("Failed to scan file with id " . $fileId . ": " . $e->getMessage());
			}
		}

		$this->logger->debug("Scanned " . count($fileIds) . " files");
        return 0;
	}
}
