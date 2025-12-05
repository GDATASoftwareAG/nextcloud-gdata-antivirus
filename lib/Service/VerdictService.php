<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Service;

use Amp\File\Driver\BlockingFilesystemDriver;
use Amp\File\FilesystemDriver;
use OC\User\NoUserException;
use OCA\GDataVaas\AppInfo\Application;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use VaasSdk\Authentication\ClientCredentialsGrantAuthenticator;
use VaasSdk\Authentication\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\Exceptions\VaasAuthenticationException;
use VaasSdk\Exceptions\VaasClientException;
use VaasSdk\Exceptions\VaasServerException;
use VaasSdk\Options\ForFileOptions;
use VaasSdk\Options\VaasOptions;
use VaasSdk\Vaas;
use VaasSdk\VaasVerdict;

class VerdictService {
	private string $username;
	private string $password;
	private string $clientId;
	private string $clientSecret;
	public string $authMethod;
	private string $tokenEndpoint;
	private string $vaasUrl;
	private IAppConfig $appConfig;
	private FileService $fileService;
	private TagService $tagService;
	private ?Vaas $vaas = null;
	private FilesystemDriver $filesystemDriver;
	private LoggerInterface $logger;

	public function __construct(
		LoggerInterface $logger,
		IAppConfig $appConfig,
		FileService $fileService,
		TagService $tagService,
	) {
		$this->logger = $logger;
		$this->appConfig = $appConfig;
		$this->fileService = $fileService;
		$this->tagService = $tagService;
		$this->filesystemDriver = new BlockingFilesystemDriver();

		$this->authMethod = $this->appConfig->getValueString(
			Application::APP_ID, 'authMethod', 'ClientCredentials'
		);
		$this->tokenEndpoint = $this->appConfig->getValueString(
			Application::APP_ID,
			'tokenEndpoint',
			'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token'
		);
		$this->vaasUrl = $this->appConfig->getValueString(
			Application::APP_ID, 'vaasUrl', 'https://gateway.staging.vaas.gdatasecurity.de'
		);
		$this->clientId = $this->appConfig->getValueString(Application::APP_ID, 'clientId');
		$this->clientSecret = $this->appConfig->getValueString(Application::APP_ID, 'clientSecret');
		$this->username = $this->appConfig->getValueString(Application::APP_ID, 'username');
		$this->password = $this->appConfig->getValueString(Application::APP_ID, 'password');
	}


	/** Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
	 * @param int $fileId
	 * @return VaasVerdict
	 * @throws EntityTooLargeException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws VaasAuthenticationException
	 * @throws VaasClientException
	 * @throws VaasServerException
	 * @throws NoUserException
	 */
	public function scanFileById(int $fileId): VaasVerdict {
		$node = $this->fileService->getNodeFromFileId($fileId);
		$filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
		if (self::isFileTooLargeToScan($filePath)) {
			$this->tagService->setTag($fileId, TagService::WONT_SCAN, silent: true);
			throw new EntityTooLargeException('File is too large');
		}

		if (!$this->isAllowedToScan($filePath)) {
			throw new NotPermittedException(
				"File is not allowed to be scanned by the 'Do not scan this' or 'Scan only this' settings"
			);
		}

		$verdict = $this->scan($filePath);

		$this->logger->info('VaaS scan result for ' . $node->getName() . ' (' . $fileId . '): Verdict: '
			. $verdict->verdict->value . ', Detection: ' . $verdict->detection . ', SHA256: ' . $verdict->sha256
			. ', FileType: ' . $verdict->fileType . ', MimeType: ' . $verdict->mimeType);

		$this->tagFile($fileId, $verdict->verdict->value);

		return $verdict;
	}

	/**
	 * Checks if a file is too large to be scanned.
	 * @param string $path
	 * @return bool
	 */
	public function isFileTooLargeToScan(string $path): bool {
		$size = filesize($path);
		return ($size === false)
			|| $size > ($this->appConfig->getValueInt(Application::APP_ID, 'maxScanSizeInMB', 256) * 1024 * 1024);
	}

	/**
	 * Checks if the file is in the doNotScanThis or not in the scanOnlyThis and throws
	 * an exception if it is not allowed to scan the file.
	 * @param string $filePath
	 * @return bool
	 */
	public function isAllowedToScan(string $filePath): bool {
		$doNotScanThis = $this->getDoNotScanThis();
		foreach ($doNotScanThis as $doNotScanThisItem) {
			if (str_contains(strtolower($filePath), strtolower($doNotScanThisItem))) {
				return false;
			}
		}
		$scanOnlyThis = $this->getScanOnlyThis();
		if (count($scanOnlyThis) === 0) {
			return true;
		}
		foreach ($scanOnlyThis as $scanOnlyThisItem) {
			if (str_contains(strtolower($filePath), strtolower($scanOnlyThisItem))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Parses the doNotScanThis from the app settings and returns it as an array.
	 * @return array
	 */
	private function getDoNotScanThis(): array {
		$doNotScanThis = $this->appConfig->getValueString(Application::APP_ID, 'doNotScanThis');
		$doNotScanThis = $this->removeWhitespacesAroundComma($doNotScanThis);
		if (empty($doNotScanThis)) {
			return [];
		}
		return explode(',', $doNotScanThis);
	}

	/**
	 * Removes whitespaces around commas in a string and trims it.
	 * @param string $s
	 * @return string
	 */
	public function removeWhitespacesAroundComma(string $s): string {
		return trim(preg_replace('/\s*,\s*/', ',', $s));
	}

	/**
	 * Parses the scanOnlyThis from the app settings and returns it as an array.
	 * @return array
	 */
	private function getScanOnlyThis(): array {
		$scanOnlyThis = $this->appConfig->getValueString(Application::APP_ID, 'scanOnlyThis');
		$scanOnlyThis = $this->removeWhitespacesAroundComma($scanOnlyThis);
		if (empty($scanOnlyThis)) {
			return [];
		}
		return explode(',', $scanOnlyThis);
	}

	/**
	 * Scans a file for malicious content with G DATA Verdict-as-a-Service and returns the verdict.
	 * @param string $filePath The local path to the file to scan.
	 * @return VaasVerdict
	 * @throws VaasAuthenticationException
	 * @throws VaasClientException
	 * @throws VaasServerException
	 */
	public function scan(string $filePath): VaasVerdict {
		if ($this->vaas == null) {
			$this->vaas = $this->createAndConnectVaas();
		}

		$options = new ForFileOptions(true, true, ForFileOptions::DEFAULT_TIMEOUT, $this->filesystemDriver);
		return $this->vaas->forFileAsync($filePath, $options)->await();
	}

	/**
	 * @return Vaas
	 * @throws VaasAuthenticationException
	 * @throws VaasClientException
	 */
	public function createAndConnectVaas(): Vaas {
		if (str_starts_with($this->vaasUrl, 'ws')) {
			if (str_starts_with($this->vaasUrl, 'ws://')) {
				$this->vaasUrl = 'http://' . substr($this->vaasUrl, 5);
			} elseif (str_starts_with($this->vaasUrl, 'wss://')) {
				$this->vaasUrl = 'https://' . substr($this->vaasUrl, 6);
			}
		}
		$options = new VaasOptions(
			useHashLookup: $this->appConfig->getValueBool(Application::APP_ID, 'hashlookup', true),
			useCache: $this->appConfig->getValueBool(Application::APP_ID, 'cache', true),
			vaasUrl: $this->vaasUrl,
			timeout: $this->appConfig->getValueInt(Application::APP_ID, 'timeout', 300)
		);
		return Vaas::builder()
			->withAuthenticator($this->getAuthenticator($this->authMethod, $this->tokenEndpoint))
			->withOptions($options)
			->build();
	}

	/**
	 * @param string $authMethod
	 * @param string $tokenEndpoint
	 * @return ClientCredentialsGrantAuthenticator|ResourceOwnerPasswordGrantAuthenticator
	 * @throws VaasAuthenticationException
	 */
	public function getAuthenticator(
		string $authMethod,
		string $tokenEndpoint,
	): ClientCredentialsGrantAuthenticator|ResourceOwnerPasswordGrantAuthenticator {
		if ($authMethod === 'ResourceOwnerPassword') {
			return new ResourceOwnerPasswordGrantAuthenticator(
				'nextcloud-customer',
				$this->username,
				$this->password,
				$tokenEndpoint
			);
		} elseif ($authMethod === 'ClientCredentials') {
			return new ClientCredentialsGrantAuthenticator(
				$this->clientId,
				$this->clientSecret,
				$tokenEndpoint
			);
		} else {
			throw new VaasAuthenticationException('Invalid auth method: ' . $authMethod);
		}
	}

	private function tagFile(int $fileId, string $tagName): void {
		switch ($tagName) {
			case TagService::MALICIOUS:
				$this->tagService->setTag($fileId, TagService::MALICIOUS, silent: false);
				break;
			case TagService::PUP:
				$this->tagService->setTag($fileId, TagService::PUP, silent: false);
				break;
			case TagService::UNSCANNED:
				$unscannedTagIsDisabled = $this->appConfig->getValueBool(
					Application::APP_ID, 'disableUnscannedTag'
				);
				if (!$unscannedTagIsDisabled) {
					$this->tagService->setTag($fileId, TagService::UNSCANNED, silent: true);
				}
				break;
			case TagService::CLEAN:
			case TagService::WONT_SCAN:
			default:
				$this->tagService->setTag($fileId, $tagName, silent: true);
				break;
		}
	}
}
