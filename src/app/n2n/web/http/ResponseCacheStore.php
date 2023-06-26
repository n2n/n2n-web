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
use n2n\context\ThreadScoped;
use n2n\core\cache\AppCache;
use n2n\core\container\TransactionManager;
use n2n\core\container\TransactionalResource;
use n2n\core\container\Transaction;
use n2n\util\cache\CacheStore;
use n2n\util\ex\IllegalStateException;

class ResponseCacheStore {
	const RESPONSE_NAME = 'r';
	const INDEX_NAME = 'i';
	
	private ?CacheStore $cacheStore;
	private cache\CacheActionQueue $responseCacheActionQueue;
	private TransactionManager $tm;
	
	function __construct(AppCache $appCache, TransactionManager $transactionManager) {
		$this->cacheStore = $appCache->lookupCacheStore(ResponseCacheStore::class, true);
		$this->responseCacheActionQueue = new cache\CacheActionQueue();
		$transactionManager->registerResource($this->responseCacheActionQueue);
		$this->tm = $transactionManager;
	}

	function close(): void {
		$this->tm->unregisterResource($this->responseCacheActionQueue);
		$this->cacheStore = null;
	}

	function isClosed(): bool {
		return $this->cacheStore === null;
	}

	private function ensureNotClosed(): void {
		if ($this->isClosed()) {
			return;
		}

		throw new IllegalStateException(self::class . ' already closed.');
	}
	
	private function buildResponseCharacteristics(int $method, string $hostName, Path $path,
			array $queryParams = null) {
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
	
	public function store(int $method, string $hostName, Path $path, array $queryParams = null,
			array $characteristics, ResponseCacheItem $item): void {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams);
		$this->cacheStore->store(self::RESPONSE_NAME, $responseCharacteristics, $item);
		$this->cacheStore->store(self::INDEX_NAME, 
				$this->buildIndexCharacteristics($responseCharacteristics, $characteristics), 
				$responseCharacteristics);
	}
	
	public function get(int $method, string $hostName, Path $path, array $queryParams = null,
			\DateTime $now = null): ?ResponseCacheItem {
		$cacheItem = $this->cacheStore->get(self::RESPONSE_NAME, 
				$this->buildResponseCharacteristics($method, $hostName, $path, $queryParams));
		if ($cacheItem === null) return null;
		
		if ($now === null) {
			$now = new \DateTime();
		}
		
		$data = $cacheItem->getData();
		if (!($data instanceof ResponseCacheItem) || $data->isExpired($now)) {
			$responseCharacteristics = $cacheItem->getCharacteristics();
			$this->cacheStore->remove(self::RESPONSE_NAME, $responseCharacteristics);
			return null;
		}
		
		return $data;
	}
	
	public function remove(int $method, string $hostName, Path $path, array $queryParams = null): void {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams);
		$indexCharacteristics = $this->buildIndexCharacteristics($responseCharacteristics, array());
		
		$that = $this;
		$this->responseCacheActionQueue->registerAction(false, function ()
				use ($that, $responseCharacteristics, $indexCharacteristics){
			$that->cacheStore->remove(self::RESPONSE_NAME, $responseCharacteristics);
			$that->cacheStore->removeAll(self::INDEX_NAME, $indexCharacteristics);
		});
	}
	
// 	public function removeByFilter(string $method, string $hostName, Path $path, array $queryParams = null,
// 			array $characteristicNeedles) {
// 		$this->cacheStore->removeAll(self::RESPONSE_NAME, $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams, 
// 				$characteristicNeedles));
// 	}
	
	public function removeByCharacteristics(array $characteristicNeedles): void {
		$cacheItems = $this->cacheStore->findAll(self::INDEX_NAME, $this->buildIndexCharacteristics(
				array(), $characteristicNeedles));
		$that = $this;
		$this->responseCacheActionQueue->registerAction(false, function () use ($that, $cacheItems) {
			foreach ($cacheItems as $cacheItem) {
				$responseCharacteristics = $cacheItem->getData();
				if (is_array($responseCharacteristics)) {
					$that->cacheStore->remove(self::RESPONSE_NAME, $responseCharacteristics);
				}
				$that->cacheStore->remove(self::INDEX_NAME, $cacheItem->getCharacteristics());
			}
		});
	}
	
	public function clear(): void {
		$this->cacheStore->clear();
	}
}
