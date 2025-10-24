<?php

namespace n2n\web\http\cache;

use PHPUnit\Framework\TestCase;
use n2n\core\cache\AppCache;
use n2n\cache\impl\ephemeral\EphemeralCacheStore;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\core\container\TransactionManager;
use DateTimeImmutable;
use DateInterval;
use n2n\cache\CharacteristicsList;

class PayloadCacheStoreTest extends TestCase {

	private MockObject $mockedAppCache;
	private PayloadCacheStore $payloadCacheStore;
	private DateTimeImmutable $today;
	private DateTimeImmutable $future;

	function setUp(): void {
		$this->mockedAppCache = $this->createMock(AppCache::class);
		$this->payloadCacheStore = new PayloadCacheStore($this->mockedAppCache, new TransactionManager());
		$this->today = new DateTimeImmutable();
		$this->future = $this->today->add(new DateInterval('PT1M'));
	}

	function testStore() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([PayloadCacheStore::class, true], [PayloadCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);

		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload3, true);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload4, true);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);

		$this->assertEquals($cachedPayload3, $this->payloadCacheStore->get(
				'holeradio', ['char1' => 1], true));
		$this->assertEquals($cachedPayload3, $sharedCacheStore->get('holeradio',
				new CharacteristicsList(['char1' => 1]))->getData());

		$this->assertEquals($cachedPayload1, $this->payloadCacheStore->get(
				'holeradio', ['char1' => 1], false));
		$this->assertEquals($cachedPayload1, $localCacheStore->get('holeradio',
				new CharacteristicsList(['char1' => 1]))->getData());
	}

	function testRemove() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([PayloadCacheStore::class, true], [PayloadCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);


		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload3, true);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload4, true);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);

		//check before and after remove from local store
		$this->assertEquals($cachedPayload1, $this->payloadCacheStore->get('holeradio', ['char1' => 1], false));
		$this->payloadCacheStore->remove('holeradio', ['char1' => 1], false);
		$this->assertNull($this->payloadCacheStore->get('holeradio', ['char1' => 1], false));

		//check before and after remove from shared store (shared was still intact after remove from local store)
		$this->assertEquals($cachedPayload3, $this->payloadCacheStore->get('holeradio', ['char1' => 1], true));
		$this->payloadCacheStore->remove('holeradio', ['char1' => 1], true);
		$this->assertNull($this->payloadCacheStore->get('holeradio', ['char1' => 1], true));

	}

	function testClearShared() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([PayloadCacheStore::class, false], [PayloadCacheStore::class, true])
				->willReturnOnConsecutiveCalls($localCacheStore, $sharedCacheStore);


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);

		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload3, true);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload4, true);

		$this->payloadCacheStore->clear(false);
		$this->assertCount(0, $localCacheStore->findAll('holeradio'));
		$this->assertCount(2, $sharedCacheStore->findAll('holeradio'));
	}

	function testClearLocal() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([PayloadCacheStore::class, true], [PayloadCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);

		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload3, true);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload4, true);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);

		$this->payloadCacheStore->clear(true);
		$this->assertCount(0, $sharedCacheStore->findAll('holeradio'));
		$this->assertCount(2, $localCacheStore->findAll('holeradio'));
	}

	function testClear() {
		$localCacheStore = new EphemeralCacheStore();
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(2))
				->method('lookupCacheStore')
				->withConsecutive([PayloadCacheStore::class, true], [PayloadCacheStore::class, false])
				->willReturnOnConsecutiveCalls($sharedCacheStore, $localCacheStore);


		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->future);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->future);
		$cachedPayload3 = new CachedPayload('content three', 3, [], null, $this->future);
		$cachedPayload4 = new CachedPayload('content four', 4, [], null, $this->future);

		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload3, true);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload4, true);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);

		$this->payloadCacheStore->clear();
		$this->assertCount(0, $sharedCacheStore->findAll('holeradio'));
		$this->assertCount(0, $localCacheStore->findAll('holeradio'));
	}

	function testIsExpired(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(PayloadCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('content one', 1, [], null, $this->today);
		$cachedPayload2 = new CachedPayload('content two', 2, [], null, $this->today);
		$this->payloadCacheStore->store('holeradio', ['char1' => 1], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio', ['char2' => 1], $cachedPayload2, false);

		//cache is there, and remains there after get
		$this->assertEquals($cachedPayload1, $this->payloadCacheStore->get('holeradio', ['char1' => 1], false, $this->today));
		$this->assertCount(2, $cacheStore->findAll('holeradio'));

		//cache there is expired, and will not be shown, requested cacheItems are removed, other maybe expired remains
		$this->assertNull($this->payloadCacheStore->get('holeradio', ['char1' => 1], false, $this->future));
		$this->assertCount(1, $cacheStore->findAll('holeradio'));
		$this->assertNull($this->payloadCacheStore->get('holeradio', ['char2' => 1], false, $this->future));
		$this->assertCount(0, $cacheStore->findAll('holeradio'));

	}

	function testSrcName(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(PayloadCacheStore::class, false)
				->willReturn($cacheStore);


		$cachedPayload = new CachedPayload('some content', 2, [], null, $this->future);

		$this->payloadCacheStore->store('holeradio', [], $cachedPayload, false);

		$this->assertEquals($cachedPayload, $this->payloadCacheStore->get('holeradio', [], false));

		$this->payloadCacheStore->remove('holeradio', [], false);

		$this->assertNull($this->payloadCacheStore->get('holeradio', [], false));
	}

	function testCharacteristics(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(PayloadCacheStore::class, false)
				->willReturn($cacheStore);

		$cachedPayload1 = new CachedPayload('some content', 1, [], null, $this->today);
		$cachedPayload2 = new CachedPayload('some content-2', 2, [], null, $this->today);
		$cachedPayload3 = new CachedPayload('some content-3', 3, [], null, $this->today);

		$this->payloadCacheStore->store('holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], $cachedPayload1, false);
		$this->payloadCacheStore->store('holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], $cachedPayload2, false);
		$this->payloadCacheStore->store('holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], $cachedPayload3, false);

		$this->assertEquals($cachedPayload1,
				$this->payloadCacheStore->get('holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $this->today));
		$this->assertEquals($cachedPayload2,
				$this->payloadCacheStore->get('holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], false, $this->today));
		$this->assertEquals($cachedPayload3,
				$this->payloadCacheStore->get('holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], false, $this->today));

		$this->payloadCacheStore->removeAll(null, ['chr2' => 'zwei'], false);

		$this->assertEquals($cachedPayload1,
				$this->payloadCacheStore->get('holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $this->today));
		$this->assertNull(
				$this->payloadCacheStore->get('holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], false, $this->today));
		$this->assertNull(
				$this->payloadCacheStore->get('holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], false, $this->today));

		$this->payloadCacheStore->clear(false);

		$this->assertNull(
				$this->payloadCacheStore->get('holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $this->today));
	}

}