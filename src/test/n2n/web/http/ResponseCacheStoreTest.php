<?php

namespace n2n\web\http;

use PHPUnit\Framework\TestCase;
use n2n\core\cache\AppCache;
use n2n\util\cache\impl\EphemeralCacheStore;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\core\container\TransactionManager;
use n2n\util\uri\Path;

class ResponseCacheStoreTest extends TestCase {

	private MockObject $mockedAppCache;
	private ResponseCacheStore $responseCacheStore;
	private \DateTimeImmutable $future;
	private \DateTime $today;

	function setUp(): void {
		$this->mockedAppCache = $this->createMock(AppCache::class);
		$this->responseCacheStore = new ResponseCacheStore($this->mockedAppCache, new TransactionManager());
		$now = new \DateTimeImmutable();
		$this->future = $now->add(new \DateInterval('PT1M'));
		$this->today = \DateTime::createFromImmutable($now);
	}


	function testStore() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($localCacheStore);
		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, true)
				->willReturn($sharedCacheStore);

		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$cacheStore->get(ResponseCacheStore::RESPONSE_NAME, ['method' => 1, 'hostName' => $hostName, 'path' => $path->__toString(),
				'query' => $queryParams]);

		$cacheStore->get(ResponseCacheStore::RESPONSE_NAME, []);
	}

	function testMethodParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem1);
		$this->responseCacheStore->store(2, 'hostname', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem2);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['pathPart1','part2']), false, []);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(2, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(2, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));


	}

	function testHostnameParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'mirror', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem1);
		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem2);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'mirror', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->remove(1, 'mirror', new Path(['pathPart1','part2']), false, []);

		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['pathPart1','part2']), false, [], $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));
	}

	function testPathParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part1']), false, [], [], $responseCacheItem1);
		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem2);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part1']), false, [], $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['pathPart1','part1']), false, []);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part1']), false, [], $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));
	}

	function testQueryParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, ['queryParam1' => 1], [], $responseCacheItem1);
		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [], [], $responseCacheItem2);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, ['queryParam1' => 1], $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['pathPart1','part2']), false, ['queryParam1' => 1]);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, ['queryParam1' => 1], $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']), false, [], $this->today));
	}

	function testCharacteristicsParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);
		$responseCacheItem3 = new ResponseCacheItem('content three', 3, [], null, $this->future);
		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [],
				['characteristicsParam1' => 1, 'characteristicsParam2' => 1], $responseCacheItem1);
		$this->responseCacheStore->store(1, 'mirror', new Path(['pathPart1','part2']), false, [],
				['characteristicsParam1' => 2], $responseCacheItem2);
		$this->responseCacheStore->store(1, 'mirror', new Path(['pathPart1','part3']), false, [],
				['characteristicsParam2' => 1, 'characteristicsParam3' => 3], $responseCacheItem3);
		// TODO: check
		$this->responseCacheStore->store(1, 'hostname', new Path(['pathPart1','part2']), false, [],
				['characteristicsParam1' => 1, 'characteristicsParam2' => 2], $responseCacheItem4);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname',
				new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->removeByCharacteristics(['characteristicsParam2' => 1], false);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['pathPart1','part2']),
				false, [], $this->today));
		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['pathPart1','part3']),
				false, [], $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'mirror',
				new Path(['pathPart1','part2']), false, [], $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['pathPart1','part2']),
				false, [], $this->today));
	}

}