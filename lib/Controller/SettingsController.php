<?php

namespace OCA\GDataVaas\Controller;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\Exception;
use OCP\IAppConfig;
use OCP\IRequest;

class SettingsController extends Controller {
	private IAppConfig $config;
	private TagService $tagService;

	public function __construct($appName, IRequest $request, IAppConfig $config, TagService $tagService) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->tagService = $tagService;
	}

	public function setconfig($username, $password, $clientId, $clientSecret, $authMethod, $quarantineFolder, $allowlist, $blocklist, $scanQueueLength): JSONResponse {
		$this->config->setValueString($this->appName, 'username', $username);
		$this->config->setValueString($this->appName, 'password', $password);
		$this->config->setValueString($this->appName, 'clientId', $clientId);
		$this->config->setValueString($this->appName, 'clientSecret', $clientSecret);
		$this->config->setValueString($this->appName, 'authMethod', $authMethod);
		$this->config->setValueString($this->appName, 'quarantineFolder', $quarantineFolder);
		$this->config->setValueString($this->appName, 'allowlist', $allowlist);
		$this->config->setValueString($this->appName, 'blocklist', $blocklist);
		$this->config->setValueInt($this->appName, 'scanQueueLength', $scanQueueLength);
		return new JSONResponse(['status' => 'success']);
	}

	public function setadvancedconfig($tokenEndpoint, $vaasUrl): JSONResponse {
		$this->config->setValueString($this->appName, 'tokenEndpoint', $tokenEndpoint);
		$this->config->setValueString($this->appName, 'vaasUrl', $vaasUrl);
		return new JSONResponse(['status' => 'success']);
	}

	public function setAutoScan(bool $autoScanFiles): JSONResponse {
		$this->config->setValueBool($this->appName, 'autoScanFiles', $autoScanFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getAutoScan(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'autoScanFiles')]);
	}

	public function setScanOnlyNewFiles(bool $scanOnlyNewFiles): JSONResponse {
		$this->config->setValueBool($this->appName, 'scanOnlyNewFiles', $scanOnlyNewFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function getScanOnlyNewFiles(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'scanOnlyNewFiles')]);
	}

	public function setPrefixMalicious(bool $prefixMalicious): JSONResponse {
		$this->config->setValueBool($this->appName, 'prefixMalicious', $prefixMalicious);
		return new JSONResponse(['status' => 'success']);
	}

	public function getPrefixMalicious(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'prefixMalicious')]);
	}

	public function getAuthMethod(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueString($this->appName, 'authMethod')]);
	}

	public function setDisableUnscannedTag(bool $disableUnscannedTag): JSONResponse {
		$this->config->setValueBool($this->appName, 'disableUnscannedTag', $disableUnscannedTag);
		return new JSONResponse(['status' => 'success']);
	}

	public function getDisableUnscannedTag(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'disableUnscannedTag')]);
	}

	public function resetAllTags(): JSONResponse {
		$this->tagService->resetAllTags();
		return new JSONResponse(['status' => 'success']);
	}

    public function getCounters(): JSONResponse {
        try {
            $filesCount = $this->tagService->getScannedFilesCount();
        }
        catch (Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        return new JSONResponse([
            'status' => 'success',
            'all' => $filesCount['all'],
            'scanned' => $filesCount['scanned']
        ]);
    }
}
