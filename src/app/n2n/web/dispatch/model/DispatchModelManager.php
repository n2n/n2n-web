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
namespace n2n\web\dispatch\model;

use n2n\util\cache\CacheStore;
use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\DynamicDispatchable;

class DispatchModelManager {
	private $cacheStore;
	private $dispatchModelFactory;
	
	private $dynamicDispatchModels = array();
	private $dispatchModels = array();
	
	
	public function __construct(DispatchModelFactory $dispatchModelFactory) {
		$this->dispatchModelFactory = $dispatchModelFactory;
	}
	
	public function setCacheStore(CacheStore $cacheStore = null) {
		$this->cacheStore = $cacheStore;
	}
	
	public function getCacheStore() {
		return $this->cacheStore;
	}
	/**
	 * @param Dispatchable $dispatchable
	 * @return DispatchModel
	 */
	public function getDispatchModel(Dispatchable $dispatchable) {
		if ($dispatchable instanceof DynamicDispatchable) {
			$objHash = spl_object_hash($dispatchable);
			if (isset($this->dynamicDispatchModels[$objHash])) {
				return $this->dynamicDispatchModels[$objHash];
			}	
			
			$dispatchModel = new DispatchModel(new \ReflectionClass($dispatchable));
			$dispatchable->setup($dispatchModel);
			return $this->dynamicDispatchModels[$objHash] = $dispatchModel;
		}
		
		$class = new \ReflectionClass($dispatchable);
		if (!isset($this->dispatchModels[$class->getName()])) {
			$this->dispatchModels[$class->getName()] 
					= $this->dispatchModelFactory->create($class);
		}
		return $this->dispatchModels[$class->getName()];
	}
	
	
}
