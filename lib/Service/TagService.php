<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\Db\DbFileMapper;
use OCP\DB\Exception;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use Psr\Log\LoggerInterface;

class TagService {
	public const CLEAN = 'Clean';
	public const MALICIOUS = 'Malicious';
	public const PUP = 'Pup';
	public const UNSCANNED = 'Unscanned';
	public const WONT_SCAN = 'Won\'t scan';

	private ISystemTagManager $tagService;
	private ISystemTagObjectMapper $tagMapper;
	private DbFileMapper $dbFileMapper;
	private LoggerInterface $logger;


	public function __construct(LoggerInterface $logger, ISystemTagManager $systemTagManager, ISystemTagObjectMapper $objectMapper, DbFileMapper $dbFileMapper) {
		$this->tagService = $systemTagManager;
		$this->tagMapper = $objectMapper;
		$this->dbFileMapper = $dbFileMapper;
		$this->logger = $logger;
	}

	/**
	 * @param string $name
	 * @param bool $create
	 * @return ISystemTag
	 * @throws TagAlreadyExistsException if tag already exists
	 * @throws TagNotFoundException if tag does not exist and $create=false
	 */
	public function getTag(string $name, bool $create = true): ISystemTag {
		try {
			$tag = $this->tagService->getTag($name, true, false);
		} catch (TagNotFoundException) {
			if (!$create) {
				throw new TagNotFoundException();
			}
			$tag = $this->tagService->createTag($name, true, false);
			$this->logger->debug("Tag created: " . $name);
		}
		return $tag;
	}

	/**
	 * @param int $fileId
	 * @param string $tagName
	 * @return void
	 */
	public function setTag(int $fileId, string $tagName): void {
		$tag = $this->getTag($tagName);
		$this->tagMapper->assignTags(strval($fileId), 'files', [$tag->getId()]);
		$this->logger->debug("Tag set: " . $tagName . " for file " . $fileId);
	}

	/**
	 * @param string $tagName
	 * @param int $fileId
	 * @return bool
	 */
	public function removeTagFromFile(string $tagName, int $fileId): bool {
		try {
			$tag = $this->tagService->getTag($tagName, true, false);
			$this->tagMapper->unassignTags(strval($fileId), 'files', [$tag->getId()]);
			$this->logger->debug("Tag removed: " . $tagName . " for file " . $fileId);
			return true;
		} catch (TagNotFoundException) {
			return false;
		}
	}

	public function removeAllTagsFromFile(int $fileId): void {
		$this->removeTagFromFile(TagService::CLEAN, $fileId);
		$this->removeTagFromFile(TagService::MALICIOUS, $fileId);
		$this->removeTagFromFile(TagService::PUP, $fileId);
		$this->removeTagFromFile(TagService::UNSCANNED, $fileId);
		$this->removeTagFromFile(TagService::WONT_SCAN, $fileId);
	}

	/**
	 * Checks if a file has either CLEAN or MALICIOUS tag and creates these.
	 * @param int $fileId
	 * @return bool
	 */
	public function hasAnyButUnscannedTag(int $fileId): bool {
		if (
			$this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::CLEAN)->getId()) ||
			$this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::MALICIOUS)->getId()) ||
			$this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::PUP)->getId()) ||
			$this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::WONT_SCAN)->getId())
		) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if a file has UNSCANNED tag and creates it.
	 * @param int $fileId
	 * @return bool
	 */
	public function hasUnscannedTag(int $fileId): bool {
		return $this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::UNSCANNED)->getId());
	}

    /**
     * Checks if a file has any Vaas tag.
     * @param int $fileId
     * @return bool
     */
    public function hasAnyVaasTag(int $fileId): bool {
        return $this->hasAnyButUnscannedTag($fileId) || $this->hasUnscannedTag($fileId);
    }

	/**
	 * @param string $tagName
	 * @param int $limit Count of object ids you want to get
	 * @return array
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithTag(string $tagName, int $limit): array {
		try {
			$tag = $this->getTag($tagName, false);
		} catch (TagNotFoundException) {
			return [];
		}
		return $this->dbFileMapper->getFileIdsWithTags([$tag->getId()], $limit);
	}

	/**
	 * @param array $excludedTagIds
	 * @param int $limit
	 * @return array
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithoutTags(array $excludedTagIds, int $limit): array {
		return $this->dbFileMapper->getFileIdsWithoutTags($excludedTagIds, $limit);
	}

	/**
	 * Get file ids that have any of the given tags
	 * @param array $tagIds The tags to get the file ids for
	 * @param int $limit The count of file ids you want to get
	 * @param ISystemTag|null $priorityTagId Tag id to prioritize over the others
	 * @return array
	 * @throws TagNotFoundException if a tag does not exist
	 * @throws Exception If the database platform is not supported
	 */
	public function getRandomTaggedFileIds(array $tagIds, int $limit, ?ISystemTag $priorityTagId = null): array {
		$objectIdsPriority = [];
		if ($priorityTagId !== null) {
			$objectIdsPriority = $this->dbFileMapper->getFileIdsWithTags([$priorityTagId->getId()], $limit);
			shuffle($objectIdsPriority);
		}
		if (count($objectIdsPriority) < $limit) {
			$objectIds = $this->dbFileMapper->getFileIdsWithTags($tagIds, $limit - count($objectIdsPriority));
			shuffle($objectIds);
			return array_merge($objectIdsPriority, $objectIds);
		}
		return $objectIdsPriority;
	}

	/**
	 * Delete a tag by name if it exists
	 * @param string $tagName
	 * @return void
	 */
	public function removeTag(string $tagName): void {
		try {
			$tag = $this->getTag($tagName, false);
		} catch (TagNotFoundException) {
			return;
		}
		$this->tagService->deleteTags([$tag->getId()]);
		$this->logger->debug("Tag removed: " . $tagName);
	}

	/**
	 * Removes all tags
	 * @return void
	 */
	public function resetAllTags(): void {
		$this->removeTag(self::CLEAN);
		$this->removeTag(self::MALICIOUS);
		$this->removeTag(self::UNSCANNED);
		$this->removeTag(self::PUP);
		$this->removeTag(self::WONT_SCAN);
		$this->logger->info("All tags removed");
	}

    /**
     * @return array
     * @throws Exception
     */
    public function getScannedFilesCount(): array {
        $tags = [
            $this->getTag(self::CLEAN)->getId(),
            $this->getTag(self::MALICIOUS)->getId(),
            $this->getTag(self::PUP)->getId(),
            $this->getTag(self::WONT_SCAN)->getId()
        ];
        $allFiles = $this->dbFileMapper->getFilesCount();
        $scannedFiles = $this->dbFileMapper->getFileIdsWithTags($tags, $allFiles);
        return [
            'all' => $allFiles,
            'scanned' => count($scannedFiles)
        ];
    }
}
