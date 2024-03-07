<?php

namespace OCA\GDataVaas\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
	private IConfig $config;

	public function __construct($appName, IRequest $request, IConfig $config) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	public function setconfig($username, $password, $clientId, $clientSecret, $authMethod, $quarantineFolder): JSONResponse
	{
		$this->config->setAppValue($this->appName, 'username', $username);
		if (!empty($password)) {
			$this->config->setAppValue($this->appName, 'password', $password);
		}
		$this->config->setAppValue($this->appName, 'clientId', $clientId);
		if (!empty($clientSecret)) {
			$this->config->setAppValue($this->appName, 'clientSecret', $clientSecret);
		}
		$this->config->setAppValue($this->appName, 'authMethod', $authMethod);
		$this->config->setAppValue($this->appName, 'quarantineFolder', $quarantineFolder);
		return new JSONResponse(['status' => 'success']);
	}

	public function setadvancedconfig($tokenEndpoint, $vaasUrl): JSONResponse
	{
		$this->config->setAppValue($this->appName, 'tokenEndpoint', $tokenEndpoint);
		$this->config->setAppValue($this->appName, 'vaasUrl', $vaasUrl);
		return new JSONResponse(['status' => 'success']);
	}

	public function setAutoScan(bool $autoScanFiles): JSONResponse
	{
		$this->config->setAppValue($this->appName, 'autoScanFiles', $autoScanFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getAutoScan(): JSONResponse
	{
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'autoScanFiles')]);
	}

	public function setScanOnlyNewFiles(bool $scanOnlyNewFiles): JSONResponse
	{
		$this->config->setAppValue($this->appName, 'scanOnlyNewFiles', $scanOnlyNewFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getScanOnlyNewFiles(): JSONResponse
	{
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'scanOnlyNewFiles')]);
	}

	public function setPrefixMalicious(bool $prefixMalicious): JSONResponse
	{
		$this->config->setAppValue($this->appName, 'prefixMalicious', $prefixMalicious);
		return new JSONResponse(['status' => 'success']);
	}

	public function getPrefixMalicious(): JSONResponse
	{
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'prefixMalicious')]);
	}

	public function getAuthMethod(): JSONResponse
	{
		return new JSONResponse(['status' => $this->config->getAppValue($this->appName, 'authMethod')]);
	}
}
