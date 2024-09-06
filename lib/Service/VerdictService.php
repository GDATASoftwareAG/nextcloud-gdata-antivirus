<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use VaasSdk\Authentication\ClientCredentialsGrantAuthenticator;
use VaasSdk\Authentication\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;
use VaasSdk\Message\VaasVerdict;
use VaasSdk\Vaas;
use VaasSdk\VaasOptions;

class VerdictService {
	public const MAX_FILE_SIZE = 268435456;

	private string $username;
	private string $password;
	private string $clientId;
	private string $clientSecret;
	private string $authMethod;
	private string $tokenEndpoint;
	private string $vaasUrl;
	private ResourceOwnerPasswordGrantAuthenticator|ClientCredentialsGrantAuthenticator $authenticator;
	private IConfig $appConfig;
	private FileService $fileService;
	private TagService $tagService;
	private ?Vaas $vaas = null;
	private LoggerInterface $logger;

	private string $lastLocalPath = "";
	private ?VaasVerdict $lastVaasVerdict = null;

	public function __construct(LoggerInterface $logger, IConfig $appConfig, FileService $fileService, TagService $tagService) {
		$this->logger = $logger;
		$this->appConfig = $appConfig;
		$this->fileService = $fileService;
		$this->tagService = $tagService;

		$this->authMethod = $this->appConfig->getAppValue(Application::APP_ID, 'authMethod', 'ClientCredentials');
		$this->tokenEndpoint = $this->appConfig->getAppValue(Application::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token');
		$this->vaasUrl = $this->appConfig->getAppValue(Application::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de');
		$this->clientId = $this->appConfig->getAppValue(Application::APP_ID, 'clientId');
		$this->clientSecret = $this->appConfig->getAppValue(Application::APP_ID, 'clientSecret');
		$this->username = $this->appConfig->getAppValue(Application::APP_ID, 'username');
		$this->password = $this->appConfig->getAppValue(Application::APP_ID, 'password');
	}


	/** Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
	 * @param int $fileId
	 * @return VaasVerdict
	 * @throws EntityTooLargeException
	 * @throws FileDoesNotExistException
	 * @throws InvalidSha256Exception
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws TimeoutException
	 * @throws UploadFailedException
	 * @throws VaasAuthenticationException
	 */
	public function scanFileById(int $fileId): VaasVerdict {
		$node = $this->fileService->getNodeFromFileId($fileId);
		$filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
		if (self::isFileTooLargeToScan($filePath)) {
			$this->tagService->setTag($fileId, TagService::WONT_SCAN);
			throw new EntityTooLargeException("File is too large");
		}

		if (!$this->isAllowedToScan($filePath)) {
			throw new NotPermittedException("File is not allowed to be scanned by the 'Do not scan this' or 'Scan only this' settings");
		}

		$verdict = $this->scan($filePath);

		$this->logger->info("VaaS scan result for " . $node->getName() . " (" . $fileId . "): Verdict: "
			. $verdict->Verdict->value . ", Detection: " . $verdict->Detection . ", SHA256: " . $verdict->Sha256 .
			", FileType: " . $verdict->FileType . ", MimeType: " . $verdict->MimeType . ", UUID: " . $verdict->Guid);

		$this->tagFile($fileId, $verdict->Verdict->value);

		return $verdict;
	}

	private function tagFile(int $fileId, string $tagName): void {
		switch ($tagName) {
			case TagService::MALICIOUS:
				$this->tagService->setTag($fileId, TagService::MALICIOUS);
				try {
					$this->fileService->setMaliciousPrefixIfActivated($fileId);
					$this->fileService->moveFileToQuarantineFolderIfDefined($fileId);
				} catch (Exception) {
				}
				break;
			case TagService::UNSCANNED:
				$unscannedTagIsDisabled = $this->appConfig->getAppValue(Application::APP_ID, 'disableUnscannedTag');
				if (!$unscannedTagIsDisabled)
					$this->tagService->setTag($fileId, $tagName);
				break;
			case TagService::CLEAN:
			case TagService::PUP:
			case TagService::WONT_SCAN:
			default:
				$this->tagService->setTag($fileId, $tagName);
				break;
		}
	}

	/**
	 * Checks if a file is too large to be scanned.
	 * @param string $path
	 * @return bool
	 */
	public static function isFileTooLargeToScan(string $path): bool {
		$size = filesize($path);
		return ($size === false) || $size > self::MAX_FILE_SIZE;
	}

	/**
	 * Scans a file for malicious content with G DATA Verdict-as-a-Service and returns the verdict.
	 * @param string $filePath The local path to the file to scan.
	 * @return VaasVerdict
	 * @throws FileDoesNotExistException
	 * @throws InvalidSha256Exception
	 * @throws TimeoutException
	 * @throws UploadFailedException
	 * @throws VaasAuthenticationException
	 */
	public function scan(string $filePath): VaasVerdict {
		$this->lastLocalPath = $filePath;
		$this->lastVaasVerdict = null;

		if ($this->vaas == null) {
			$this->vaas = $this->createAndConnectVaas();
		}

		try {
			$verdict = $this->vaas->ForFile($filePath);

			$this->lastVaasVerdict = $verdict;

			return $verdict;
		} catch (Exception $e) {
			$this->vaas = null;
			throw $e;
		}
	}

	/**
	 * Call this from a StorageWrapper, when a local file was renamed. This allows the scanner to track the name
	 * of the file that was scanned last.
	 * @param string $localSource The local source path.
	 * @param string $localTarget The local destination path.
	 */
	public function onRename(string $localSource, string $localTarget): void {
		if ($localSource === $this->lastLocalPath) {
			$this->lastLocalPath = $localTarget;
		}
	}


	/**
	 * Tag the file that was scanned last with its verdict. Call this from an EventListener on CacheEntryInsertedEvent or
	 * CacheEntryUpdatedEvent.
	 * @param string $localPath The local path.
	 * @param int $fileId The corresponding file id to tag.
	 */
	public function tagLastScannedFile(string $localPath, int $fileId): void {
		if (self::isFileTooLargeToScan($localPath)) {
			$this->tagFile($fileId, TagService::WONT_SCAN);
			return;
		}
		if (!$this->isAllowedToScan($localPath)) {
			$this->tagFile($fileId, TagService::UNSCANNED);
			return;
		}
		if ($localPath === $this->lastLocalPath) {
			if ($this->lastVaasVerdict !== null) {
				$this->tagFile($fileId, $this->lastVaasVerdict->Verdict->value);
			} else {
				$this->tagFile($fileId, TagService::UNSCANNED);
			}
		}
	}

	/**
	 * Parses the scanOnlyThis from the app settings and returns it as an array.
	 * @return array
	 */
	private function getScanOnlyThis(): array {
		$scanOnlyThis = $this->appConfig->getAppValue(Application::APP_ID, 'scanOnlyThis');
		$scanOnlyThis = $this->removeWhitespacesAroundComma($scanOnlyThis);
		if (empty($scanOnlyThis)) {
			return [];
		}
		return explode(",", $scanOnlyThis);
	}
	
	/**
	 * Parses the doNotScanThis from the app settings and returns it as an array.
	 * @return array
	 */
	private function getDoNotScanThis(): array {
		$doNotScanThis = $this->appConfig->getAppValue(Application::APP_ID, 'doNotScanThis');
		$doNotScanThis = $this->removeWhitespacesAroundComma($doNotScanThis);
		if (empty($doNotScanThis)) {
			return [];
		}
		return explode(",", $doNotScanThis);
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
	 * @param string $authMethod
	 * @return ClientCredentialsGrantAuthenticator|ResourceOwnerPasswordGrantAuthenticator
	 * @throws VaasAuthenticationException
	 */
	public function getAuthenticator(string $authMethod): ClientCredentialsGrantAuthenticator|ResourceOwnerPasswordGrantAuthenticator {
		if ($authMethod === 'ResourceOwnerPassword') {
			return new ResourceOwnerPasswordGrantAuthenticator(
				"nextcloud-customer",
				$this->username,
				$this->password,
				$this->tokenEndpoint
			);
		} elseif ($authMethod === 'ClientCredentials') {
			return new ClientCredentialsGrantAuthenticator(
				$this->clientId,
				$this->clientSecret,
				$this->tokenEndpoint
			);
		} else {
			throw new VaasAuthenticationException("Invalid auth method: " . $authMethod);
		}
	}

	/**
	 * @throws VaasAuthenticationException
	 */
	public function createAndConnectVaas(): Vaas {
		$this->authenticator = $this->getAuthenticator($this->authMethod);
		$options = new VaasOptions(false, false);
		$vaas = new Vaas($this->vaasUrl, $this->logger, $options);
		$vaas->Connect($this->authenticator->getToken());
		return $vaas;
	}

	/**
	 * Checks if the file is in the doNotScanThis or not in the scanOnlyThis and throws an exception if it is not allowed to scan the file.
	 * @param string $filePath
	 * @return bool
	 */
	public function isAllowedToScan(string $filePath): bool {
		$doNotScanThis = $this->getDoNotScanThis();
		$this->logger->debug("doNotScanThis: " . implode(", ", $doNotScanThis));
		foreach ($doNotScanThis as $doNotScanThisItem) {
			if (str_contains(strtolower($filePath), strtolower($doNotScanThisItem))) {
				return false;
			}
		}
		$scanOnlyThis = $this->getScanOnlyThis();
		if (count($scanOnlyThis) === 0) {
			return true;
		}
		$this->logger->debug("scanOnlyThis: " . implode(", ", $scanOnlyThis));
		foreach ($scanOnlyThis as $scanOnlyThisItem) {
			if (str_contains(strtolower($filePath), strtolower($scanOnlyThisItem))) {
				return true;
			}
		}
		return false;
	}
}
