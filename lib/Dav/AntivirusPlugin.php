<?php

namespace OCA\GDataVaas\Dav;

use OC_Template;
use OCP\HintException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\ICollection;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class AntivirusPlugin extends ServerPlugin {
	public function __construct(
		private IShareManager $shareManager,
		private IUserSession $userSession,
		private LoggerInterface $logger,
		private Server $server,
		private IConfig $config,
		private IRequest $request,
	) {
	}

	public function initialize(Server $server): void {
		$server->on('beforeCreateFile', [$this, 'beforeCreateFile']);
		$this->server = $server;
	}

	public function beforeCreateFile($path, &$data, ICollection $parentNode, &$modified): void {
		$this->server->httpResponse->setBody($this->generateBody());
		$this->server->httpResponse->setStatus(415);
		$this->server->sapi->sendResponse($this->server->httpResponse);
		exit;
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
