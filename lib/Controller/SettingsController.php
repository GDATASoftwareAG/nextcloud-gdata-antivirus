<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Controller;

use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\Exception;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Mail\IMailer;
use VaasSdk\Exceptions\VaasAuthenticationException;
use VaasSdk\Options\VaasOptions;
use VaasSdk\Vaas;
use VaasSdk\Verdict;

class SettingsController extends Controller {
	private IAppConfig $config;
	private TagService $tagService;
	private IMailer $mailer;
	private VerdictService $verdictService;

	public function __construct(
		$appName,
		IRequest $request,
		IAppConfig $config,
		TagService $tagService,
		IMailer $mailer,
		VerdictService $verdictService,
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->tagService = $tagService;
		$this->mailer = $mailer;
		$this->verdictService = $verdictService;
	}

	public function setconfig(
		$username,
		$password,
		$clientId,
		$clientSecret,
		$authMethod,
		$quarantineFolder,
		$scanOnlyThis,
		$doNotScanThis,
		$notifyMails,
		$maxScanSize,
	): JSONResponse {
		if (!empty($notifyMails)) {
			$mails = explode(',', preg_replace('/\s+/', '', $notifyMails));
			foreach ($mails as $mail) {
				if ($this->mailer->validateMailAddress($mail) === false) {
					return new JSONResponse(['status' => 'error', 'message' => 'Invalid email address: ' . $mail]);
				}
			}
		}
		if ((int)$maxScanSize < 1) {
			return new JSONResponse(['status' => 'error', 'message' => 'Invalid max scan size: ' . $maxScanSize]);
		}
		$this->config->setValueString($this->appName, 'username', $username);
		$this->config->setValueString($this->appName, 'password', $password);
		$this->config->setValueString($this->appName, 'clientId', $clientId);
		$this->config->setValueString($this->appName, 'clientSecret', $clientSecret);
		$this->config->setValueString($this->appName, 'authMethod', $authMethod);
		$this->config->setValueString($this->appName, 'quarantineFolder', $quarantineFolder);
		$this->config->setValueString($this->appName, 'scanOnlyThis', $scanOnlyThis);
		$this->config->setValueString($this->appName, 'doNotScanThis', $doNotScanThis);
		$this->config->setValueString($this->appName, 'notifyMails', $notifyMails);
		$this->config->setValueInt($this->appName, 'maxScanSizeInMB', (int)$maxScanSize);
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
		} catch (Exception $e) {
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

	public function getSendMailOnVirusUpload(): JSONResponse {
		return new JSONResponse(
			['status' => $this->config->getValueBool($this->appName, 'sendMailOnVirusUpload')]
		);
	}

	public function setSendMailOnVirusUpload(bool $sendMailOnVirusUpload): JSONResponse {
		$this->config->setValueBool($this->appName, 'sendMailOnVirusUpload', $sendMailOnVirusUpload);
		return new JSONResponse(['status' => 'success']);
	}

	public function getSendMailSummaryOfMaliciousFiles(): JSONResponse {
		return new JSONResponse(['status' => $this->config->getValueBool($this->appName, 'notifyAdminEnabled')]);
	}

	public function setSendMailSummaryOfMaliciousFiles(bool $sendMailSummaryOfMaliciousFiles): JSONResponse {
		$this->config->setValueBool($this->appName, 'notifyAdminEnabled', $sendMailSummaryOfMaliciousFiles);
		return new JSONResponse(['status' => 'success']);
	}

	public function testSettings(string $tokenEndpoint, string $vaasUrl): JSONResponse {
		try {
			$authenticator = $this->verdictService->getAuthenticator($this->verdictService->authMethod, $tokenEndpoint);
			$options = new VaasOptions(true, true, $vaasUrl);
			$vaas = Vaas::builder()
				->withAuthenticator($authenticator)
				->withOptions($options)
				->build();
			$verdict = $vaas->forUrlAsync('https://www.gdata.de')->await();
			if ($verdict->verdict === Verdict::CLEAN) {
				return new JSONResponse(['status' => 'success']);
			}
			return new JSONResponse(['status' => 'error', 'message' => 'Test URL verdict: ' . $verdict->verdict->value]);
		} catch (VaasAuthenticationException $e) {
			return new JSONResponse([
				'status' => 'error',
				'message' => 'Authentication failed. Please also check your login details above and save them before
				taking the test. ' . $e->getMessage()
			]);
		} catch (\Exception $e) {
			return new JSONResponse(['status' => 'error', 'message' => $e->getMessage()]);
		}
	}
}
