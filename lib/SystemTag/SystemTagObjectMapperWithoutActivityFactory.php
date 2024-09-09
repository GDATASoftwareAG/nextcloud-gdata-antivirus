<?php

namespace OCA\GDataVaas\SystemTag;

use OC\SystemTag\SystemTagObjectMapper;
use OCP\EventDispatcher\Event;
use OCP\IDBConnection;
use OCP\SystemTag\ISystemTagManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemTagObjectMapperWithoutActivityFactory
{
	/**
	 * Variant of Nextcloud's ISystemTagObjectMapper, but does not generate events or activities for tag changes.
	 */
	public static function createSilentSystemTagObjectMapper(IDBConnection $connection, ISystemTagManager $tagManager): SystemTagObjectMapper
	{
		return new SystemTagObjectMapper($connection, $tagManager, self::createFakeEventDispatcher());
	}

	private static function createFakeEventDispatcher(): EventDispatcherInterface
	{
		return new class implements EventDispatcherInterface {

            public function addListener(string $eventName, callable $listener, int $priority = 0)
            {
                // DUMMY
            }

            public function addSubscriber(EventSubscriberInterface $subscriber)
            {
                // DUMMY
            }

            public function removeListener(string $eventName, callable $listener)
            {
                // DUMMY
            }

            public function removeSubscriber(EventSubscriberInterface $subscriber)
            {
                // DUMMY
            }

            public function getListeners(?string $eventName = null)
            {
                // DUMMY
            }

            public function dispatch(object $event, ?string $eventName = null): object
            {
                // DUMMY
                return new Event();
            }

            public function getListenerPriority(string $eventName, callable $listener)
            {
                // DUMMY
            }

            public function hasListeners(?string $eventName = null)
            {
                // DUMMY
                return false;
            }
        };
	}
}
