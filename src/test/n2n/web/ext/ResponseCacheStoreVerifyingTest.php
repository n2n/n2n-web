<?php

namespace n2n\web\ext;

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
use n2n\web\http\cache\ResponseCacheItem;
use n2n\web\http\cache\ResponseCacheStore;
use n2n\web\http\cache\ResponseCacheId;
use n2n\web\http\cache\ResponseCacheVerifying;
use n2n\web\http\cache\ResponseCacheVerifier;

class ResponseCacheStoreVerifyingTest extends TestCase {

	private MockObject $mockedAppCache;
	private ResponseCacheStore $responseCacheStore;
	private DateTimeImmutable $future;
	private DateTimeImmutable $today;
	private MockObject $magicContextMock;

	function setUp(): void {
		$this->mockedAppCache = $this->createMock(AppCache::class);
		$this->responseCacheStore = new ResponseCacheStore($this->mockedAppCache, new TransactionManager(),
				new ResponseCacheVerifying($this->magicContextMock = $this->createMock(MagicContext::class)));
		$this->today = new DateTimeImmutable();
		$this->future = $this->today->add(new DateInterval('PT1M'));
	}


	function testVerifyValid() {
		$sharedCacheStore = new EphemeralCacheStore();

		$this->mockedAppCache->expects($this->exactly(1))
				->method('lookupCacheStore')
				->with(ResponseCacheStore::class, true)
				->willReturn($sharedCacheStore);

		$responseCacheId = new ResponseCacheId(1, 'hostname', new Path(['path', 'part2']), []);
		$responseCacheItem = new ResponseCacheItem(
				new CachedPayload('content one', 1, [], null, $this->future),
				['char1' => '1'], 'VerifierMock');


		$verifierMock = $this->createMock(ResponseCacheVerifier::class);
		$verifierMock->expects($this->once())->method('verifyValidity')
				->with($responseCacheId, $responseCacheItem)
				->willReturn(true);

		$this->magicContextMock->expects($this->exactly(2))
				->method('lookup')
				->with('VerifierMock')
				->willReturn($verifierMock);

		$this->responseCacheStore->store($responseCacheId, $responseCacheItem, true);
		$this->assertEquals($responseCacheItem, $this->responseCacheStore->get($responseCacheId, true));
	}

}