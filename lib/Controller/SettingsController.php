<?php

namespace OCA\GDataVaas\Controller;

use OCA\GDataVaas\Service\TagService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Mail\IMailer;

class SettingsController extends Controller {
	private IConfig $config;
	private TagService $tagService;
    private IMailer $mailer;


	public function __construct($appName, IRequest $request, IConfig $config, TagService $tagService, IMailer $mailer) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->tagService = $tagService;
        $this->mailer = $mailer;
	}

	public function setconfig($username, $password, $clientId, $clientSecret, $authMethod, $quarantineFolder, $allowlist, $blocklist, $scanQueueLength, $notifyMails): JSONResponse {
        if (!empty($notifyMails)) {
            $mails = explode(',', preg_replace('/\s+/', '', $notifyMails));
            foreach ($mails as $mail) {
                if ($this->mailer->validateMailAddress($mail) === false) {
                    return new JSONResponse(['status' => 'error', 'message' => 'Invalid email address: ' . $mail]);
                }
            }
        }
        if (!empty($scanQueueLength)) {
            if (!is_numeric($scanQueueLength) || $scanQueueLength < 1) {
                return new JSONResponse(['status' => 'error', 'message' => 'Invalid scan queue length']);
            }
        }
		$this->config->setAppValue($this->appName, 'username', $username);
		$this->config->setAppValue($this->appName, 'password', $password);
		$this->config->setAppValue($this->appName, 'clientId', $clientId);
		$this->config->setAppValue($this->appName, 'clientSecret', $clientSecret);
		$this->config->setAppValue($this->appName, 'authMethod', $authMethod);
		$this->config->setAppValue($this->appName, 'quarantineFolder', $quarantineFolder);
		$this->config->setAppValue($this->appName, 'allowlist', $allowlist);
		$this->config->setAppValue($this->appName, 'blocklist', $blocklist);
		$this->config->setAppValue($this->appName, 'scanQueueLength', $scanQueueLength);
		$this->config->setAppValue($this->appName, 'notifyMails', $notifyMails);
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
    
    public function getSendMailOnVirusUpload(): JSONResponse{
        return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'sendMailOnVirusUpload')]);
    }

    public function setSendMailOnVirusUpload(bool $sendMailOnVirusUpload): JSONResponse {
        $this->config->setValueBool($this->appName, 'sendMailOnVirusUpload', $sendMailOnVirusUpload);
        return new JSONResponse(['status' => 'success']);
    }

    public function getSendMailSummaryOfMaliciousFiles(): JSONResponse{
        return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'notifyAdminEnabled')]);
    }

    public function setSendMailSummaryOfMaliciousFiles(bool $sendMailSummaryOfMaliciousFiles): JSONResponse {
        $this->config->setValueBool($this->appName, 'notifyAdminEnabled', $sendMailSummaryOfMaliciousFiles);
        return new JSONResponse(['status' => 'success']);
    }
}
