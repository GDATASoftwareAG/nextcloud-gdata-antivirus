<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\SystemTag;

use OC\SystemTag\SystemTagObjectMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\SystemTag\ISystemTagManager;

class SystemTagObjectMapperWithoutActivityFactory {
	/**
	 * Variant of Nextcloud's ISystemTagObjectMapper, but does not generate events or activities for tag changes.
	 */
	public static function createSilentSystemTagObjectMapper(
		IDBConnection $connection,
		ISystemTagManager $tagManager,
	): SystemTagObjectMapper {
		return new SystemTagObjectMapper($connection, $tagManager, self::createFakeEventDispatcher());
	}

	private static function createFakeEventDispatcher(): IEventDispatcher {
		return new class implements IEventDispatcher {
			#[\Override]
			public function addListener(string $eventName, callable $listener, int $priority = 0): void {
				// DUMMY
			}

			#[\Override]
			public function removeListener(string $eventName, callable $listener): void {
				// DUMMY
			}

			#[\Override]
			public function addServiceListener(string $eventName, string $className, int $priority = 0): void {
				// DUMMY
			}

			#[\Override]
			public function hasListeners(string $eventName): bool {
				// DUMMY
				return false;
			}

			#[\Override]
			public function dispatch(string $eventName, Event $event): void {
				// DUMMY
			}

			#[\Override]
			public function dispatchTyped(Event $event): void {
				// DUMMY
			}
		};
	}
}
