<?php

namespace OCA\GDataVaas\Tests;

use OCA\GDataVaas\Db\DbFileMapper;
use OCA\GDataVaas\Service\TagService;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class TagServiceTest extends TestCase {
	public static $OBJECT_ID_1 = 1;

	private function getTagManager(): ISystemTagManager {
		$tagManager = $this->createMock(ISystemTagManager::class);
		$tagManager->method('getTag')->willReturnCallback(function ($name) {
			$tag = $this->createMock(ISystemTag::class);
			$tag->method('getId')->willReturn($name);
			return $tag;
		});
		return $tagManager;
	}

	public function testSetWontScan_ShouldDeleteCleanAndMaliciousAndAssignWontScan() {
		$tagManager = $this->getTagManager();

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => [TagService::CLEAN, TagService::MALICIOUS]]);
		$unassignTagsMatcher = $this->exactly(2);
		$tagMapper->expects($unassignTagsMatcher)->method('unassignTags')->willReturnCallback(function ($objectId, $objectType, $tagIds) use ($unassignTagsMatcher) {
			switch($unassignTagsMatcher->numberOfInvocations()) {
				case 1:
					$this->assertEquals(strval(self::$OBJECT_ID_1), $objectId);
					$this->assertEquals('files', $objectType);
					$this->assertEquals([TagService::CLEAN], $tagIds);
					break;
				case 2:
					$this->assertEquals(strval(self::$OBJECT_ID_1), $objectId);
					$this->assertEquals('files', $objectType);
					$this->assertEquals([TagService::MALICIOUS], $tagIds);
					break;
				default: $this->fail("Unexpected number of calls to unassignTags");
			}
		});
		$tagMapper->expects($this->once())->method('assignTags')->with(strval(self::$OBJECT_ID_1), 'files', [TagService::WONT_SCAN]);
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, TagService::WONT_SCAN);
	}

	public function testSetWontScan_TagAlreadySet_ShouldNotDoAnything() {
		$tagManager = $this->getTagManager();

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => [TagService::WONT_SCAN]]);
		$tagMapper->expects($this->never())->method('unassignTags');
		$tagMapper->expects($this->never())->method('assignTags');
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, TagService::WONT_SCAN);
	}

	public function testSetWontScan_UnscannedTagDoesNotExist_SouldTagFileWithWontScan_WithNoException() {
		$tagManager = $this->getTagManager();
		
		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => [TagService::PUP]]);
		$tagMapper->expects($this->once())->method('unassignTags');
		$tagMapper->expects($this->once())->method('assignTags');
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, TagService::WONT_SCAN);
	}

	public function testSetWontScan_NoneVaasTagIsSet_SouldTagFileWithWontScan_AndNotDeleteTheNoneVaasTag() {
		$tagManager = $this->getTagManager();
		
		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => ["NoneVaasTag"]]);
		$tagMapper->expects($this->never())->method('unassignTags');
		$tagMapper->expects($this->once())->method('assignTags');
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, TagService::WONT_SCAN);
	}
}
