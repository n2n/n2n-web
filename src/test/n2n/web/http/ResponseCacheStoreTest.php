<?php

namespace n2n\web\http;

use PHPUnit\Framework\TestCase;
use n2n\core\cache\AppCache;
use n2n\util\cache\impl\EphemeralCacheStore;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\core\container\TransactionManager;
use n2n\util\uri\Path;
use DateTimeImmutable;
use DateInterval;
use n2n\util\magic\MagicContext;
use n2n\web\http\cache\CachedPayload;

class ResponseCacheStoreTest extends TestCase {

	private MockObject $mockedAppCache;
	private ResponseCacheStore $responseCacheStore;
	private DateTimeImmutable $future;
	private DateTimeImmutable $today;

	function setUp(): void {
		$this->mockedAppCache = $this->createMock(AppCache::class);
		$this->responseCacheStore = new ResponseCacheStore($this->mockedAppCache, new TransactionManager(),
				new ResponseCacheVerifying($this->createMock(MagicContext::class)));
		$this->today = new DateTimeImmutable();
		$this->future = $this->today->add(new DateInterval('PT1M'));
	}


	function testStore() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$path = new Path(['path', 'part2']);

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true], [ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), ['char1' => 1], $responseCacheItem2, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', $path, []), [], $responseCacheItem2, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', $path, []), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), [], $responseCacheItem4, false);

		//shared
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(
				new ResponseCacheId(1, 'hostname', $path, []), true, $this->today));
		$this->assertEquals($responseCacheItem1, $sharedCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData());
		//local
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname']));
		$this->assertEquals($responseCacheItem4, $this->responseCacheStore->get(
				new ResponseCacheId(1, 'hostname', $path, []), false, $this->today));
		$this->assertEquals($responseCacheItem4, $localCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData());

	}

	function testRemove() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true], [ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		//SetUp
		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));
		$responseCacheItem5 = new ResponseCacheItem(new CachedPayload('content five', 5, [], null, $this->future));
		$responseCacheItem6 = new ResponseCacheItem(new CachedPayload('content six', 6, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem2, true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), ['char2' => 1], $responseCacheItem4, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem5, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem6, false);
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));


		//remove from local store
		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1]]));
		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1]]));
		//query param need to be exact and not only part of, if it is set
		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), false);
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1]]));
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1]]));
		$this->assertCount(6, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));


		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1, 'q2' => 2]]));
		$this->assertCount(2, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1, 'q2' => 2]]));
		//order of query param is not relevant, they will be sorted by key (on store and on remove)
		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), false);
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1, 'q2' => 2]]));
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME,
				['method' => 1, 'hostName' => 'hostname', 'query' => ['q1' => 1, 'q2' => 2]]));
		$this->assertCount(5, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(5, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//shared is still intact
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME, ['method' => 1, 'hostName' => 'hostname']));
		$this->assertCount(3, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME, ['method' => 1, 'hostName' => 'hostname']));
		//remove same from shared store
		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), true);
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME, ['method' => 1, 'hostName' => 'hostname']));
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME, ['method' => 1, 'hostName' => 'hostname']));

	}

	function testClearShared() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true], [ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		//SetUp
		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));
		$responseCacheItem5 = new ResponseCacheItem(new CachedPayload('content five', 5, [], null, $this->future));
		$responseCacheItem6 = new ResponseCacheItem(new CachedPayload('content six', 6, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem2, true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), ['char2' => 1], $responseCacheItem4, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem5, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem6, false);
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//clear shared
		$this->responseCacheStore->clear(true);
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

	}

	function testClearLocal() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, false], [ResponseCacheStore::class, true])
				->willReturnOnConsecutiveCalls($localCacheStore, $sharedCacheStore);


		//SetUp
		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));
		$responseCacheItem5 = new ResponseCacheItem(new CachedPayload('content five', 5, [], null, $this->future));
		$responseCacheItem6 = new ResponseCacheItem(new CachedPayload('content six', 6, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), ['char2' => 1], $responseCacheItem4, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem5, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem6, false);
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem2, true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//clear local
		$this->responseCacheStore->clear(false);
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

	}

	function testClear() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true], [ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		//SetUp
		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));
		$responseCacheItem5 = new ResponseCacheItem(new CachedPayload('content five', 5, [], null, $this->future));
		$responseCacheItem6 = new ResponseCacheItem(new CachedPayload('content six', 6, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), ['char2' => 1], $responseCacheItem2, true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), ['char2' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), [], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), ['char1' => 1], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), ['char2' => 1], $responseCacheItem4, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem5, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem6, false);
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//clear both = empty would be same as => null
		$this->responseCacheStore->clear();
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(0, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(0, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

	}

	function testIsExpired(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);


		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content today one', 1, [], null, $this->today));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content today two', 1, [], null, $this->today));
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem2, false);
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//cache is there, and remains there after get
		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//cache there is expired, and will not be shown, requested cacheItems are removed, other maybe expired remains
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->future));
		$this->assertCount(1, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(1, $cacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), false, $this->future));
		$this->assertCount(0, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(0, $cacheStore->findAll(ResponseCacheStore::INDEX_NAME));
	}

	function testMethodParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), false, $this->today));


	}

	function testHostnameParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), [], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testPathParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), [], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false, $this->today));

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testQueryParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), [], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), [], $responseCacheItem2, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false, $this->today));

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testCharacteristicsParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$responseCacheItem1 = new ResponseCacheItem(new CachedPayload('content one', 1, [], null, $this->future));
		$responseCacheItem2 = new ResponseCacheItem(new CachedPayload('content two', 2, [], null, $this->future));
		$responseCacheItem3 = new ResponseCacheItem(new CachedPayload('content three', 3, [], null, $this->future));
		$responseCacheItem4 = new ResponseCacheItem(new CachedPayload('content four', 4, [], null, $this->future));
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []),
				['characteristicsParam1' => 1, 'characteristicsParam2' => 1], $responseCacheItem1, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []),
				['characteristicsParam1' => 2], $responseCacheItem2, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part3']), []),
				['characteristicsParam2' => 1, 'characteristicsParam3' => 3], $responseCacheItem3, false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part4']), []),
				['characteristicsParam1' => 1, 'characteristicsParam2' => 2], $responseCacheItem4, false);

		$this->assertEquals($responseCacheItem1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname',
				new Path(['path', 'part2']), []), false, $this->today));

		$this->responseCacheStore->removeByCharacteristics(['characteristicsParam2' => 1], false);

		//'characteristicsParam1' => 1 are deleted
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']),
				[]), false, $this->today));
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part3']),
				[]), false, $this->today));
		$this->assertEquals($responseCacheItem2, $this->responseCacheStore->get(new ResponseCacheId(1, 'mirror',
				new Path(['path', 'part2']), []), false, $this->today));
		//'characteristicsParam1' => 2 still exist
		$this->assertEquals($responseCacheItem4, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname',
				new Path(['path', 'part4']), []), false, $this->today));

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']),
				[]), false, $this->today));
	}

}