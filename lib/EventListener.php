<?php

namespace OCA\GDataVaas;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\AbstractCacheEvent;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryUpdatedEvent;
use Psr\Log\LoggerInterface;

class EventListener implements IEventListener
{
    private LoggerInterface $logger;

    private TagService $tagService;

    public function __construct(LoggerInterface $logger, TagService $tagService)
    {
        $this->logger = $logger;
        $this->tagService = $tagService;
    }

    public static function register(IRegistrationContext $context): void {
        $context->registerEventListener(CacheEntryInsertedEvent::class, EventListener::class);
        $context->registerEventListener(CacheEntryUpdatedEvent::class, EventListener::class);
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof AbstractCacheEvent) {
            return;
        }

        $path = $event->getPath();
        $fileId = $event->getFileId();

        if (self::shouldTag($path) && !$this->tagService->hasAnyVaasTag($fileId)) {
            $this->tagService->setTag($event->getFileId(), TagService::UNSCANNED);
        }
    }

    private static function shouldTag(string $path): bool {
        return str_starts_with($path, 'files/');
    }
}
