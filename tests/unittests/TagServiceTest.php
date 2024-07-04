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
	public static $TAG_ID_1 = "tag1";
	public static $TAG_ID_2 = "tag2";
	public static $TAG_ID_3 = "tag3";

	public function testSetTag_ShouldDeleteTag2AndTag3AndAssignTag1() {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn(self::$TAG_ID_1);

		$tagManager = $this->createMock(ISystemTagManager::class);
		$tagManager->method('getTag')->willReturn($tag);

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => [self::$TAG_ID_2, self::$TAG_ID_3]]);
		$unassignTagsMatcher = $this->exactly(2);
		$tagMapper->expects($unassignTagsMatcher)->method('unassignTags')->willReturnCallback(function ($objectId, $objectType, $tagIds) use ($unassignTagsMatcher) {
			switch($unassignTagsMatcher->numberOfInvocations()) {
				case 1:
					$this->assertEquals($objectId, strval(self::$OBJECT_ID_1));
					$this->assertEquals($objectType, 'files');
					$this->assertEquals($tagIds, [self::$TAG_ID_2]);
					break;
				case 2:
					$this->assertEquals($objectId, strval(self::$OBJECT_ID_1));
					$this->assertEquals($objectType, 'files');
					$this->assertEquals($tagIds, [self::$TAG_ID_3]);
					break;
				default: $this->fail("Unexpected number of calls to unassignTags");
			}
		});
		$tagMapper->expects($this->once())->method('assignTags')->with(strval(self::$OBJECT_ID_1), 'files', [self::$TAG_ID_1]);
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, self::$TAG_ID_1);
	}

	public function testSetTag1_TagAlreadySet_ShouldNotDoAnything() {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn(self::$TAG_ID_1);

		$tagManager = $this->createMock(ISystemTagManager::class);
		$tagManager->method('getTag')->willReturn($tag);

		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$tagMapper->method('getTagIdsForObjects')->willReturn([self::$OBJECT_ID_1 => [self::$TAG_ID_1]]);
		$tagMapper->expects($this->never())->method('unassignTags');
		$tagMapper->expects($this->never())->method('assignTags');
		
		$dbFileMapper = $this->createMock(DbFileMapper::class);

		$tagService = new TagService(new TestLogger(), $tagManager, $tagMapper, $dbFileMapper);
		$tagService->setTag(self::$OBJECT_ID_1, self::$TAG_ID_1);
	}
}
