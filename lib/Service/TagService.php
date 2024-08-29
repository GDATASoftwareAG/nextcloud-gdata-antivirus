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
use function in_array;

class TagService {
	public const CLEAN = 'Clean';
	public const MALICIOUS = 'Malicious';
	public const PUP = 'Pup';
	public const UNSCANNED = 'Unscanned';
	public const WONT_SCAN = 'Won\'t scan';

	private ISystemTagManager $tagService;
	private ISystemTagObjectMapper $standardTagMapper;
	private ISystemTagObjectMapper $silentTagMapper;
	private DbFileMapper $dbFileMapper;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger, ISystemTagManager $systemTagManager, ISystemTagObjectMapper $standardTagMapper, ISystemTagObjectMapper $silentTagMapper, DbFileMapper $dbFileMapper) {
		$this->tagService = $systemTagManager;
		$this->standardTagMapper = $standardTagMapper;
		$this->silentTagMapper = $silentTagMapper;
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

	private function addTagToArray(string $tagName, array &$tagIds): array {
		try {
			array_push($tagIds, $this->getTag($tagName, false)->getId());
		} catch (TagNotFoundException) {
			$this->logger->debug("Tag not found: " . $tagName);
		}
		return $tagIds;
	}

	private function getVaasTagIds(): array {
		$vaasTagIds = [];
		$vaasTagIds = $this->addTagToArray(self::CLEAN, $vaasTagIds);
		$vaasTagIds = $this->addTagToArray(self::MALICIOUS, $vaasTagIds);
		$vaasTagIds = $this->addTagToArray(self::PUP, $vaasTagIds);
		$vaasTagIds = $this->addTagToArray(self::UNSCANNED, $vaasTagIds);
		$vaasTagIds = $this->addTagToArray(self::WONT_SCAN, $vaasTagIds);
		return $vaasTagIds;
	}

	/**
	 * @param int    $fileId  The file to tag
	 * @param string $tagName Which tag to set
	 * @param bool   $silent  If true, suppress events and activities
	 */
	public function setTag(int $fileId, string $tagName, bool $silent): void {
		$mapper = $silent ? $this->silentTagMapper : $this->standardTagMapper;
		$tag = $this->getTag($tagName);
		$filesTagIds = $mapper->getTagIdsForObjects($fileId, 'files');
		$vaasTagIds = $this->getVaasTagIds();

		if (isset($filesTagIds[$fileId])) {
			foreach ($filesTagIds[$fileId] as $tagId) {
				if ($tagId != $tag->getId() && in_array($tagId, $vaasTagIds)) {
					// Removal of outdated tags should always be silent
					$this->silentTagMapper->unassignTags(strval($fileId), 'files', [$tagId]);
				}
			}
			if (in_array($tag->getId(), $filesTagIds[$fileId])) {
				return;
			}
		}
		$mapper->assignTags(strval($fileId), 'files', [$tag->getId()]);

		$this->logger->debug("Tag set: " . $tagName . " for file " . $fileId);
	}

	/**
	 * Checks if a file has either CLEAN or MALICIOUS tag and creates these.
	 * @param int $fileId
	 * @return bool
	 */
	public function hasAnyButUnscannedTag(int $fileId): bool {
		$anyButUnscannedTagIds = [];
		$anyButUnscannedTagIds = $this->addTagToArray(self::CLEAN, $anyButUnscannedTagIds);
		$anyButUnscannedTagIds = $this->addTagToArray(self::MALICIOUS, $anyButUnscannedTagIds);
		$anyButUnscannedTagIds = $this->addTagToArray(self::PUP, $anyButUnscannedTagIds);
		$anyButUnscannedTagIds = $this->addTagToArray(self::WONT_SCAN, $anyButUnscannedTagIds);
		foreach ($anyButUnscannedTagIds as $tagId) {
			if ($this->standardTagMapper->haveTag([$fileId], 'files', $tagId)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if a file has UNSCANNED tag and creates it.
	 * @param int $fileId
	 * @return bool
	 */
	public function hasUnscannedTag(int $fileId): bool {
		try {
			return $this->standardTagMapper->haveTag([$fileId], 'files', $this->getTag(self::UNSCANNED, false)->getId());
		} catch (TagNotFoundException) {
			return false;
		}
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
	 * @param int $offset
	 * @return array
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithTag(string $tagName, int $limit, int $offset = 0): array {
		try {
			$tag = $this->getTag($tagName, false);
		} catch (TagNotFoundException) {
			return [];
		}
		return $this->dbFileMapper->getFileIdsWithTags([$tag->getId()], $limit, $offset);
	}

	/**
	 * @param array $excludedTagIds
	 * @param int $limit
	 * @param int $offset The offset of the first result, default is 0
	 * @return array
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithoutTags(array $excludedTagIds, int $limit, int $offset = 0): array {
		return $this->dbFileMapper->getFileIdsWithoutTags($excludedTagIds, $limit, $offset);
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
		$tagIds = [];
		$tagIds = $this->addTagToArray(self::CLEAN, $tagIds);
		$tagIds = $this->addTagToArray(self::MALICIOUS, $tagIds);
		$tagIds = $this->addTagToArray(self::PUP, $tagIds);
		$tagIds = $this->addTagToArray(self::WONT_SCAN, $tagIds);
		$allFiles = $this->dbFileMapper->getFilesCount();
		$scannedFiles = $this->dbFileMapper->getFileIdsWithTags($tagIds, $allFiles);
		return [
			'all' => $allFiles,
			'scanned' => count($scannedFiles)
		];
	}
}
