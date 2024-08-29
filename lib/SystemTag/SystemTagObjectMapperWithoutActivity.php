<?php

namespace OCA\GDataVaas\SystemTag;

use OC\SystemTag\SystemTagObjectMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;

/**
 * Variant of Nextcloud's ISystemTagObjectMapper, but does not generate events or activities for tag changes.
 */
class SystemTagObjectMapperWithoutActivity implements ISystemTagObjectMapper {

	private static function createFakeEventDispatcher(): IEventDispatcher {
		return new class implements IEventDispatcher {
			public function addListener(string $eventName, callable $listener, int $priority = 0): void {
				// DUMMY
			}

			public function removeListener(string $eventName, callable $listener): void {
				// DUMMY
			}

			public function addServiceListener(string $eventName, string $className, int $priority = 0): void {
				// DUMMY
			}

			public function hasListeners(string $eventName): bool {
				// DUMMY
				return false;
			}

			public function dispatch(string $eventName, Event $event): void {
				// DUMMY
			}

			public function dispatchTyped(Event $event): void {
				// DUMMY
			}
		};
	}

	private ISystemTagObjectMapper $wrappedMapper;

	public function __construct(
		protected IDBConnection     $connection,
		protected ISystemTagManager $tagManager,
	) {
		$this->wrappedMapper = new SystemTagObjectMapper($connection, $tagManager, self::createFakeEventDispatcher());
	}

	public function getTagIdsForObjects($objIds, string $objectType): array {
		return $this->wrappedMapper->getTagIdsForObjects($objIds, $objectType);
	}

	public function getObjectIdsForTags($tagIds, string $objectType, int $limit = 0, string $offset = ''): array {
		return $this->wrappedMapper->getObjectIdsForTags($tagIds, $objectType, $limit, $offset);
	}

	public function assignTags(string $objId, string $objectType, $tagIds) {
		$this->wrappedMapper->assignTags($objId, $objectType, $tagIds);
	}

	public function unassignTags(string $objId, string $objectType, $tagIds) {
		$this->wrappedMapper->unassignTags($objId, $objectType, $tagIds);
	}

	public function haveTag($objIds, string $objectType, string $tagId, bool $all = true): bool {
		return $this->wrappedMapper->haveTag($objIds, $objectType, $tagId, $all);
	}
}
