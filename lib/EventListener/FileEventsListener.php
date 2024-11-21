<?php

namespace OCA\GDataVaas\EventListener;

use Exception;
use OC_Template;
use OCP\IAppConfig;
use OCA\GDataVaas\AppInfo\Application;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\GDataVaas\Service\FileService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCA\GDataVaas\Exceptions\VirusFoundException;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;
use OCA\GDataVaas\Service\MailService;

/** @template-implements IEventListener<BeforeNodeCopiedEvent|BeforeNodeDeletedEvent|BeforeNodeRenamedEvent|BeforeNodeTouchedEvent|BeforeNodeWrittenEvent|NodeCopiedEvent|NodeCreatedEvent|NodeDeletedEvent|NodeRenamedEvent|NodeTouchedEvent|NodeWrittenEvent> */
class FileEventsListener implements IEventListener {
	public function __construct(
		private IRootFolder $rootFolder,
		private IVersionManager $versionManager,
		private IMimeTypeLoader $mimeTypeLoader,
		private IUserSession $userSession,
		private LoggerInterface $logger,
		private IConfig $config,
		private Server $server,
		private IRequest $request,
		private VerdictService $verdictService,
		private FileService $fileService,
		private TagService $tagService,
		private IAppConfig $appConfig,
		private MailService $mailService
	) {
	}

	public static function register(IRegistrationContext $context): void {
		$context->registerEventListener(NodeWrittenEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeWrittenEvent) {
			$node = $event->getNode();
			try {
				$verdict = $this->verdictService->scanFileById($node->getId());
			} catch (\Exception $e) {
				$unscannedTagIsDisabled = $this->appConfig->getValueBool(Application::APP_ID, 'disableUnscannedTag');
				if (!$unscannedTagIsDisabled) {
					$this->tagService->setTag($node->getId(), TagService::UNSCANNED, silent: true);
				}
				$this->logger->error("Failed to scan uploaded file '{$node->getName()}' with ID '{$node->getId()}': {$e->getMessage()}");
				return;
			}

			if ($verdict->Verdict->value == TagService::MALICIOUS) {
				$this->sendErrorResponse(new VirusFoundException($verdict, $node->getName(), $node->getId()));
				$this->fileService->deleteFile($node->getId());
				if ($this->appConfig->getValueBool(Application::APP_ID, 'sendMailOnVirusUpload')) {
					$this->mailService->notifyMaliciousUpload($verdict, $node->getPath(), $this->userSession->getUser()->getUID(), $node->getSize());
				}
				exit;
			}
		}
	}

	public function generateBody(Exception $ex): mixed {
		if ($this->acceptHtml()) {
			$templateName = 'exception';
			$renderAs = 'guest';
			$templateName = '425';
		} else {
			$templateName = 'xml_exception';
			$renderAs = null;
			$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		}

		$debug = $this->config->getSystemValueBool('debug', false);

		$content = new OC_Template('gdatavaas', $templateName, $renderAs);
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
		$contentString = $content->fetchPage();
		return $contentString;
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

	private function sendErrorResponse(Exception $ex): void {
		$this->server->httpResponse->setBody($this->generateBody($ex));
		$this->server->httpResponse->setStatus(415);
		$this->server->sapi->sendResponse($this->server->httpResponse);
	}
}
