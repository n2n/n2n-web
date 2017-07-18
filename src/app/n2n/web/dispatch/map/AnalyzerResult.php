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
namespace n2n\web\dispatch\map;

use n2n\web\dispatch\property\ManagedProperty;
use n2n\util\col\ArrayUtils;

class AnalyzerResult {
	private $mappingResult;
	private $managedProperty;
	private $lastPathPart;
	private $mapValue;

	public function __construct(MappingResult $mappingResult, ManagedProperty $managedProperty, PropertyPath $propertyPath) {
		$this->mappingResult = $mappingResult;
		$this->managedProperty = $managedProperty;
		$this->lastPathPart = $propertyPath->getLast();
		$this->mapValue = $mappingResult->__get($managedProperty->getName());
	}

	public function getMappingResult() {
		return $this->mappingResult;
	}

	public function getManagedProperty() {
		return $this->managedProperty;
	}

	public function getLastPathPart() {
		return $this->lastPathPart;
	}

	public function getPathParts() {
		return $this->pathParts;
	}
	
	public function getMapValue() {
		if (!$this->lastPathPart->isArray()) return $this->mapValue;
		
		$key = $this->lastPathPart->getResolvedArrayKey();
		if (!ArrayUtils::isArrayLike($this->mapValue) || !array_key_exists($key, $this->mapValue)) {
			throw new CorruptedMappingResultException();
		}
		
		return $this->mapValue[$key];	
	}
	
	public function hasInvalidRawValue() {
		return $this->mappingResult->getBindingErrors()->hasInvalidRawValue($this->lastPathPart);
	}
	
	public function getInvalidRawValue() {
		return $this->mappingResult->getBindingErrors()->getInvalidRawValue($this->lastPathPart);
	}
	
	public function testMapValue($value) {
		if (!$this->lastPathPart->isArray() || null !== $this->lastPathPart->getArrayKey()) {
			return $value == $this->getMapValue(); 
		}
		
// 		if (!ArrayUtils::isArrayLike($this->mapValue)) {
// 			throw new CorruptedMappingResultException();
// 		}
		
		// @todo propblems with no objects which are no arrayobject?
		return in_array($value, (array) $this->mapValue);
	}

	public function getLabel(): string {
		return $this->mappingResult->getLabel($this->lastPathPart);
	}
}
