<?php

namespace n2n\web\http\cache;

use n2n\web\http\cache\CacheActionQueue;
use n2n\core\container\TransactionManager;
use n2n\core\cache\AppCache;
use n2n\web\http\ResponseCacheStore;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\ResponseCacheItem;
use n2n\util\cache\CacheStore;

class PayloadCacheStore {

	private CacheActionQueue $cacheActionQueue;
	private TransactionManager $tm;

	private ?CacheStore $sharedCacheStore = null;
	private ?CacheStore $localCacheStore = null;

	function __construct(private AppCache $appCache, TransactionManager $transactionManager) {
		$this->cacheActionQueue = new CacheActionQueue();
		$transactionManager->registerResource($this->cacheActionQueue);
		$this->tm = $transactionManager;
	}

	function close(): void {
		$this->tm->unregisterResource($this->cacheActionQueue);
		unset($this->cacheActionQueue);
		$this->sharedCacheStore = null;
		$this->localCacheStore = null;
	}

	function isClosed(): bool {
		return !isset($this->cacheActionQueue);
	}

	private function ensureNotClosed(): void {
		if ($this->isClosed()) {
			return;
		}

		throw new IllegalStateException(self::class . ' already closed.');
	}

	private function getCacheStore(bool $shared): CacheStore {
		$this->ensureNotClosed();

		if ($shared) {
			return $this->sharedCacheStore ?? $this->sharedCacheStore
					= $this->appCache->lookupCacheStore(ResponseCacheStore::class, true);
		}

		return $this->localCacheStore ?? $this->localCacheStore
				= $this->appCache->lookupCacheStore(ResponseCacheStore::class, false);
	}

	private function getCacheStores(?bool $shared): array {
		$cacheStores = [];
		if ($shared === null || $shared === true) {
			$cacheStores[] = $this->getCacheStore(true);
		}
		if ($shared === null || $shared === false) {
			$cacheStores[] = $this->getCacheStore(false);
		}
		return $cacheStores;
	}

	public function store(string $srcName, array $characteristics, ResponseCacheItem $item, bool $shared): void {
		$this->getCacheStore($shared)->store($srcName, $characteristics, $item);
	}

	public function get(string $srcName, array $characteristics, bool $shared, \DateTime $now = null): ?ResponseCacheItem {
		$cacheStore = $this->getCacheStore($shared);
		$cacheItem = $cacheStore->get($srcName, $characteristics);;
		if ($cacheItem === null) {
			return null;
		}

		if ($now === null) {
			$now = new \DateTime();
		}

		$data = $cacheItem->getData();
		if (!($data instanceof ResponseCacheItem) || $data->isExpired($now)) {
			$cacheStore->remove($srcName, $characteristics);
			return null;
		}

		return $data;
	}

	public function remove(string $srcName, array $characteristics, bool $shared): void {
		$this->cacheActionQueue->registerAction(false, function ()
				use ($srcName, $characteristics, $shared) {
			$this->getCacheStore($shared)->remove($srcName, $characteristics);
		});
	}

	public function removeAll(string $srcName = null, array $characteristicNeedles = null, bool $shared = null): void {
		$this->cacheActionQueue->registerAction(false, function ()
				use ($shared, $srcName, $characteristicNeedles) {
			foreach ($this->getCacheStores($shared) as $cacheStore) {
				$cacheStore->removeAll($srcName, $characteristicNeedles);
			}
		});
	}

	public function clear(bool $shared = null): void {
		foreach ($this->getCacheStores($shared) as $cacheStore) {
			$cacheStore->clear();
		}
	}

}