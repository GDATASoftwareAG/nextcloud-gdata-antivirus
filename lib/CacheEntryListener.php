<?php

namespace OCA\GDataVaas;

use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\AbstractCacheEvent;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryUpdatedEvent;
use Psr\Log\LoggerInterface;

class CacheEntryListener implements IEventListener
{
    private LoggerInterface $logger;

    private TagService $tagService;

    private VerdictService $verdictService;

    public function __construct(LoggerInterface $logger, TagService $tagService, VerdictService $verdictService)
    {
        $this->logger = $logger;
        $this->tagService = $tagService;
        $this->verdictService = $verdictService;
    }

    public static function register(IRegistrationContext $context): void {
        $context->registerEventListener(CacheEntryInsertedEvent::class, CacheEntryListener::class);
        $context->registerEventListener(CacheEntryUpdatedEvent::class, CacheEntryListener::class);
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof AbstractCacheEvent) {
            return;
        }

        $storage = $event->getStorage();
        $path = $event->getPath();
        $fileId = $event->getFileId();

        if (self::shouldTag($path) && !$this->tagService->hasAnyVaasTag($fileId)) {
            $this->logger->debug("Handling " . get_class($event) . " for " . $path);

            $this->verdictService->tagLastScannedFile($storage->getLocalFile($path), $fileId);
        }
    }

    private static function shouldTag(string $path): bool {
        return str_starts_with($path, 'files/');
    }
}
