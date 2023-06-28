<?php

namespace n2n\web\http\cache;

use PHPUnit\Framework\TestCase;
use n2n\core\cache\impl\EphemeralAppCache;
use n2n\core\cache\AppCache;
use n2n\util\cache\impl\EphemeralCacheStore;
use PHPUnit\Framework\MockObject\MockObject;
use n2n\web\http\ResponseCacheItem;
use n2n\core\container\TransactionManager;

class PayloadCacheStoreTest extends TestCase {

	private MockObject $mockedAppCache;
	private PayloadCacheStore $payloadCacheStore;

	function setUp(): void {
		$this->mockedAppCache = $this->createMock(AppCache::class);
		$this->payloadCacheStore = new PayloadCacheStore($this->mockedAppCache, new TransactionManager());
	}

	function testSrcName(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(PayloadCacheStore::class, false)
				->willReturn($cacheStore);

		$now = new \DateTimeImmutable();
		$future = $now->add(new \DateInterval('PT1M'));


		$responseCacheItem = new ResponseCacheItem('some content', 2, [], null, $future);

		$this->payloadCacheStore->store('holeradio', [], $responseCacheItem, false);

		$this->assertEquals($responseCacheItem, $this->payloadCacheStore->get('holeradio', [], false));

		$this->payloadCacheStore->remove('holeradio', [], false);

		$this->assertNull($this->payloadCacheStore->get('holeradio', [], false));
	}

	function testCharacteristics(): void {
		$cacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->once())
				->method('lookupCacheStore')
				->with(PayloadCacheStore::class, false)
				->willReturn($cacheStore);

		$now = new \DateTimeImmutable();


		$responseCacheItem1 = new ResponseCacheItem('some content', 1, [], null, $now);
		$responseCacheItem2 = new ResponseCacheItem('some content-2', 2, [], null, $now);
		$responseCacheItem3 = new ResponseCacheItem('some content-3', 3, [], null, $now);

		$this->payloadCacheStore->store('holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], $responseCacheItem1, false);
		$this->payloadCacheStore->store('holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], $responseCacheItem2, false);
		$this->payloadCacheStore->store('holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], $responseCacheItem3, false);

		$this->assertEquals($responseCacheItem1,
				$this->payloadCacheStore->get( 'holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $now));
		$this->assertEquals($responseCacheItem2,
				$this->payloadCacheStore->get( 'holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], false, $now));
		$this->assertEquals($responseCacheItem3,
				$this->payloadCacheStore->get( 'holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], false, $now));

		$this->payloadCacheStore->removeAll(null, ['chr2' => 'zwei'], false);

		$this->assertEquals($responseCacheItem1,
				$this->payloadCacheStore->get( 'holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $now));
		$this->assertNull(
				$this->payloadCacheStore->get( 'holeradio-2', ['chr0' => 0, 'chr2' => 'zwei'], false, $now));
		$this->assertNull(
				$this->payloadCacheStore->get( 'holeradio-3', ['chr1' => 2, 'chr2' => 'zwei'], false, $now));

		$this->payloadCacheStore->clear(false);

		$this->assertNull(
				$this->payloadCacheStore->get( 'holeradio', ['chr1' => 1, 'chr2' => 'not-zwei'], false, $now));
	}

}