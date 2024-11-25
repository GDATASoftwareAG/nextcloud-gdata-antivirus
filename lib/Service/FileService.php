<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\AppInfo\Application;
use OCP\App\IAppManager;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;

class FileService {

	private IUserMountCache $userMountCache;
	private IRootFolder $rootFolder;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;
	private IAppManager $appManager;
	private ITrashManager $trashManager;

	public function __construct(LoggerInterface $logger, IUserMountCache $userMountCache, IRootFolder $rootFolder, IAppConfig $appConfig, IAppManager $appManager, ITrashManager $trashManager) {
		$this->userMountCache = $userMountCache;
		$this->rootFolder = $rootFolder;
		$this->appConfig = $appConfig;
		$this->appManager = $appManager;
		$this->trashManager = $trashManager;
		$this->logger = $logger;
	}

	/**
	 * Checks if the 'Set prefix for malicious files' setting is activated and sets the prefix if it is.
	 * @param int $fileId
	 * @return void
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	public function setMaliciousPrefixIfActivated(int $fileId): void {
		if ($this->appConfig->getValueBool(Application::APP_ID, 'prefixMalicious')) {
			$file = $this->getNodeFromFileId($fileId);
			if (!str_starts_with($file->getName(), '[MALICIOUS] ')) {
				$newFileName = "[MALICIOUS] " . $file->getName();
				$file->move($file->getParent()->getPath() . '/' . $newFileName);
				$this->logger->info("Malicious prefix added to file " . $file->getName() . " (" . $fileId . ")");
			}
		}
	}

	/**
	 * @param int $fileId
	 * @return Node
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getNodeFromFileId(int $fileId): Node {
		$mounts = $this->userMountCache->getMountsForFileId($fileId);
		foreach ($mounts as $mount) {
			if ($node = $this->findNodeInMount($mount, $fileId)) {
				return $node;
			}
		}
		throw new NotFoundException();
	}

	/**
	 * @param $mount
	 * @param int $fileId
	 * @return Node|null
	 * @throws NotPermittedException
	 */
	private function findNodeInMount(\OCP\Files\Config\ICachedMountFileInfo $mount, int $fileId): ?Node {
		$mountUserFolder = $this->rootFolder->getUserFolder($mount->getUser()->getUID());
		$nodes = $mountUserFolder->getById($fileId);
		return $nodes[0] ?? null;
	}

	/**
	 * Moves a file to the quarantine folder if it is defined in the app settings.
	 * @param int $fileId
	 * @return void
	 * @throws InvalidPathException
	 * @throws LockedException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function moveFileToQuarantineFolderIfDefined(int $fileId): void {
		$quarantineFolderPath = $this->appConfig->getValueString(Application::APP_ID, 'quarantineFolder');
		if (empty($quarantineFolderPath)) {
			throw new InvalidPathException('Quarantine folder path is not defined');
		}
		$mounts = $this->userMountCache->getMountsForFileId($fileId);
		$mountUserFolder = $this->rootFolder->getUserFolder($mounts[0]->getUser()->getUID());
		try {
			$quarantine = $mountUserFolder->get($quarantineFolderPath);
		} catch (NotFoundException) {
			$quarantine = $mountUserFolder->newFolder($quarantineFolderPath);
			$this->logger->info("Quarantine folder created at " . $quarantine->getPath());
		}
		$file = $this->getNodeFromFileId($fileId);
		$file->move($quarantine->getPath() . '/' . $file->getName());
		$this->logger->info("File " . $file->getName() . " (" . $fileId . ") moved to quarantine folder.");
	}

	public function deleteFile(int $fileId): void {
		$file = $this->getNodeFromFileId($fileId);
		$file->unlock(\OCP\Lock\ILockingProvider::LOCK_SHARED);
		$trashEnabled = $this->appManager->isEnabledForUser('files_trashbin');
		if ($trashEnabled) {
			$this->trashManager->pauseTrash();
		}
		$file->delete();
		if ($trashEnabled) {
			$this->trashManager->resumeTrash();
		}
		$this->logger->info("File " . $file->getName() . " (" . $fileId . ") deleted.");
	}
}
