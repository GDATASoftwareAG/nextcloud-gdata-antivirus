<?php

namespace OCA\GDataVaas\EventListener;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\HintException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<BeforeNodeCopiedEvent|BeforeNodeDeletedEvent|BeforeNodeRenamedEvent|BeforeNodeTouchedEvent|BeforeNodeWrittenEvent|NodeCopiedEvent|NodeCreatedEvent|NodeDeletedEvent|NodeRenamedEvent|NodeTouchedEvent|NodeWrittenEvent> */
class FileEventsListener implements IEventListener {
	public function __construct(
		private IRootFolder $rootFolder,
		private IVersionManager $versionManager,
		private IMimeTypeLoader $mimeTypeLoader,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	public static function register($context): void {
		$context->registerEventListener(BeforeNodeWrittenEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeWrittenEvent) {
			throw new HintException('BeforeNodeWrittenEvent');
			$this->logger->debug(BeforeNodeWrittenEvent::class . ':' . $event->getNode()->getPath());
			return;
		}
	}
}
