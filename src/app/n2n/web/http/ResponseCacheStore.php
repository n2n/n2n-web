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
use n2n\core\container\AppCache;
use n2n\core\container\TransactionManager;
use n2n\core\container\TransactionalResource;
use n2n\core\container\Transaction;

class ResponseCacheStore implements ThreadScoped {
	const RESPONSE_NAME = 'r';
	const INDEX_NAME = 'i';
	
	private $cacheStore;
	private $responseCacheActionQueue;
	
	private function _init(AppCache $appCache, TransactionManager $transactionManager) {
		$this->cacheStore = $appCache->lookupCacheStore(ResponseCacheStore::class);
		$this->responseCacheActionQueue = new ResponseCacheActionQueue();
		$transactionManager->registerResource($this->responseCacheActionQueue);
	}
	
	private function buildResponseCharacteristics(int $method, string $subsystemName = null, Path $path, 
			array $queryParams = null) {
		if ($queryParams !== null) {
			ksort($queryParams);
		}
		return array('method' => $method, 'subsystemName' => $subsystemName, 'path' => $path->__toString(), 
				'query' => $queryParams);
	}
	
	private function buildIndexCharacteristics(array $responseCharacteristics, array $customCharacteristics) {
		foreach ($customCharacteristics as $key => $value) {
			$responseCharacteristics['cust' . $key] = $value;
		}
		return $responseCharacteristics;
	}
	
	public function store(int $method, string $subsystemName = null, Path $path, array $queryParams = null, 
			array $characteristics, ResponseCacheItem $item) {
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $subsystemName, $path, $queryParams);
		$this->cacheStore->store(self::RESPONSE_NAME, $responseCharacteristics, $item);
		$this->cacheStore->store(self::INDEX_NAME, 
				$this->buildIndexCharacteristics($responseCharacteristics, $characteristics), 
				$responseCharacteristics);
	}
	
	public function get(int $method, string $subsystemName = null, Path $path, array $queryParams = null, 
			\DateTime $now = null) {
		$cacheItem = $this->cacheStore->get(self::RESPONSE_NAME, 
				$this->buildResponseCharacteristics($method, $subsystemName, $path, $queryParams));
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
	
	public function remove(int $method, string $subsystemName = null, Path $path, array $queryParams = null) {
		if ($this->responseCacheActionQueue->isSealed()) return;
		
		$responseCharacteristics = $this->buildResponseCharacteristics($method, $subsystemName, $path, $queryParams);
		$indexCharacteristics = $this->buildIndexCharacteristics($responseCharacteristics, array());
		
		$that = $this;
		$this->responseCacheActionQueue->onCommit(false, function () 
				use ($that, $responseCharacteristics, $indexCharacteristics){
			$that->cacheStore->remove(self::RESPONSE_NAME, $responseCharacteristics);
			$that->cacheStore->removeAll(self::INDEX_NAME, $indexCharacteristics);
		});
	}
	
// 	public function removeByFilter(string $method, string $subsystemName = null, Path $path, array $queryParams = null,
// 			array $characteristicNeedles) {
// 		$this->cacheStore->removeAll(self::RESPONSE_NAME, $this->buildResponseCharacteristics($method, $subsystemName, $path, $queryParams, 
// 				$characteristicNeedles));
// 	}
	
	public function removeByCharacteristics(array $characteristicNeedles) {
		if ($this->responseCacheActionQueue->isSealed()) return;
		
		$cacheItems = $this->cacheStore->findAll(self::INDEX_NAME, $this->buildIndexCharacteristics(
				array(), $characteristicNeedles));
		$that = $this;
		$this->responseCacheActionQueue->onCommit(false, function () use ($that, $cacheItems) {
			foreach ($cacheItems as $cacheItem) {
				$responseCharacteristics = $cacheItem->getData();
				if (is_array($responseCharacteristics)) {
					$that->cacheStore->remove(self::RESPONSE_NAME, $responseCharacteristics);
				}
				$that->cacheStore->remove(self::INDEX_NAME, $cacheItem->getCharacteristics());
			}
		});
	}
	
	public function clear() {
		if ($this->responseCacheActionQueue->isSealed()) return;
		
		$this->cacheStore->clear();
	}
}

class ResponseCacheActionQueue implements TransactionalResource {
	private $inTransaction = false;
	private $sealed = false;
	private $onCommitClosures = array();
		
	private function reset() {
		$this->inTransaction = false;
		$this->sealed = false;
		$this->onCommitClosures = array();
	}
	
	public function isSealed() {
		return $this->sealed;
	}
	
	public function onCommit(bool $master, \Closure $closure) {
		if (!$this->inTransaction) {
			$closure();
			return;
		}
		
		if ($this->sealed) return;
		
		if ($master) {
			$this->onCommitClosures = array();
			$this->sealed = true;
		}
		
		$this->onCommitClosures[] = $closure;
	}
	
	public function beginTransaction(Transaction $transaction) {
		$this->inTransaction = true;		
	}
	
	public function prepareCommit(Transaction $transaction): bool {
		return true;
	}
	
	public function commit(Transaction $transaction) {
		foreach ($this->onCommitClosures as $onCommitClosure) {
			$onCommitClosure();
		}
		
		$this->reset();
	}
	
	/**
	 * @param Transaction $transaction
	 */
	public function rollBack(Transaction $transaction) {
		$this->reset();
	}
}
