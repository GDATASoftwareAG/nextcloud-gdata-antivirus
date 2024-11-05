<?php

namespace OCA\GDataVaas\Listener;

use Exception;
use OCA\GDataVaas\Service\VerdictService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;

class CacheEntryInsertedListener implements IEventListener
{
    private readonly LoggerInterface $logger;
    private VerdictService $verdictService;
    
    public function __construct(LoggerInterface $logger, VerdictService $verdictService)
    {
        $this->logger = $logger;
        $this->verdictService = $verdictService;
    }
    
    public function handle(Event $event): void
    {
        if (!($event instanceof CacheEntryInsertedEvent)) {
            return;
        }

        $this->logger->info('Cache entry inserted: ' . $event->getPath());
        
        $fileId = $event->getFileId();
        try {
            $verdict = $this->verdictService->scanFileById($fileId);
            // Delete file if malicious
        } catch (EntityTooLargeException|NotFoundException|NotPermittedException|FileDoesNotExistException|InvalidSha256Exception|TimeoutException|UploadFailedException|VaasAuthenticationException $e) {
            $this->logger->error($e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('An unexpected error occurred while scanning file ' . $fileId . ' with GData VaaS. Please check the logs for more information and contact your administrator: ' . $e->getMessage());
        }
    }
}