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

class VerdictSericeTest extends TestCase {
	private LoggerInterface $logger;

	public function setUp(): void {
		parent::setUp();
		$this->logger = new TestLogger();
	}

	public function testIsAllowedByAllowAndBlocklist_multipleAllowListEntries_ShouldAllowWhenMatching(): void {
		$appConfig = $this->createMock(IAppConfig::class);

		$appConfig
			->method('getValueString')
			->willReturnCallback(function ($appId, $key, $default) {
				if ($appId === Application::APP_ID) {
					switch ($key) {
						case 'authMethod':
							return 'ClientCredentials';
						case 'tokenEndpoint':
							return 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token';
						case 'vaasUrl':
							return 'wss://gateway.staging.vaas.gdatasecurity.de';
						case 'clientId':
							return 'something';
						case 'clientSecret':
							return 'something';
						case 'username':
							return 'something';
						case 'password':
							return 'something';
						case 'allowlist':
							return 'eicar.txt,eicar.com';
						case 'blocklist':
							return '';
					}
				}
				return $default;
			});

		$verdictService = new VerdictService(
			$this->logger,
			$appConfig,
			$this->createMock(FileService::class),
			$this->createMock(TagService::class));

		$result = $verdictService->isAllowedByAllowAndBlocklist('/mypath/eicar.txt');
		$this->assertTrue($result);
	}
}
