<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\Files\EntityTooLargeException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use VaasSdk\ClientCredentialsGrantAuthenticator;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;
use VaasSdk\Message\VaasVerdict;
use VaasSdk\ResourceOwnerPasswordGrantAuthenticator;
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

	/**
	 * Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
	 * @param int $fileId
	 * @return VaasVerdict
	 * @throws InvalidPathException
	 * @throws InvalidSha256Exception
	 * @throws NotFoundException
	 * @throws UploadFailedException
	 * @throws TimeoutException
	 * @throws NotPermittedException
	 * @throws FileDoesNotExistException if the VaaS SDK could not find the file
	 * @throws EntityTooLargeException if the file that should be scanned is too large
	 * @throws VaasAuthenticationException if the authentication with the VaaS service fails
	 */
	public function scanFileById(int $fileId): VaasVerdict {
		$node = $this->fileService->getNodeFromFileId($fileId);
		$filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
		if ($node->getSize() > self::MAX_FILE_SIZE) {
			$this->tagService->removeAllTagsFromFile($fileId);
			$this->tagService->setTag($fileId, TagService::WONT_SCAN);
			throw new EntityTooLargeException("File is too large");
		}

		$blocklist = $this->getBlocklist();
		$this->logger->info("Blocklist: " . implode(", ", $blocklist));
		foreach ($blocklist as $blocklistItem) {
			if (str_contains(strtolower($filePath), strtolower($blocklistItem))) {
				$this->logger->info("File " . $node->getName() . " (" . $fileId . ") is in the blocklist and will not be scanned.");
				throw new NotPermittedException("File is in the blocklist");
			}
		}

		$allowlist = $this->getAllowlist();
		$this->logger->info("Allowlist: " . implode(", ", $allowlist));
		foreach ($allowlist as $allowlistItem) {
			if (!str_contains(strtolower($filePath), strtolower($allowlistItem))) {
				$this->logger->info("File " . $node->getName() . " (" . $fileId . ") is not in the allowlist and will not be scanned.");
				throw new NotPermittedException("File is not in the allowlist");
			}
		}

		$verdict = $this->scan($filePath);

		$this->logger->info("VaaS scan result for " . $node->getName() . " (" . $fileId . "): Verdict: "
			. $verdict->Verdict->value . ", Detection: " . $verdict->Detection . ", SHA256: " . $verdict->Sha256 .
			", FileType: " . $verdict->FileType . ", MimeType: " . $verdict->MimeType . ", UUID: " . $verdict->Guid);

		$this->tagService->removeAllTagsFromFile($fileId);

		switch ($verdict->Verdict->value) {
			case TagService::CLEAN:
				$this->tagService->setTag($fileId, TagService::CLEAN);
				break;
			case TagService::MALICIOUS:
				$this->tagService->setTag($fileId, TagService::MALICIOUS);
				try {
					$this->fileService->setMaliciousPrefixIfActivated($fileId);
					$this->fileService->moveFileToQuarantineFolderIfDefined($fileId);
				} catch (Exception) {
				}
				break;
			case TagService::PUP:
				$this->tagService->setTag($fileId, TagService::PUP);
				break;
			default:
				$this->tagService->setTag($fileId, TagService::UNSCANNED);
				break;
		}

		return $verdict;
	}

	public function scan(string $filePath): VaasVerdict {
		if ($this->vaas == null) {
			$this->vaas = $this->createAndConnectVaas();
		}

		try {
			$verdict = $this->vaas->ForFile($filePath);

			return $verdict;
		} catch (Exception $e) {
			$this->logger->error("Vaas for file: " . $e->getMessage());
			$this->vaas = null;
			throw $e;
		}
	}

	/**
	 * Parses the allowlist from the app settings and returns it as an array.
	 * @return array
	 */
	private function getAllowlist(): array {
		$allowlist = $this->appConfig->getAppValue(Application::APP_ID, 'allowlist');
		$allowlist = preg_replace('/\s+/', '', $allowlist);
		if (empty($allowlist)) {
			return [];
		}
		return explode(",", $allowlist);
	}

	/**
	 * Parses the blocklist from the app settings and returns it as an array.
	 * @return array
	 */
	private function getBlocklist(): array {
		$blocklist = $this->appConfig->getAppValue(Application::APP_ID, 'blocklist');
		$blocklist = preg_replace('/\s+/', '', $blocklist);
		if (empty($blocklist)) {
			return [];
		}
		return explode(",", $blocklist);
	}

	/**
	 * @throws VaasAuthenticationException
	 */
	private function createAndConnectVaas(): Vaas {
		if ($this->authMethod === 'ResourceOwnerPassword') {
			$this->authenticator = new ResourceOwnerPasswordGrantAuthenticator(
				"nextcloud-customer",
				$this->username,
				$this->password,
				$this->tokenEndpoint
			);
		} elseif ($this->authMethod === 'ClientCredentials') {
			$this->authenticator = new ClientCredentialsGrantAuthenticator(
				$this->clientId,
				$this->clientSecret,
				$this->tokenEndpoint
			);
		}

		$options = new VaasOptions(false, false);
		$vaas = new Vaas($this->vaasUrl, $this->logger, $options);
		$vaas->Connect($this->authenticator->getToken());
		return $vaas;
	}
}
