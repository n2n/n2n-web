<?php

namespace n2n\web\http;

use PHPUnit\Framework\TestCase;
use n2n\core\cache\AppCache;
use n2n\cache\impl\ephemeral\EphemeralCacheStore;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\core\container\TransactionManager;
use n2n\util\uri\Path;
use DateTimeImmutable;
use DateInterval;
use n2n\util\magic\MagicContext;
use n2n\web\http\cache\CachedPayload;
use n2n\web\http\cache\ResponseCacheId;
use n2n\web\http\cache\ResponseCacheItem;
use n2n\web\http\cache\ResponseCacheStore;
use n2n\web\http\cache\ResponseCacheVerifying;

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


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), new ResponseCacheItem($cachedPayload2, ['char1' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), new ResponseCacheItem($cachedPayload1, ['char2' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', $path, []), new ResponseCacheItem($cachedPayload2, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', $path, []), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', $path, []), new ResponseCacheItem($cachedPayload4, []), false);

		//shared
		$this->assertCount(1, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(
				new ResponseCacheId(1, 'hostname', $path, []), true, $this->today)->getCachedPayload());
		$this->assertEquals($cachedPayload1, $sharedCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData()->getCachedPayload());
		//local
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(3, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));
		$this->assertCount(1, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname']));
		$this->assertEquals($cachedPayload4, $this->responseCacheStore->get(
				new ResponseCacheId(1, 'hostname', $path, []), false, $this->today)->getCachedPayload());
		$this->assertEquals($cachedPayload4, $localCacheStore->get(ResponseCacheStore::RESPONSE_NAME,
				['method' => 1, 'hostName' => 'hostname', 'path' => $path->__toString(), 'query' => []])->getData()->getCachedPayload());

	}

	function testRemove() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();
		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([ResponseCacheStore::class, true], [ResponseCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		//SetUp
		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);
		$cachedPayload5 = new CachedPayload('content five', 5, [], null, $this->future);
		$cachedPayload6 = new CachedPayload('content six', 6, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char1' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char2' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, ['char2' => 1]), true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), new ResponseCacheItem($cachedPayload4, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload5, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload6, []), false);
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
		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);
		$cachedPayload5 = new CachedPayload('content five', 5, [], null, $this->future);
		$cachedPayload6 = new CachedPayload('content six', 6, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char1' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char2' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, ['char2' => 1]), true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), new ResponseCacheItem($cachedPayload4, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload5, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload6, []), false);
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
		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);
		$cachedPayload5 = new CachedPayload('content five', 5, [], null, $this->future);
		$cachedPayload6 = new CachedPayload('content six', 6, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), new ResponseCacheItem($cachedPayload4, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload5, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload6, ['char1' => 1]), false);
		$this->assertCount(7, $localCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(8, $localCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char1' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char2' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, ['char2' => 1]), true);
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
		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);
		$cachedPayload5 = new CachedPayload('content five', 5, [], null, $this->future);
		$cachedPayload6 = new CachedPayload('content six', 6, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char1' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, ['char2' => 1]), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), true);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, ['char2' => 1]), true);
		$this->assertCount(2, $sharedCacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(4, $sharedCacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 2, 'q1' => 1]), new ResponseCacheItem($cachedPayload3, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1, 'q2' => 2]), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q1' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['q2' => 1]), new ResponseCacheItem($cachedPayload3, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload3, ['char1' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part4']), []), new ResponseCacheItem($cachedPayload4, ['char2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload5, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload6, []), false);
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


		$cachedPayload1 = new CachedPayload('content today one', 1, [], null, $this->today);
		$cachedPayload2 = new CachedPayload('content today two', 1, [], null, $this->today);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, []), false);
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::RESPONSE_NAME));
		$this->assertCount(2, $cacheStore->findAll(ResponseCacheStore::INDEX_NAME));

		//cache is there, and remains there after get
		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());
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

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, []), false);

		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
		$this->assertEquals($cachedPayload2, $this->responseCacheStore->get(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(2, 'hostname', new Path(['path', 'part2']), []), false, $this->today));


	}

	function testHostnameParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload1, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, []), false);

		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []), false, $this->today));
		$this->assertEquals($cachedPayload2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testPathParam(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), new ResponseCacheItem($cachedPayload1, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, []), false);

		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part1']), []), false, $this->today));
		$this->assertEquals($cachedPayload2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testQueryParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);

		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), new ResponseCacheItem($cachedPayload1, []), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), new ResponseCacheItem($cachedPayload2, []), false);

		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->remove(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false);

		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), ['queryParam1' => 1]), false, $this->today));
		$this->assertEquals($cachedPayload2, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []), false, $this->today));
	}

	function testCharacteristicsParams(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []),
				new ResponseCacheItem($cachedPayload1, ['characteristicsParam1' => 1, 'characteristicsParam2' => 1]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']), []),
				new ResponseCacheItem($cachedPayload2, ['characteristicsParam1' => 2]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'mirror', new Path(['path', 'part3']), []),
				new ResponseCacheItem($cachedPayload3, ['characteristicsParam2' => 1, 'characteristicsParam3' => 3]), false);
		$this->responseCacheStore->store(new ResponseCacheId(1, 'hostname', new Path(['path', 'part4']), []),
				new ResponseCacheItem($cachedPayload4, ['characteristicsParam1' => 1, 'characteristicsParam2' => 2]), false);

		$this->assertEquals($cachedPayload1, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname',
				new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->removeByCharacteristics(['characteristicsParam2' => 1], false);

		//'characteristicsParam1' => 1 are deleted
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']),
				[]), false, $this->today));
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part3']),
				[]), false, $this->today));
		$this->assertEquals($cachedPayload2, $this->responseCacheStore->get(new ResponseCacheId(1, 'mirror',
				new Path(['path', 'part2']), []), false, $this->today)->getCachedPayload());
		//'characteristicsParam1' => 2 still exist
		$this->assertEquals($cachedPayload4, $this->responseCacheStore->get(new ResponseCacheId(1, 'hostname',
				new Path(['path', 'part4']), []), false, $this->today)->getCachedPayload());

		$this->responseCacheStore->clear(false);
		$this->assertNull($this->responseCacheStore->get(new ResponseCacheId(1, 'mirror', new Path(['path', 'part2']),
				[]), false, $this->today));
	}

}