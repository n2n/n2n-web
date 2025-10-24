<?php

namespace n2n\web\http\cache;

use n2n\core\container\TransactionManager;
use n2n\core\cache\AppCache;
use n2n\util\ex\IllegalStateException;
use n2n\cache\CacheStore;
use n2n\cache\CharacteristicsList;

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
		if (!$this->isClosed()) {
			return;
		}

		throw new IllegalStateException(self::class . ' already closed.');
	}

	private function getCacheStore(bool $shared): CacheStore {
		$this->ensureNotClosed();

		if ($shared) {
			return $this->sharedCacheStore ?? $this->sharedCacheStore
					= $this->appCache->lookupCacheStore(PayloadCacheStore::class, true);
		}

		return $this->localCacheStore ?? $this->localCacheStore
				= $this->appCache->lookupCacheStore(PayloadCacheStore::class, false);
	}

	/**
	 * @param bool|null $shared
	 * @return CacheStore[]
	 */
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

	public function store(string $srcName, CharacteristicsList|array $characteristicsList, CachedPayload $item, bool $shared): void {
		$this->getCacheStore($shared)->store($srcName, CharacteristicsList::fromArg($characteristicsList), $item);
	}

	public function get(string $srcName, CharacteristicsList|array $characteristicsList, bool $shared, ?\DateTimeInterface $now = null): ?CachedPayload {
		$characteristicsList = CharacteristicsList::fromArg($characteristicsList);
		$cacheStore = $this->getCacheStore($shared);
		$cacheItem = $cacheStore->get($srcName, $characteristicsList);
		if ($cacheItem === null) {
			return null;
		}

		if ($now === null) {
			$now = new \DateTime();
		}

		$data = $cacheItem->getData();
		if (!($data instanceof CachedPayload) || $data->isExpired($now)) {
			$cacheStore->remove($srcName, $characteristicsList);
			return null;
		}

		return $data;
	}

	public function remove(string $srcName, CharacteristicsList|array $characteristicsList, bool $shared): void {
		$characteristicsList = CharacteristicsList::fromArg($characteristicsList);
		$this->cacheActionQueue->registerRemoveAction(false, function ()
				use ($srcName, $characteristicsList, $shared) {
			$this->getCacheStore($shared)->remove($srcName, $characteristicsList);
		});
	}

	public function removeAll(?string $srcName = null, CharacteristicsList|array|null $characteristicNeedlesList = null, ?bool $shared = null): void {
		$characteristicNeedlesList = CharacteristicsList::fromArg($characteristicNeedlesList);
		$this->cacheActionQueue->registerRemoveAction(false, function ()
				use ($shared, $srcName, $characteristicNeedlesList) {
			foreach ($this->getCacheStores($shared) as $cacheStore) {
				$cacheStore->removeAll($srcName, $characteristicNeedlesList);
			}
		});
	}

	public function clear(?bool $shared = null): void {
		foreach ($this->getCacheStores($shared) as $cacheStore) {
			$cacheStore->clear();
		}
	}

}