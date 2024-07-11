<?php

namespace unittests;

use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Db\DbFileMapper;
use OCA\GDataVaas\Service\ScanService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCP\IAppConfig;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class ScanServiceTest extends TestCase {
	private LoggerInterface $logger;

	public function setUp(): void {
		parent::setUp();
		$this->logger = new TestLogger();
	}

	public function testRun_unscannedTagDisabled_unscannedTagShouldNotBeCreated(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')->with(Application::APP_ID, 'disableUnscannedTag')->willReturn(true);

		$tagManager = $this->createMock(ISystemTagManager::class);
		$getTagMatcher = $this->exactly(5);
		$tagManager
			->expects($getTagMatcher)
			->method('getTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable) use ($getTagMatcher) {
				switch($getTagMatcher->numberOfInvocations()) {
					case 1:
						$this->assertEquals(TagService::MALICIOUS, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 2:
						$this->assertEquals(TagService::PUP, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 3:
						$this->assertEquals(TagService::CLEAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 4:
						$this->assertEquals(TagService::WONT_SCAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 5:
						$this->assertEquals(TagService::UNSCANNED, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					default: $this->fail("Unexpected number of calls to unassignTags");
				}

			});
		$createTagMatcher = $this->exactly(4);
		$tagManager
			->expects($createTagMatcher)
			->method('createTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable) use ($createTagMatcher) {
				switch($createTagMatcher->numberOfInvocations()) {
					case 1:
						$this->assertEquals(TagService::MALICIOUS, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 2:
						$this->assertEquals(TagService::PUP, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 3:
						$this->assertEquals(TagService::CLEAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 4:
						$this->assertEquals(TagService::WONT_SCAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					default: $this->fail("Unexpected number of calls to unassignTags");
				}
			});

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$dbFileMapper = $this->createMock(DbFileMapper::class);
		$tagService = new TagService($this->logger, $tagManager, $tagMapper, $dbFileMapper);
		$verdictService = $this->createMock(VerdictService::class);

		$scanService = new ScanService(
			$this->logger,
			$tagService,
			$verdictService,
			$appConfig
		);

		$scanService->run();
	}

	public function testRun_unscannedTagEnabled_unscannedTagShouldBeCreated(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')->with(Application::APP_ID, 'disableUnscannedTag')->willReturn(true);

		$tagManager = $this->createMock(ISystemTagManager::class);
		$getTagMatcher = $this->exactly(5);
		$tagManager
			->expects($getTagMatcher)
			->method('getTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable) use ($getTagMatcher) {
				switch($getTagMatcher->numberOfInvocations()) {
					case 1:
						$this->assertEquals(TagService::MALICIOUS, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 2:
						$this->assertEquals(TagService::PUP, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 3:
						$this->assertEquals(TagService::CLEAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 4:
						$this->assertEquals(TagService::WONT_SCAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					case 5:
						$this->assertEquals(TagService::UNSCANNED, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						throw new TagNotFoundException();
					default: $this->fail("Unexpected number of calls to unassignTags");
				}

			});
		$createTagMatcher = $this->exactly(4);
		$tagManager
			->expects($createTagMatcher)
			->method('createTag')
			->willReturnCallback(function ($tagName, $userVisible, $userAssignable) use ($createTagMatcher) {
				switch($createTagMatcher->numberOfInvocations()) {
					case 1:
						$this->assertEquals(TagService::MALICIOUS, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 2:
						$this->assertEquals(TagService::PUP, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 3:
						$this->assertEquals(TagService::CLEAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 4:
						$this->assertEquals(TagService::WONT_SCAN, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					case 5:
						$this->assertEquals(TagService::UNSCANNED, $tagName);
						$this->assertTrue($userVisible);
						$this->assertFalse($userAssignable);
						return $this->createMock(ISystemTag::class);
					default: $this->fail("Unexpected number of calls to unassignTags");
				}
			});

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$dbFileMapper = $this->createMock(DbFileMapper::class);
		$tagService = new TagService($this->logger, $tagManager, $tagMapper, $dbFileMapper);
		$verdictService = $this->createMock(VerdictService::class);

		$scanService = new ScanService(
			$this->logger,
			$tagService,
			$verdictService,
			$appConfig
		);

		$scanService->run();
	}
}
