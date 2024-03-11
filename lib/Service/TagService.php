<?php

namespace OCA\GDataVaas\Service;

use OCA\GDataVaas\Db\DbFileMapper;
use OCP\DB\Exception;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\ISystemTagManager;

class TagService
{
	public const CLEAN = 'Clean';
	public const MALICIOUS = 'Malicious';
    public const UNSCANNED = 'Unscanned';
    
	private ISystemTagManager $tagService;
	private ISystemTagObjectMapper $tagMapper;
    private DbFileMapper $dbFileMapper;


    public function __construct(ISystemTagManager $systemTagManager, ISystemTagObjectMapper $objectMapper, DbFileMapper $dbFileMapper) {
        $this->tagService = $systemTagManager;
        $this->tagMapper = $objectMapper;
        $this->dbFileMapper = $dbFileMapper;
	}

    /**
     * @param string $name
     * @param bool $create
     * @return ISystemTag
     * @throws TagAlreadyExistsException if tag already exists
     * @throws TagNotFoundException if tag does not exist and $create=false
     */
    public function getTag(string $name, bool $create=true) : ISystemTag {
		try {
			$tag = $this->tagService->getTag($name, true, true);
		} catch (TagNotFoundException) {
			if (!$create) {
				throw new TagNotFoundException();
			}
			$tag = $this->tagService->createTag($name, true, true);
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
	}

    /**
     * @param string $tagName
     * @param int $fileId
     * @return bool
     */
    public function removeTagFromFile(string $tagName, int $fileId) :bool {
		try {
			$tag = $this->tagService->getTag($tagName, true, true);
			$this->tagMapper->unassignTags(strval($fileId), 'files', [$tag->getId()]);
			return true;
		} catch (TagNotFoundException) {
			return false;
		}
	}

    /**
     * Checks if a file has either CLEAN or MALICIOUS tag and creates these.
     * @param int $fileId
     * @return bool
     */
    public function hasCleanOrMaliciousTag(int $fileId): bool
	{
        if ($this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::CLEAN)->getId()) ||
            $this->tagMapper->haveTag([$fileId], 'files', $this->getTag(self::MALICIOUS)->getId())) {
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
     * @param string $tagName
     * @param int $limit Count of object ids you want to get
     * @param string $offset The last object id you already received
     * @return array
     */
    public function getFileIdsWithTag(string $tagName, int $limit, string $offset): array {
        try {
            $tag = $this->getTag($tagName, false);
        } catch (TagNotFoundException) {
            return [];
        }        
        return $this->tagMapper->getObjectIdsForTags([$tag->getId()], 'files', $limit, $offset);
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
     * @param ISystemTag|null $priorTagId Tag id to prioritize over the others
     * @return array
     * @throws TagNotFoundException if a tag does not exist
     */
    public function getRandomTaggedFileIds(array $tagIds, int $limit, ?ISystemTag $priorTagId=null): array {
        if ($priorTagId === null) {
            $objectIds = $this->tagMapper->getObjectIdsForTags($tagIds, 'files');
            shuffle($objectIds);
            return array_slice($objectIds, 0, $limit);
        }
        $objectIdsPrior = $this->tagMapper->getObjectIdsForTags([$priorTagId->getId()], 'files', $limit, 0);
        if (count($objectIdsPrior) >= $limit) {
            return $objectIdsPrior;
        }
        $objectIds = $this->tagMapper->getObjectIdsForTags($tagIds, 'files');
        shuffle($objectIds);
        return array_merge($objectIdsPrior, array_slice($objectIds, 0, $limit - count($objectIdsPrior)));
    }

    /**
     * Delete a tag by name if it exists
     * @param string $tagName
     * @return void
     */
    public function removeTag(string $tagName): void
    {
        try{
            $tag = $this->getTag($tagName, false);
        } catch (TagNotFoundException) {
            return;
        }
        $this->tagService->deleteTags([$tag->getId()]);
    }
}
