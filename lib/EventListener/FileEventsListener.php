<?php

namespace OCA\GDataVaas\EventListener;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;

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

	public static function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeNodeCreatedEvent::class, self::class);
		$context->registerEventListener(BeforeNodeWrittenEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeCreatedEvent) {
			$node = $event->getNode();
			$event->stopPropagation();
			$exception = new Exception('virus detected', 415);
			$trace = $exception->getTraceAsString();
			throw $exception;
			$this->logger->debug(BeforeNodeCreatedEvent::class . ':' . $event->getNode()->getPath());
			return;
		}
	}
}
