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
		$path = new Path(['path','part2']);

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true],[ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);
		$responseCacheItem3 = new ResponseCacheItem('content three', 3, [], null, $this->future);
		$responseCacheItem4 = new ResponseCacheItem('content four', 4, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], ['char1' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(1, 'mirror', new Path(['path','part2']), [], [], $responseCacheItem2, false);
		$this->responseCacheStore->store(2, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem3, false);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem4, false);

		//shared
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], true, $this->today));
		$this->assertEquals($responseCacheItem1, $sharedCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData());
		//local
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME, ['method' => 1, 'hostName' => 'hostname']));
		$this->assertEquals($responseCacheItem4, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));
		$this->assertEquals($responseCacheItem4, $localCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData());

	}

	function testMethodParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem1, false);
		$this->responseCacheStore->store(2, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['path','part2']), [], false);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(2, 'hostname', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(2, 'hostname', new Path(['path','part2']), [], false, $this->today));


	}

	function testHostnameParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'mirror', new Path(['path','part2']), [], [], $responseCacheItem1, false);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'mirror', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->remove(1, 'mirror', new Path(['path','part2']), [], false);

		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['path','part2']), [], false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));
	}

	function testPathParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part1']), [], [], $responseCacheItem1, false);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part1']), [], false, $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['path','part1']), [], false);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part1']), [], false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));
	}

	function testQueryParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem('content one', 1, [], null, $this->future);
		$responseCacheItem2 = new ResponseCacheItem('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), ['queryParam1' => 1], [], $responseCacheItem1, false);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [], [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), ['queryParam1' => 1], false, $this->today));

		$this->responseCacheStore->remove(1, 'hostname', new Path(['path','part2']), ['queryParam1' => 1], false);

		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), ['queryParam1' => 1], false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']), [], false, $this->today));
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
		$responseCacheItem4 = new ResponseCacheItem('content four', 4, [], null, $this->future);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part2']), [],
				['characteristicsParam1' => 1, 'characteristicsParam2' => 1], $responseCacheItem1, false);
		$this->responseCacheStore->store(1, 'mirror', new Path(['path','part2']), [],
				['characteristicsParam1' => 2], $responseCacheItem2, false);
		$this->responseCacheStore->store(1, 'mirror', new Path(['path','part3']), [],
				['characteristicsParam2' => 1, 'characteristicsParam3' => 3], $responseCacheItem3, false);
		$this->responseCacheStore->store(1, 'hostname', new Path(['path','part4']), [],
				['characteristicsParam1' => 1, 'characteristicsParam2' => 2], $responseCacheItem4, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(1, 'hostname',
				new Path(['path','part2']), [], false, $this->today));

		$this->responseCacheStore->removeByCharacteristics(['characteristicsParam2' => 1], false);

		//'characteristicsParam1' => 1 are deleted
		$this->assertNull($this->responseCacheStore->get(1, 'hostname', new Path(['path','part2']),
				[], false, $this->today));
		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['path','part3']),
				[], false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(1, 'mirror',
				new Path(['path','part2']), [], false, $this->today));
		//'characteristicsParam1' => 2 still exist
		$this->assertEquals($responseCacheItem4, $this->responseCacheStore->get(1, 'hostname',
				new Path(['path','part4']), [], false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(1, 'mirror', new Path(['path','part2']),
				[], false, $this->today));
	}

}