<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\EventListener;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Exceptions\VirusFoundException;
use OCA\GDataVaas\Service\FileService;
use OCA\GDataVaas\Service\MailService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\FileInfo;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Lock\LockedException;
use OCP\Template\ITemplateManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;

class FileEventsListener implements IEventListener {
	public function __construct(
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly IConfig $config,
		private readonly Server $server,
		private readonly IRequest $request,
		private readonly VerdictService $verdictService,
		private readonly FileService $fileService,
		private readonly TagService $tagService,
		private readonly IAppConfig $appConfig,
		private readonly MailService $mailService,
		private readonly ITemplateManager $templateManager,
	) {
	}

	public static function register(IRegistrationContext $context): void {
		$context->registerEventListener(NodeWrittenEvent::class, self::class);
	}

	/**
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws Exception
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof NodeWrittenEvent) {
			$node = $event->getNode();
			if ($node->getType() !== FileInfo::TYPE_FILE) {
				return;
			}
			try {
				$verdict = $this->verdictService->scanFileById($node->getId());
			} catch (Exception $e) {
				$unscannedTagIsDisabled = $this->appConfig->getValueBool(
					Application::APP_ID, 'disableUnscannedTag'
				);
				if (!$unscannedTagIsDisabled) {
					$this->tagService->setTag($node->getId(), TagService::UNSCANNED, silent: true);
				}
				$this->logger->error(
					"Failed to scan uploaded file '{$node->getName()}' with
					ID '{$node->getId()}': {$e->getMessage()}"
				);
				return;
			}

			if ($verdict->verdict->value == TagService::MALICIOUS) {
				try {
					$this->sendErrorResponse(new VirusFoundException($verdict, $node->getName(), $node->getId()));
					$this->fileService->deleteFile($node->getId());
					if ($this->appConfig->getValueBool(Application::APP_ID, 'sendMailOnVirusUpload')) {
						$this->mailService->notifyMaliciousUpload(
							$verdict, $node->getPath(), $this->userSession->getUser()->getUID(), $node->getSize()
						);
					}
				} catch (Exception $e) {
					try {
						$this->fileService->setMaliciousPrefixIfActivated($node->getId());
						$this->fileService->moveFileToQuarantineFolderIfDefined($node->getId());
						$this->logger->error(
							"Failed to block upload of malicious file '{$node->getName()}'.
							File was quarantined instead. Error: {$e->getMessage()}"
						);
					} catch (Exception $e) {
						$this->logger->error(
							"Failed to set malicious prefix or move file to quarantine for '{$node->getName()}'. Error: {$e->getMessage()}"
						);
					}
				}
				exit;
			}
		}
	}

	private function sendErrorResponse(Exception $ex): void {
		$this->server->httpResponse->setBody($this->generateBody($ex));
		$this->server->httpResponse->setStatus(415);
		$this->server->sapi->sendResponse($this->server->httpResponse);
	}

	public function generateBody(Exception $ex): string {
		if ($this->acceptHtml()) {
			$renderAs = TemplateResponse::RENDER_AS_GUEST;
			$templateName = 'exception';
		} else {
			$templateName = 'xml_exception';
			$renderAs = TemplateResponse::RENDER_AS_BLANK;
			$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		}

		$debug = $this->config->getSystemValueBool('debug');

		$content = $this->templateManager->getTemplate('gdatavaas', $templateName, $renderAs);
		$content->assign('title', 'Error');
		$content->assign('message', $ex->getMessage());
		$content->assign('remoteAddr', $this->request->getRemoteAddress());
		$content->assign('requestID', $this->request->getId());
		$content->assign('debugMode', $debug);
		$content->assign('errorClass', get_class($ex));
		$content->assign('errorMsg', $ex->getMessage());
		$content->assign('errorCode', $ex->getCode());
		$content->assign('file', $ex->getFile());
		$content->assign('line', $ex->getLine());
		$content->assign('exception', $ex);
		return $content->fetchPage();
	}

	private function acceptHtml(): bool {
		foreach (explode(',', $this->request->getHeader('Accept')) as $part) {
			$subparts = explode(';', $part);
			if (str_ends_with($subparts[0], '/html')) {
				return true;
			}
		}
		return false;
	}
}
