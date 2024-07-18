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

	public function testIsAllowedToScan_multipleScanOnlyThisEntries_ShouldAllowWhenMatching(): void {
        $scanOnlyThis = ["eicar.txt", "eicar2.txt"];
        $doNotScanThis = [];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
			$this->logger,
			$appConfig,
			$this->createMock(FileService::class),
			$this->createMock(TagService::class));

		$result = $verdictService->isAllowedToScan('/mypath/eicar.txt');
		$this->assertTrue($result);
	}
    
    public function testIsAllowedToScan_multipleScanOnlyThisEntries_ShouldNotAllowWhenNotMatching(): void {
        $scanOnlyThis = ["eicar.txt", "eicar2.txt"];
        $doNotScanThis = [];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar3.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedToScan_multipleDoNotScanThisEntries_ShouldNotAllowWhenMatching(): void {
        $scanOnlyThis = [];
        $doNotScanThis = ["eicar.txt", "eicar2.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedToScan_multipleDoNotScanThisEntries_ShouldAllowWhenNotMatching(): void {
        $scanOnlyThis = [];
        $doNotScanThis = ["eicar.txt", "eicar2.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar3.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedToScan_scanOnlyThisAndDoNotScanThisEntries_ShouldAllowWhenMatchingScanOnlyThis(): void {
        $scanOnlyThis = ["eicar.txt", "eicar2.txt"];
        $doNotScanThis = ["eicar2.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedToScan_scanOnlyThisAndDoNotScanThisEntries_ShouldNotAllowWhenMatchingDoNotScanThis(): void {
        $scanOnlyThis = ["eicar.txt", "eicar2.txt"];
        $doNotScanThis = ["eicar2.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar2.txt');
        $this->assertFalse($result);
    }
    
    public function testIsAllowedToScan_scanOnlyThisAndDoNotScanThisEntries_ShouldWorkWithEmptyLists(): void {
        $scanOnlyThis = [];
        $doNotScanThis = [];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/eicar2.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedToScan_withAllowedScanPath_ShouldScanWithSpacesInLists(): void {
        $scanOnlyThis = ["Scan folder ", "eicar2.txt"];
        $doNotScanThis = ["eicar3.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/Scan folder/eicar1.txt');
        $this->assertTrue($result);
    }
    
    public function testIsAllowedToScan_withForbiddenScanPath_ShouldNotScanWithSpacesInLists(): void {
        $scanOnlyThis = ["eicar2.txt"];
        $doNotScanThis = [" Scan folder", "eicar3.txt"];
        $appConfig = $this->getAppConfigMock($scanOnlyThis, $doNotScanThis);

        $verdictService = new VerdictService(
            $this->logger,
            $appConfig,
            $this->createMock(FileService::class),
            $this->createMock(TagService::class));
        
        $result = $verdictService->isAllowedToScan('/mypath/Scan folder/eicar4.txt');
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

    private function getAppConfigMock(array $scanOnlyThis, array $doNotScanThis): IAppConfig {
        $appConfig = $this->createMock(IAppConfig::class);
        $appConfig
            ->method('getValueString')
            ->willReturnCallback(function ($appId, $key, $default) use ($doNotScanThis, $scanOnlyThis) {
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
                        case 'scanOnlyThis':
                            return implode(', ', $scanOnlyThis);
                        case 'doNotScanThis':
                            return implode(', ', $doNotScanThis);
                    }
                }
                return $default;
            });
        return $appConfig;
    }
}
