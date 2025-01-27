<?php

namespace OCA\GDataVaas\Service;

use OC;
use OC\User\NoUserException;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\GDataVaas\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;

class FileService {

	private IUserMountCache $userMountCache;
	private IRootFolder $rootFolder;
	private IAppConfig $appConfig;
	private LoggerInterface $logger;
    private IAppManager $appManager;

	public function __construct(LoggerInterface $logger, IUserMountCache $userMountCache, IRootFolder $rootFolder, IAppConfig $appConfig, IAppManager $appManager) {
		$this->userMountCache = $userMountCache;
		$this->rootFolder = $rootFolder;
		$this->appConfig = $appConfig;
		$this->logger = $logger;
        $this->appManager = $appManager;
	}

    /**
     * Checks if the 'Set prefix for malicious files' setting is activated and sets the prefix if it is.
     * @param int $fileId
     * @return void
     * @throws InvalidPathException
     * @throws LockedException
     * @throws NoUserException
     * @throws NotFoundException
     * @throws NotPermittedException
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
     * @throws NoUserException
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
     * @param ICachedMountFileInfo $mount
     * @param int $fileId
     * @return Node|null
     * @throws NoUserException
     * @throws NotPermittedException
     */
	private function findNodeInMount(ICachedMountFileInfo $mount, int $fileId): ?Node {
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
     * @throws NoUserException
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

    /**
     * @param int $fileId
     * @throws InvalidPathException
     * @throws LockedException
     * @throws NoUserException
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    public function deleteFile(int $fileId): void {
		$file = $this->getNodeFromFileId($fileId);
		$file->unlock(ILockingProvider::LOCK_SHARED);
        if ($this->appManager->isEnabledForUser('files_trashbin')){
            $trashManager = OC::$server->get(ITrashManager::class);
            $trashManager->pauseTrash();
            $file->delete();
            $trashManager->resumeTrash();
        } else {
            $file->delete();
        }
		$this->logger->info("File " . $file->getName() . " (" . $fileId . ") deleted.");
	}
}
