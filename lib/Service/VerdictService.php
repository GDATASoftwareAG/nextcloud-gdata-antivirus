<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCP\Files\EntityTooLargeException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use VaasSdk\ClientCredentialsGrantAuthenticator;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;
use VaasSdk\Message\Verdict;
use VaasSdk\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\Vaas;

class VerdictService
{
	public const MAX_FILE_SIZE = 2147483646;
    private const APP_ID = "gdatavaas";
    
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
    private Vaas $vaas;

    public function __construct(IConfig $appConfig, FileService $fileService, TagService $tagService)
    {
        $this->appConfig = $appConfig;
        $this->fileService = $fileService;
        $this->tagService = $tagService;
        
        $this->authMethod = $this->appConfig->getAppValue(self::APP_ID, 'authMethod', 'ResourceOwnerPassword');
        $this->tokenEndpoint = $this->appConfig->getAppValue(self::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token');
        $this->vaasUrl = $this->appConfig->getAppValue(self::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de');
        $this->clientId = $this->appConfig->getAppValue(self::APP_ID, 'clientId');
        $this->clientSecret = $this->appConfig->getAppValue(self::APP_ID, 'clientSecret');
        $this->username = $this->appConfig->getAppValue(self::APP_ID, 'username');
        $this->password = $this->appConfig->getAppValue(self::APP_ID, 'password');
        
        if ($this->authMethod === 'ResourceOwnerPassword') {
            $this->authenticator = new ResourceOwnerPasswordGrantAuthenticator(
                "nextcloud-customer",
                $this->username,
                $this->password,
                $this->tokenEndpoint);
        } elseif ($this->authMethod === 'ClientCredentials') {
            $this->authenticator = new ClientCredentialsGrantAuthenticator(
                $this->clientId,
                $this->clientSecret,
                $this->tokenEndpoint
            );
        }
        
        $this->vaas = new Vaas($this->vaasUrl);
    }

    /**
     * Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
     * @param int $fileId
     * @return Verdict
     * @throws InvalidPathException
     * @throws InvalidSha256Exception
     * @throws NotFoundException
     * @throws UploadFailedException
     * @throws TimeoutException
     * @throws VaasAuthenticationException
     * @throws NotPermittedException
     * @throws FileDoesNotExistException if the VaaS SDK could not find the file
     * @throws EntityTooLargeException if the file that should be scanned is too large
    */
    public function scanFileById(int $fileId): Verdict
    {
        $node = $this->fileService->getNodeFromFileId($fileId);
		$filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
		if ($node->getSize() > self::MAX_FILE_SIZE) {
			throw new EntityTooLargeException("File is too large");
		}
        $this->vaas->Connect($this->authenticator->getToken());
		$verdict = $this->vaas->ForFile($filePath)->Verdict;

        $this->tagService->removeTagFromFile(TagService::CLEAN, $fileId);
        $this->tagService->removeTagFromFile(TagService::MALICIOUS, $fileId);
        $this->tagService->removeTagFromFile(TagService::UNSCANNED, $fileId);

        switch ($verdict->value) {
            case TagService::CLEAN:
                $this->tagService->setTag($fileId, TagService::CLEAN);
                break;
            case TagService::MALICIOUS:
                $this->tagService->setTag($fileId, TagService::MALICIOUS);
                try {
                    $this->fileService->setMaliciousPrefixIfActivated($fileId);
                    $this->fileService->moveFileToQuarantineFolderIfDefined($fileId);
                } catch (Exception) {}
                break;
            default:
                $this->tagService->setTag($fileId, TagService::UNSCANNED);
                break;
        }

		return $verdict;
	}
}
