<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCP\Files\EntityTooLargeException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
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
    private IAppConfig $appConfig;
    private FileService $fileService;
    private TagService $tagService;
    private ?Vaas $vaas = null;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, IAppConfig $appConfig, FileService $fileService, TagService $tagService)
    {
        $this->logger = $logger;
        $this->appConfig = $appConfig;
        $this->fileService = $fileService;
        $this->tagService = $tagService;

        $this->authMethod = $this->appConfig->getValueString(self::APP_ID, 'authMethod', 'ResourceOwnerPassword');
        $this->tokenEndpoint = $this->appConfig->getValueString(self::APP_ID, 'tokenEndpoint', 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token');
        $this->vaasUrl = $this->appConfig->getValueString(self::APP_ID, 'vaasUrl', 'wss://gateway.staging.vaas.gdatasecurity.de');
        $this->clientId = $this->appConfig->getValueString(self::APP_ID, 'clientId');
        $this->clientSecret = $this->appConfig->getValueString(self::APP_ID, 'clientSecret');
        $this->username = $this->appConfig->getValueString(self::APP_ID, 'username');
        $this->password = $this->appConfig->getValueString(self::APP_ID, 'password');
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
    public function scanFileById(int $fileId): VaasVerdict
    {
        $node = $this->fileService->getNodeFromFileId($fileId);
        $filePath = $node->getStorage()->getLocalFile($node->getInternalPath());
        if ($node->getSize() > self::MAX_FILE_SIZE) {
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

        if ($this->vaas == null) {
            $this->vaas = $this->createAndConnectVaas();
        }

        try {
            $verdict = $this->vaas->ForFile($filePath);
        } catch (Exception $e) {
            $this->logger->error("Vaas for file: " . $e->getMessage());
            $this->vaas = null;
            throw $e;
        }

        $this->logger->info("VaaS scan result for " . $node->getName() . " (" . $fileId . "): Verdict: "
            . $verdict->Verdict->value . ", Detection: " . $verdict->Detection . ", SHA256: " . $verdict->Sha256 .
            ", FileType: " . $verdict->FileType . ", MimeType: " . $verdict->MimeType . ", UUID: " . $verdict->Guid);

        $this->tagService->removeTagFromFile(TagService::CLEAN, $fileId);
        $this->tagService->removeTagFromFile(TagService::MALICIOUS, $fileId);
        $this->tagService->removeTagFromFile(TagService::PUP, $fileId);
        $this->tagService->removeTagFromFile(TagService::UNSCANNED, $fileId);

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

    /**
     * Parses the allowlist from the app settings and returns it as an array.
     * @return array
     */
    private function getAllowlist(): array
    {
        $allowlist = $this->appConfig->getValueString(self::APP_ID, 'allowlist');
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
    private function getBlocklist(): array
    {
        $blocklist = $this->appConfig->getValueString(self::APP_ID, 'blocklist');
        $blocklist = preg_replace('/\s+/', '', $blocklist);
        if (empty($blocklist)) {
            return [];
        }
        return explode(",", $blocklist);
    }

    /**
     * @throws VaasAuthenticationException
     */
    private function createAndConnectVaas(): Vaas
    {
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

        $vaas = new Vaas($this->vaasUrl);
        $vaas->Connect($this->authenticator->getToken());
        return $vaas;
    }
}
