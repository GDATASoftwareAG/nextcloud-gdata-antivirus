<?php

/**
 * Copyright (c) 2014 Victor Dubiniuk <victor.dubiniuk@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\GDataVaas;

use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\GDataVaas\Activity\Provider;
use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\MailService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\Activity\IManager as ActivityManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\InvalidContentException;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use VaasSdk\Message\Verdict;

class AvirWrapper extends Wrapper {
	/**
	 * Modes that are used for writing
	 * @var array
	 */
	private $writingModes = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

	protected VerdictService $verdictService;
    protected MailService $mailService;
    protected IConfig $appConfig;

	/** @var IL10N */
	protected $l10n;

	/** @var LoggerInterface */
	protected $logger;

	/** @var ActivityManager */
	protected $activityManager;

    /** @var bool */
	protected $isHomeStorage;

	/** @var bool */
	private $shouldScan = true;

	/** @var bool */
	private $trashEnabled;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->verdictService = $parameters['verdictService'];
        $this->mailService = $parameters['mailService'];
        $this->appConfig = $parameters['appConfig'];
		$this->logger = $parameters['logger'];
		$this->activityManager = $parameters['activityManager'];
		$this->isHomeStorage = $parameters['isHomeStorage'];
		$this->trashEnabled = $parameters['trashEnabled'];

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $parameters['eventDispatcher'];
	}

	/**
	 * Asynchronously scan data that are written to the file
	 * @param string $path
	 * @param string $mode
	 * @return resource | false
	 */
	public function fopen($path, $mode) {
		$stream = $this->storage->fopen($path, $mode);

		/*
		 * Only check when
		 *  - it is a resource
		 *  - it is a writing mode
		 *  - if it is a homestorage it starts with files/
		 *  - if it is not a homestorage we always wrap (external storages)
		 */
		if ($this->shouldWrap($path) && is_resource($stream) && $this->isWritingMode($mode)) {
			$stream = $this->wrapSteam($path, $stream);
		}
		return $stream;
	}

	public function writeStream(string $path, $stream, ?int $size = null): int {
		if ($this->shouldWrap($path)) {
			$stream = $this->wrapSteam($path, $stream);
		}
		return parent::writeStream($path, $stream, $size);
	}

    public function rename($source, $target) {
        if ($this->shouldWrap($source)) {
            // After the upload apps/dav/lib/Connector/Sabre/File.php calls moveFromStorage which calls rename
            $this->logger->debug(sprintf("rename(%s, %s)", $source, $target));
            $this->verdictService->onRename($this->getLocalFile($source), $this->getLocalFile($target));
        }
        return parent::rename($source, $target);
    }

	private function shouldWrap(string $path): bool {
		return $this->shouldScan
			&& (!$this->isHomeStorage
				|| (strpos($path, 'files/') === 0
					|| strpos($path, '/files/') === 0)
			);
	}

	private function wrapSteam(string $path, $stream) {
		try {
			$logger = $this->logger;
			return CallbackReadDataWrapper::wrap(
				$stream,
				null,
				null,
				function () use ($path, $logger) {
					$localPath = $this->getLocalFile($path);
					$filesize = $this->filesize($path);
					$logger->debug("Closing " . $localPath . " with size " . $filesize);

					if ($filesize > VerdictService::MAX_FILE_SIZE) {
						return;
					}

					try {
						$verdict = $this->verdictService->scan($localPath);
					} catch (\Exception $e) {
						$this->logger->error($e->getMessage(), ['exception' => $e]);
						return;
					}
					$logger->debug("Verdict for  " . $localPath . " is " . $verdict->Verdict->value);

					if ($verdict->Verdict == Verdict::MALICIOUS) {
						$logger->debug("Removing malicious file  " . $localPath);

						//prevent from going to trashbin
						if ($this->trashEnabled) {
							/** @var ITrashManager $trashManager */
							$trashManager = \OC::$server->query(ITrashManager::class);
							$trashManager->pauseTrash();
						}

						$owner = $this->getOwner($path);
						$this->unlink($path);

						if ($this->trashEnabled) {
							/** @var ITrashManager $trashManager */
							$trashManager = \OC::$server->query(ITrashManager::class);
							$trashManager->resumeTrash();
						}

						$this->logger->warning(
							'Infected file deleted. ' . $verdict->Detection
								. ' Account: ' . $owner . ' Path: ' . $path,
							['app' => 'gdatavaas']
						);

						$activity = $this->activityManager->generateEvent();
						$activity->setApp(Application::APP_ID)
							->setSubject(Provider::SUBJECT_VIRUS_DETECTED_UPLOAD, [$verdict->Detection ?? "no_detection_name"])
							->setMessage(Provider::MESSAGE_FILE_DELETED)
							->setObject('', 0, $path)
							->setAffectedUser($owner)
							->setType(Provider::TYPE_VIRUS_DETECTED);
						$this->activityManager->publish($activity);
                        
                        if ($this->appConfig->getAppValue(Application::APP_ID, 'sendMailOnVirusUpload')) {
                            $this->mailService->notifyMaliciousUpload($verdict, $path, $owner, $filesize);
                        }

						throw new InvalidContentException(
							sprintf(
								'Virus %s is detected in the file. Upload cannot be completed.',
								$verdict->Detection
							)
						);
					}
				}
			);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}
		return $stream;
	}

	/**
	 * Checks whether passed mode is suitable for writing
	 * @param string $mode
	 * @return bool
	 */
	private function isWritingMode($mode) {
		// Strip unessential binary/text flags
		$cleanMode = str_replace(
			['t', 'b'],
			['', ''],
			$mode
		);
		return in_array($cleanMode, $this->writingModes);
	}
}
