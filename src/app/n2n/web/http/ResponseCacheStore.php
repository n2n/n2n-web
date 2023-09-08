<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\http;

use n2n\util\uri\Path;
use n2n\core\cache\AppCache;
use n2n\core\container\TransactionManager;
use n2n\util\cache\CacheStore;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\cache\CacheActionQueue;

class ResponseCacheStore {
	const RESPONSE_NAME = 'r';
	const INDEX_NAME = 'i';
	
	private CacheActionQueue $responseCacheActionQueue;
	private TransactionManager $tm;

	private ?CacheStore $sharedCacheStore = null;
	private ?CacheStore $localCacheStore = null;
	
	function __construct(private AppCache $appCache, TransactionManager $transactionManager) {
		$this->responseCacheActionQueue = new CacheActionQueue();
		$transactionManager->registerResource($this->responseCacheActionQueue);
		$this->tm = $transactionManager;
	}

	function close(): void {
		$this->tm->unregisterResource($this->responseCacheActionQueue);
		unset($this->responseCacheActionQueue);
		$this->sharedCacheStore = null;
		$this->localCacheStore = null;
	}

	function isClosed(): bool {
		return !isset($this->responseCacheActionQueue);
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
	
	private function buildResponseCharacteristics(int $method, string $hostName, Path $path,
			array $queryParams = null): array {
		if ($queryParams !== null) {
			ksort($queryParams);
		}
		return array('method' => $method, 'hostName' => $hostName, 'path' => $path->__toString(), 
				'query' => $queryParams);
	}
	
	private function buildIndexCharacteristics(array $responseCharacteristics, array $customCharacteristics): array {
		foreach ($customCharacteristics as $key => $value) {
			$responseCharacteristics['cust' . $key] = $value;
		}
		return $responseCharacteristics;
	}
	
	public function store(int $method, string $hostName, Path $path, ?array $queryParams,
			array $characteristics, ResponseCacheItem $item, bool $shared): void {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams);
		$this->getCacheStore($shared)->store(self::RESPONSE_NAME, $responseCharacteristics, $item);
		$this->getCacheStore($shared)->store(self::INDEX_NAME,
				$this->buildIndexCharacteristics($responseCharacteristics, $characteristics), 
				$responseCharacteristics);
	}
	
	public function get(int $method, string $hostName, Path $path, ?array $queryParams,
			bool $shared, \DateTime $now = null): ?ResponseCacheItem {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams);

		$cacheItem = $this->getCacheStore($shared)->get(self::RESPONSE_NAME, $responseCharacteristics);
		if ($cacheItem === null) {
			return null;
		}

		if ($now === null) {
			$now = new \DateTime();
		}

		$data = $cacheItem->getData();
		$indexCharacteristics = $this->buildIndexCharacteristics($responseCharacteristics, array());
		if (!($data instanceof ResponseCacheItem) || $data->isExpired($now)) {
			$responseCharacteristics = $cacheItem->getCharacteristics();
			$this->getCacheStore($shared)->remove(self::RESPONSE_NAME, $responseCharacteristics);
			$this->getCacheStore($shared)->removeAll(self::INDEX_NAME, $indexCharacteristics);
			return null;
		}
		
		return $data;
	}
	
	public function remove(int $method, string $hostName, Path $path, ?array $queryParams, bool $shared): void {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams);
		$indexCharacteristics = $this->buildIndexCharacteristics($responseCharacteristics, array());
		
		$that = $this;
		$this->responseCacheActionQueue->registerRemoveAction(false, function ()
				use ($that, $responseCharacteristics, $indexCharacteristics, $shared){
			$that->getCacheStore($shared)->remove(self::RESPONSE_NAME, $responseCharacteristics);
			$that->getCacheStore($shared)->removeAll(self::INDEX_NAME, $indexCharacteristics);
		});
	}

	private function removeByResponseCharacteristics(array $responseCharacteristics): void {

	}
	
// 	public function removeByFilter(string $method, string $hostName, Path $path, array $queryParams = null,
// 			array $characteristicNeedles) {
// 		$this->cacheStore->removeAll(self::RESPONSE_NAME, $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams, 
// 				$characteristicNeedles));
// 	}
	
	public function removeByCharacteristics(array $characteristicNeedles, bool $shared): void {
		$cacheItems = $this->getCacheStore($shared)->findAll(self::INDEX_NAME, $this->buildIndexCharacteristics(
				array(), $characteristicNeedles));
		$this->responseCacheActionQueue->registerRemoveAction(false, function () use ($cacheItems, $shared) {
			foreach ($cacheItems as $cacheItem) {
				$responseCharacteristics = $cacheItem->getData();
				if (is_array($responseCharacteristics)) {
					$this->getCacheStore($shared)->remove(self::RESPONSE_NAME, $responseCharacteristics);
				}
				$this->getCacheStore($shared)->remove(self::INDEX_NAME, $cacheItem->getCharacteristics());
			}
		});
	}

	public function clear(bool $shared = null): void {
		foreach ($this->getCacheStores($shared) as $cacheStore) {
			$cacheStore->clear();
		}
	}
}
