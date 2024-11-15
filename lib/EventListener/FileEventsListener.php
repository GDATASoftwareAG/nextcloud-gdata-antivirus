<?php

namespace OCA\GDataVaas\EventListener;

use OC_Template;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeWrittenEvent;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\HintException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Server;

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
	) {
	}

	public static function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeNodeCreatedEvent::class, self::class);
		$context->registerEventListener(BeforeNodeWrittenEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeCreatedEvent) {
			$this->server->httpResponse->setBody($this->generateBody());
			$this->server->httpResponse->setStatus(415);
			$this->server->sapi->sendResponse($this->server->httpResponse);
			exit;
		}
	}

	public function generateBody(): mixed {
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

		$ex = new HintException('virus found', 'virus found', 415);
		$content = new OC_Template('gdatavaas', $templateName, $renderAs);
		$content->assign('title', 'virus found');
		$content->assign('message', 'virus found');
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
}
