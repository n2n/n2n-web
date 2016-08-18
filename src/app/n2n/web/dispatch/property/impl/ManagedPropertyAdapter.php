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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\dispatch\property\impl;

use n2n\web\dispatch\property\ManagedProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\Dispatchable;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\AnalyzerResult;
use n2n\web\dispatch\ui\Form;
use n2n\web\dispatch\map\MappingResult;
use n2n\web\dispatch\map\PropertyPathPart;

abstract class ManagedPropertyAdapter implements ManagedProperty {
	protected $name;
	protected $accessProxy;
	protected $array;
	protected $mapTypeConstraint;
	protected $useArrayObject;
	/**
	 * @param AccessProxy $accessProxy
	 * @param bool $array
	 */
	public function __construct(AccessProxy $accessProxy, $array) {
		$this->name = $accessProxy->getPropertyName();
		$this->accessProxy = $accessProxy;
		$this->array = $array;
		
		if ($this->mapTypeConstraint !== null) return;
		
		if ($this->isArray()) {
			$this->mapTypeConstraint = $this->accessProxy->getConstraint();
		} else {
			$this->mapTypeConstraint = $this->accessProxy->getConstraint()->getLenientCopy();
		}
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::getName()
	 */
	public function getName() {
		return $this->name;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::isArray()
	 */
	public function isArray() {
		return $this->array;			
	}
	/**
	 * @return mixed
	 */
	public function createEmptyValue() {
		if (!$this->isArray()) return null;
		
		if ($this->useArrayObject) {
			return new \ArrayObject();
		}
		
		return array();
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::readValue()
	 */
	public function readValue(Dispatchable $dispatchable) {
		return $this->accessProxy->getValue($dispatchable);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::writeValue()
	 */
	public function writeValue(Dispatchable $dispatchable, $value) {
		$this->accessProxy->setValue($dispatchable, $value);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::convertValueToMapValue()
	 */
	public function writeValueToMappingResult($value, MappingResult $mappingResult, N2nContext $n2nContext) {
		$mapValue = null;
		if ($value === null) {
			$mapValue = $this->createEmptyValue();
		} else if ($value instanceof \ArrayObject) {
			$mapValue = new \ArrayObject($value->getArrayCopy());
		} else {
			$mapValue = $value;
		}
		$mappingResult->__set($this->getName(), $mapValue);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::createMapValueField()
	 */
	public function resolveMapValue(PropertyPathPart $pathPart, MappingResult $mappingResult, 
			N2nContext $n2nContext) {
		if (!$mappingResult->containsPropertyName($this->name)) {
			$this->writeValueToMappingResult($this->readValue($mappingResult->getObject()), 
					$mappingResult, $n2nContext);
		}
		
		if (!$this->isArray() || !$pathPart->isArray()) return;
		
		$key = $pathPart->getResolvedArrayKey();
		$mapValue = $mappingResult->__get($this->name);
		if (!array_key_exists($key, $mapValue)) {
			$mapValue[$key] = null;
		}
		$mappingResult->__set($this->name, $mapValue);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::getMapTypeConstraint()
	 */
	public function getMapTypeConstraint() {
		return $this->mapTypeConstraint;	
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::convertMapValueToValue()
	 */
	public function readValueFromMappingResult(MappingResult $mappingResult, N2nContext $n2nContext) {
		return $mappingResult->__get($this->getName());
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::prepareForm()
	 */
	public function prepareForm(Form $form, AnalyzerResult $analyzerResult = null) {
	}
}
