<?php

namespace OCA\GDataVaas\Db;

use OCA\GDataVaas\Service\VerdictService;
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
     * @return array of file ids
     * @throws Exception if the database platform is not supported
     */
    public function getFileIdsWithoutTags(array $excludedTagIds, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        $qb->automaticTablePrefix(true);
        
        $qb->select('f.fileid')
            ->from($this->getTableName(), 'f')
            ->leftJoin('f', 'systemtag_object_mapping', 'o', $qb->expr()->eq('f.fileid', $qb->createFunction($this->getPlatformSpecificCast())))
            ->leftJoin('f', 'mimetypes', 'm', $qb->expr()->eq('f.mimetype', 'm.id'))
            ->where($qb->expr()->notIn('o.systemtagid', $qb->createNamedParameter($excludedTagIds, IQueryBuilder::PARAM_INT_ARRAY)))
            ->orWhere($qb->expr()->isNull('o.systemtagid'))
            ->andWhere($qb->expr()->notLike('m.mimetype', $qb->createNamedParameter('%unix-directory%')))
            ->andWhere($qb->expr()->lte('f.size', $qb->createNamedParameter(VerdictService::MAX_FILE_SIZE)))
            ->andWhere($qb->expr()->like('f.path', $qb->createNamedParameter('files/%')))
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
        $platform = $this->db->getDatabasePlatform()->getName();
        if ($platform === 'mysql') {
            $cast = 'CAST(' . 'o.objectid' . ' AS UNSIGNED)';
        } elseif ($platform === 'sqlite') {
            $cast = 'CAST(' . 'o.objectid' . ' AS INTEGER)';
        } elseif ($platform === 'postgresql') {
            $cast = 'CAST(' . 'o.objectid' . ' AS BIGINT)';
        } else {
            throw new Exception('Unsupported database platform: ' . $platform);
        }
        return $cast;
    }
}
