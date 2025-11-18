<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeLoader;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class DbFileMapper extends QBMapper {
	private string $stringType;
	private LoggerInterface $logger;
	private IMimeTypeLoader $mimeTypeLoader;
	private IConfig $config;

	/**
	 * @throws Exception
	 */
	public function __construct(IDBConnection $db, LoggerInterface $logger, IMimeTypeLoader $mimeTypeLoader, IConfig $config) {
		parent::__construct($db, 'filecache');
		$this->stringType = $this->getStringTypeDeclarationSQL();
		$this->logger = $logger;
		$this->mimeTypeLoader = $mimeTypeLoader;
		$this->config = $config;
	}

	/**
	 * Get the DB type for a string
	 * @return string the database string type
	 * @throws Exception if the database platform is not supported
	 */
	private function getStringTypeDeclarationSQL(): string {
		$platform = $this->db->getDatabaseProvider();
		if ($platform === 'mysql') {
			$stringType = 'CHAR(64)';
		} elseif ($platform === 'sqlite' || $platform === 'postgres') {
			$stringType = 'VARCHAR(64)';
		} else {
			throw new Exception('Unsupported database platform: ' . $platform);
		}
		return $stringType;
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
		$dirMimeTypeId = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);
		$instanceId = $this->config->getSystemValue('instanceid', '');

		$query = $this->db->getQueryBuilder();
		$query->select('fc.fileid')
			->from('filecache', 'fc')
			->leftJoin('fc', 'storages', 's', $query->expr()->eq('fc.storage', 's.numeric_id'))
			->leftJoin(
				'fc', 'systemtag_object_mapping', 'o', $query->expr()->eq(
					'o.objectid', $query->createFunction(sprintf('CAST(fc.fileid AS %s)', $this->stringType))))
			->where($query->expr()->notIn(
				'o.systemtagid', $query->createNamedParameter($excludedTagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->orWhere($query->expr()->isNull('o.systemtagid'))
			->andWhere($query->expr()->neq('fc.mimetype', $query->createNamedParameter($dirMimeTypeId)))
			->andWhere($query->expr()->orX(
				$query->expr()->like('fc.path', $query->createNamedParameter('files/%')),
				$query->expr()->notLike('s.id', $query->createNamedParameter('home::%'))
			))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("appdata_$instanceId/%")))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("\_\_groupfolders/versions/%")))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("\_\_groupfolders/trash/%")))
			->orderBy('fc.fileid', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit);

		$fileIds = [];
		$result = $query->executeQuery();
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
		$dirMimeTypeId = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);
		$instanceId = $this->config->getSystemValue('instanceid', '');

		$query = $this->db->getQueryBuilder();
		$query->select('fc.fileid')
			->from('filecache', 'fc')
			->leftJoin('fc', 'storages', 's', $query->expr()->eq('fc.storage', 's.numeric_id'))
			->leftJoin(
				'fc', 'systemtag_object_mapping', 'o', $query->expr()->eq(
				'o.objectid', $query->createFunction(sprintf('CAST(fc.fileid AS %s)', $this->stringType))))
			->where($query->expr()->in(
				'o.systemtagid', $query->createNamedParameter($includedTagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->orWhere($query->expr()->isNull('o.systemtagid'))
			->andWhere($query->expr()->neq('fc.mimetype', $query->createNamedParameter($dirMimeTypeId)))
			->andWhere($query->expr()->orX(
				$query->expr()->like('fc.path', $query->createNamedParameter('files/%')),
				$query->expr()->notLike('s.id', $query->createNamedParameter('home::%'))
			))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("appdata_$instanceId/%")))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("\_\_groupfolders/versions/%")))
			->andWhere($query->expr()->notLike('fc.path', $query->createNamedParameter("\_\_groupfolders/trash/%")))
			->orderBy('fc.fileid', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit);

		$fileIds = [];
		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$fileIds[] = $row['fileid'];
		}
		return $fileIds;
	}

	/**
	 * Get the number of files in the nextcloud instance
	 * @return int
	 * @throws Exception
	 */
	public function getFilesCount(): int {
		$fileCount = 0;

		$dirMimeTypeId = $this->mimeTypeLoader->getId(FileInfo::MIMETYPE_FOLDER);
		$instanceId = $this->config->getSystemValue('instanceid', '');

		$fileQuery = $this->db->getQueryBuilder();
		$fileQuery->select($fileQuery->func()->count())
			->from('filecache', 'fc')
			->leftJoin('fc', 'storages', 's', $fileQuery->expr()->eq('fc.storage', 's.numeric_id'))
			->where($fileQuery->expr()->eq('fc.storage', $fileQuery->createParameter('storageId')))
			->andWhere($fileQuery->expr()->neq('fc.mimetype', $fileQuery->createNamedParameter($dirMimeTypeId)))
			->andWhere($fileQuery->expr()->orX(
				$fileQuery->expr()->like('fc.path', $fileQuery->createNamedParameter('files/%')),
				$fileQuery->expr()->notLike('s.id', $fileQuery->createNamedParameter('home::%'))
			))
			->andWhere($fileQuery->expr()->notLike('fc.path', $fileQuery->createNamedParameter("appdata_$instanceId/%")))
			->andWhere($fileQuery->expr()->notLike('fc.path', $fileQuery->createNamedParameter("\_\_groupfolders/versions/%")))
			->andWhere($fileQuery->expr()->notLike('fc.path', $fileQuery->createNamedParameter("\_\_groupfolders/trash/%")));

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
