<?php

namespace OCA\GDataVaas\Controller;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
	private IConfig $config;
	private TagService $tagService;

	public function __construct($appName, IRequest $request, IConfig $config, TagService $tagService) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->tagService = $tagService;
	}

	public function setconfig($username, $password, $clientId, $clientSecret, $authMethod, $quarantineFolder, $allowlist, $blocklist, $scanQueueLength): JSONResponse {
		$this->config->setAppValue($this->appName, 'username', $username);
		$this->config->setAppValue($this->appName, 'password', $password);
		$this->config->setAppValue($this->appName, 'clientId', $clientId);
		$this->config->setAppValue($this->appName, 'clientSecret', $clientSecret);
		$this->config->setAppValue($this->appName, 'authMethod', $authMethod);
		$this->config->setAppValue($this->appName, 'quarantineFolder', $quarantineFolder);
		$this->config->setAppValue($this->appName, 'allowlist', $allowlist);
		$this->config->setAppValue($this->appName, 'blocklist', $blocklist);
		$this->config->setAppValue($this->appName, 'scanQueueLength', $scanQueueLength);
		return new JSONResponse(['status' => 'success']);
	}

	public function setadvancedconfig($tokenEndpoint, $vaasUrl): JSONResponse {
		$this->config->setAppValue($this->appName, 'tokenEndpoint', $tokenEndpoint);
		$this->config->setAppValue($this->appName, 'vaasUrl', $vaasUrl);
		return new JSONResponse(['status' => 'success']);
	}

	public function setAutoScan(bool $autoScanFiles): JSONResponse {
		$this->config->setAppValue($this->appName, 'autoScanFiles', $autoScanFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getAutoScan(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'autoScanFiles')]);
	}

	public function setScanOnlyNewFiles(bool $scanOnlyNewFiles): JSONResponse {
		$this->config->setAppValue($this->appName, 'scanOnlyNewFiles', $scanOnlyNewFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getScanOnlyNewFiles(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'scanOnlyNewFiles')]);
	}

	public function setPrefixMalicious(bool $prefixMalicious): JSONResponse {
		$this->config->setAppValue($this->appName, 'prefixMalicious', $prefixMalicious);
		return new JSONResponse(['status' => 'success']);
	}

	public function getPrefixMalicious(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'prefixMalicious')]);
	}

	public function getAuthMethod(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'authMethod')]);
	}

	public function setDisableUnscannedTag(bool $disableUnscannedTag): JSONResponse {
		$this->config->setAppValue($this->appName, 'disableUnscannedTag', $disableUnscannedTag);
		return new JSONResponse(['status' => 'success']);
	}

	public function getDisableUnscannedTag(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'disableUnscannedTag')]);
	}

	public function resetAllTags(): JSONResponse {
		$this->tagService->resetAllTags();
		return new JSONResponse(['status' => 'success']);
	}
}
