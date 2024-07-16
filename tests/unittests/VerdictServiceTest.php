<?php

namespace unittests;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\FileService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class VerdictServiceTest extends TestCase {
	private LoggerInterface $logger;

	public function setUp(): void {
		parent::setUp();
		$this->logger = new TestLogger();
	}

	public function testIsAllowedByAllowAndBlocklist_multipleAllowListEntries_ShouldAllowWhenMatching(): void {
        $allowlist = ["eicar.txt", "eicar2.txt"];
        $blocklist = [];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
			$this->logger,
			$appConfig,
			$this->createMock(FileService::class),
			$this->createMock(TagService::class));

		$result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar.txt');
		$this->assertTrue($result);
	}
    
    public function testIsAllowedByAllowAndBlocklist_multipleAllowListEntries_ShouldNotAllowWhenNotMatching(): void {
        $allowlist = ["eicar.txt", "eicar2.txt"];
        $blocklist = [];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar3.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_multipleBlockListEntries_ShouldNotAllowWhenMatching(): void {
        $allowlist = [];
        $blocklist = ["eicar.txt", "eicar2.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_multipleBlockListEntries_ShouldAllowWhenNotMatching(): void {
        $allowlist = [];
        $blocklist = ["eicar.txt", "eicar2.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar3.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_allowAndBlockListEntries_ShouldAllowWhenMatchingAllowList(): void {
        $allowlist = ["eicar.txt", "eicar2.txt"];
        $blocklist = ["eicar2.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_allowAndBlockListEntries_ShouldNotAllowWhenMatchingBlockList(): void {
        $allowlist = ["eicar.txt", "eicar2.txt"];
        $blocklist = ["eicar2.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar2.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_allowAndBlockListEntries_ShouldWorkWithEmptyLists(): void {
        $allowlist = [];
        $blocklist = [];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar2.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_withAllowedScanPath_ShouldScanWithSpacesInLists(): void {
        $allowlist = ["Scan folder ", "eicar2.txt"];
        $blocklist = ["eicar3.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/Scan folder/eicar1.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedByAllowAndBlocklist_withForbiddenScanPath_ShouldNotScanWithSpacesInLists(): void {
        $allowlist = ["eicar2.txt"];
        $blocklist = [" Scan folder", "eicar3.txt"];
        $appConfig = $this->getAppConfigMock($allowlist, $blocklist);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/Scan folder/eicar4.txt');
        $this->assertFalse($result);
    }
    
    public function testRemoveWhitespacesAroundComma_ShouldRemoveWhitespaces(): void {
        $verdictService = new VerdictService(
            $this->logger,
            $this->createMock(IAppConfig::class),
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->removeWhitespacesAroundComma('a, b, c');
        $result2 = $verdictService->removeWhitespacesAroundComma('a,b,c');
        $result3 = $verdictService->removeWhitespacesAroundComma(' a, b , c,d');
        
        $this->assertEquals('a,b,c', $result);
        $this->assertEquals('a,b,c', $result2);
        $this->assertEquals('a,b,c,d', $result3);
    }

    private function getAppConfigMock(array $allowlist, array $blocklist): IAppConfig {
        $appConfig = $this->createMock(IAppConfig::class);
        $appConfig
            ->method('getValueString')
            ->willReturnCallback(function ($appId, $key, $default) use ($blocklist, $allowlist) {
                if ($appId === Application::APP_ID) {
                    switch ($key) {
                        case 'authMethod':
                            return 'ClientCredentials';
                        case 'tokenEndpoint':
                            return 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token';
                        case 'vaasUrl':
                            return 'wss://gateway.staging.vaas.gdatasecurity.de';
                        case 'clientSecret':
                        case 'username':
                        case 'password':
                        case 'clientId':
                            return 'something';
                        case 'allowlist':
                            return implode(', ', $allowlist);
                        case 'blocklist':
                            return implode(', ', $blocklist);
                    }
                }
                return $default;
            });
        return $appConfig;
    }
}
