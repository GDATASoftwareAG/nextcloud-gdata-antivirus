<?php

namespace OCA\GDataVaas\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class DbFileMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'filecache');
	}

	/**
	 * Get file ids that do not have any of the given tags
	 * @param array $excludedTagIds
	 * @param int $limit
	 * @param int $offset default 0
	 * @return array of file ids
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithoutTags(array $excludedTagIds, int $limit, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->automaticTablePrefix(true);

		$qb->select('f.fileid')
			->from($this->getTableName(), 'f')
			->leftJoin('f', 'systemtag_object_mapping', 'o', $qb->expr()->eq('f.fileid', $qb->createFunction($this->getPlatformSpecificCast())))
			->leftJoin('f', 'mimetypes', 'm', $qb->expr()->eq('f.mimetype', 'm.id'))
			->where($qb->expr()->notIn('o.systemtagid', $qb->createNamedParameter($excludedTagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->orWhere($qb->expr()->isNull('o.systemtagid'))
			->andWhere($qb->expr()->notLike('m.mimetype', $qb->createNamedParameter('%unix-directory%')))
			->andWhere($qb->expr()->orX(
				$qb->expr()->like('f.path', $qb->createNamedParameter('files/%')),
				$qb->expr()->like('f.path', $qb->createNamedParameter('__groupfolders/%'))
			))
			->orderBy('f.fileid', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit);

		$fileIds = [];
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$fileIds[] = $row['fileid'];
		}
		return $fileIds;
	}

	/**
	 * Get file ids that have at least one of the given tags
	 * @param array $includedTagIds
	 * @param int $limit
	 * @param int $offset
	 * @return array of file ids
	 * @throws Exception if the database platform is not supported
	 */
	public function getFileIdsWithTags(array $includedTagIds, int $limit, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->automaticTablePrefix(true);

		$qb->select('f.fileid')
			->from($this->getTableName(), 'f')
			->leftJoin('f', 'systemtag_object_mapping', 'o', $qb->expr()->eq('f.fileid', $qb->createFunction($this->getPlatformSpecificCast())))
			->leftJoin('f', 'mimetypes', 'm', $qb->expr()->eq('f.mimetype', 'm.id'))
			->where($qb->expr()->in('o.systemtagid', $qb->createNamedParameter($includedTagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->notLike('m.mimetype', $qb->createNamedParameter('%unix-directory%')))
			->andWhere($qb->expr()->orX(
				$qb->expr()->like('f.path', $qb->createNamedParameter('files/%')),
				$qb->expr()->like('f.path', $qb->createNamedParameter('__groupfolders/%'))
			))
			->orderBy('f.fileid', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit);

		$fileIds = [];
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$fileIds[] = $row['fileid'];
		}
		return $fileIds;
	}

	/**
	 * Create a platform-specific cast function
	 * @return string the database platform-specific cast function
	 * @throws Exception if the database platform is not supported
	 */
	private function getPlatformSpecificCast(): string {
		$platform = $this->db->getDatabaseProvider();
		if ($platform === 'mysql') {
			$cast = 'CAST(' . 'o.objectid' . ' AS UNSIGNED)';
		} elseif ($platform === 'sqlite') {
			$cast = 'CAST(' . 'o.objectid' . ' AS INTEGER)';
		} elseif ($platform === 'postgres') {
			$cast = 'CAST(' . 'o.objectid' . ' AS BIGINT)';
		} else {
			throw new Exception('Unsupported database platform: ' . $platform);
		}
		return $cast;
	}

	/**
	 * Get the number of files in the nextcloud instance
	 * @return int
	 * @throws Exception
	 */
	public function getFilesCount(): int {
		$fileCount = 0;
		
		$fileQuery = $this->db->getQueryBuilder();
		$fileQuery->select($fileQuery->func()->count())
			->from('filecache', 'f')
			->leftJoin('f', 'mimetypes', 'm', $fileQuery->expr()->eq('f.mimetype', 'm.id'))
			->where($fileQuery->expr()->eq('storage', $fileQuery->createParameter('storageId')))
			->andWhere($fileQuery->expr()->notLike('m.mimetype', $fileQuery->createNamedParameter('%unix-directory%')))
			->andWhere($fileQuery->expr()->orX(
				$fileQuery->expr()->like('f.path', $fileQuery->createNamedParameter('files/%')),
				$fileQuery->expr()->like('f.path', $fileQuery->createNamedParameter('__groupfolders/%'))
			));

		$storageQuery = $this->db->getQueryBuilder();
		$storageQuery->selectAlias('numeric_id', 'id')
			->from('storages');
		$storageResult = $storageQuery->executeQuery();
		while ($storageRow = $storageResult->fetch()) {
			$fileQuery->setParameter('storageId', $storageRow['id']);
			$fileResult = $fileQuery->executeQuery();
			$fileCount += (int)$fileResult->fetchOne();
			$fileResult->closeCursor();
		}
		$storageResult->closeCursor();
		
		return $fileCount;
	}
}
