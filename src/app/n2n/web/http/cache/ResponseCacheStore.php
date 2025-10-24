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

namespace n2n\web\http\cache;

use DateTimeImmutable;
use DateTimeInterface;
use n2n\core\cache\AppCache;
use n2n\core\container\TransactionManager;
use n2n\cache\CacheStore;
use n2n\util\ex\IllegalStateException;
use n2n\cache\CharacteristicsList;

class ResponseCacheStore {
	const RESPONSE_NAME = 'r';
	const INDEX_NAME = 'i';

	private CacheActionQueue $responseCacheActionQueue;
	private TransactionManager $tm;

	private ?CacheStore $sharedCacheStore = null;
	private ?CacheStore $localCacheStore = null;

	function __construct(private AppCache $appCache, TransactionManager $transactionManager,
			private ResponseCacheVerifying $verifying) {
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

	private function buildResponseCharacteristics(ResponseCacheId $responseCacheId): CharacteristicsList {
		$method = $responseCacheId->getMethod();
		$hostName = $responseCacheId->getHostName();
		$path = $responseCacheId->getPath();
		$queryParams = $responseCacheId->getQueryParams();

		if ($queryParams !== null) {
			ksort($queryParams);
		}
		return new CharacteristicsList(array('method' => $method, 'hostName' => $hostName, 'path' => $path->__toString(),
				'query' => json_encode($queryParams)));
	}

	const CUSTOM_KEY_PREFIX = 'cust';

	private function buildIndexCharacteristicsList(CharacteristicsList $responseCharacteristicsList,
			CharacteristicsList $customCharacteristicsList): CharacteristicsList {
		$responseCharacteristics = $responseCharacteristicsList->toArray();
		$customCharacteristics = $customCharacteristicsList->toArray();
		ksort($customCharacteristics);
		foreach ($customCharacteristics as $key => $value) {
			$responseCharacteristics[self::CUSTOM_KEY_PREFIX . $key] = $value;
		}
		return new CharacteristicsList($responseCharacteristics);
	}

	public function store(ResponseCacheId $responseCacheId,
			ResponseCacheItem $item, bool $shared): void {
		if ($item->hasVerifier()) {
			$this->verifying->assertVerifier($item->getVerifierLookupId());
		}

		$responseCharacteristics = $this->buildResponseCharacteristics($responseCacheId);
		$this->getCacheStore($shared)->store(self::RESPONSE_NAME, $responseCharacteristics, $item);
		$this->getCacheStore($shared)->store(self::INDEX_NAME,
				$this->buildIndexCharacteristicsList($responseCharacteristics, $item->getCharacteristicsList()),
				$responseCharacteristics);
	}

	public function get(ResponseCacheId $responseCacheId,
			bool $shared, ?DateTimeInterface $now = null): ?ResponseCacheItem {
		$responseCharacteristics = $this->buildResponseCharacteristics($responseCacheId);

		$cacheItem = $this->getCacheStore($shared)->get(self::RESPONSE_NAME, $responseCharacteristics);
		if ($cacheItem === null) {
			return null;
		}

		if ($now === null) {
			$now = new DateTimeImmutable();
		}

		$data = $cacheItem->getData();
		if (!($data instanceof ResponseCacheItem) || $data->isExpired($now)
				|| ($data->hasVerifier() && !$this->verifying->verifyValidity($responseCacheId, $data, $now))) {
			$this->removeByResponseCharacteristics($responseCharacteristics, $shared);
			return null;
		}

		return $data;
	}

	public function remove(ResponseCacheId $responseCacheId, bool $shared): void {
		$responseCharacteristics = $this->buildResponseCharacteristics($responseCacheId);
		$this->responseCacheActionQueue->registerRemoveAction(false, function()
		use ($responseCharacteristics, $shared) {
			$this->removeByResponseCharacteristics($responseCharacteristics, $shared);
		});
	}

	private function removeByResponseCharacteristics(CharacteristicsList|array $responseCharacteristicsList, $shared): void {
		$responseCharacteristicsList = CharacteristicsList::fromArg($responseCharacteristicsList);
		$indexCharacteristicsList = $this->buildIndexCharacteristicsList($responseCharacteristicsList, array());
		$this->getCacheStore($shared)->remove(self::RESPONSE_NAME, $responseCharacteristicsList);
		$this->getCacheStore($shared)->removeAll(self::INDEX_NAME, $indexCharacteristicsList);
	}

// 	public function removeByFilter(string $method, string $hostName, Path $path, ?array $queryParams,
// 			array $characteristicNeedles) {
// 		$this->cacheStore->removeAll(self::RESPONSE_NAME, $this->buildResponseCharacteristics($method, $hostName, $path, $queryParams, 
// 				$characteristicNeedles));
// 	}

	public function removeByCharacteristics(CharacteristicsList|array $characteristicNeedles, bool $shared): void {
		$cacheItems = $this->getCacheStore($shared)->findAll(self::INDEX_NAME, $this->buildIndexCharacteristicsList(
				new CharacteristicsList(array()), CharacteristicsList::fromArg($characteristicNeedles)));
		$this->responseCacheActionQueue->registerRemoveAction(false, function() use ($cacheItems, $shared) {
			foreach ($cacheItems as $cacheItem) {
				$responseCharacteristics = $cacheItem->getData();
				if (is_array($responseCharacteristics)) {
					$this->getCacheStore($shared)->remove(self::RESPONSE_NAME, new CharacteristicsList($responseCharacteristics));
				}
				$this->getCacheStore($shared)->remove(self::INDEX_NAME, $cacheItem->getCharacteristicsList());
			}
		});
	}

	public function clear(?bool $shared = null): void {
		foreach ($this->getCacheStores($shared) as $cacheStore) {
			$cacheStore->clear();
		}
	}
}
