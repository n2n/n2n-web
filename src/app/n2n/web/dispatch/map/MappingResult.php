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

use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\map\bind\BindingErrors;
use n2n\web\dispatch\model\DispatchModel;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\core\container\N2nContext;
use n2n\util\col\ArrayUtils;
use n2n\util\type\ArgUtils;
use n2n\util\type\TypeUtils;

class MappingResult {
	private $dispatchable;
	private $dispatchModel;
	private $values = array();
	private $labels = array();
	private $bindingErrors;
	private $attrs;
	/**
	 * @param Dispatchable $dispatchable
	 * @param DispatchModel $dispatchModel
	 */
	public function __construct(Dispatchable $dispatchable, DispatchModel $dispatchModel, $attrs = null) {
		$this->dispatchable = $dispatchable;
		$this->dispatchModel = $dispatchModel;
		$this->bindingErrors = new BindingErrors();
		$this->attrs = $attrs;
	}
	/**
	 * @return mixed
	 */
	public function getAttrs() {
		return $this->attrs;
	}
	/**
	 * @param mixed $attrs
	 */
	public function setAttrs($attrs) {
		$this->attrs = $attrs;
	}
	/**
	 * @return DispatchModel
	 */
	public function getDispatchModel() {
		return $this->dispatchModel;
	}
	/**
	 * @return Dispatchable
	 */
	public function getObject() {
		return $this->dispatchable;
	}
	/**
	 * @return array
	 */
	public function getPropertyNames() {
		return array_keys($this->values);
	}
	/**
	 * @return array
	 */
	public function getPropertyValues() {
		return $this->values;
	}
	
	public function unsetPropertyValues() {
		$this->values = array();
	}
	/**
	 * @param string $propertyName
	 * @return bool
	 */
	public function containsPropertyName($propertyName) {
		return array_key_exists($propertyName, $this->values);
	}
	
	public function loadProperty($propertyName, N2nContext $n2nContext) {
		$managedProperty = $this->dispatchModel->getPropertyByName($propertyName);
		
		$managedProperty->writeValueToMappingResult(
				$managedProperty->readValue($this->getObject()), $this, $n2nContext);
		
// 		if (!$this->containsPropertyName($propertyName)) {
// 			throw new DispatchErrorException(get_class($managedProperty) 
// 					. '::writeValueToMappingResultValue() is supposed to fill MappingResult property ' 
// 					. $propertyName);
// 		}
		
		return $this->__get($propertyName);
	}
	
	/**
	 * 
	 * @param string $propertyName
	 * @throws PropertyUnknownToMappingResultException
	 */
	public function __get(string $propertyName) {
		if (!$this->containsPropertyName($propertyName)) {
			$this->dispatchModel->getPropertyByName($propertyName);
			
			throw new PropertyUnknownToMappingResultException('MappingResult of ' . get_class($this->dispatchable) 
					. ' contains no value for property: ' . $propertyName);
		}
				
		return $this->values[$propertyName];
	}
	
	public function __set($propertyName, $mapValue) {
		$managedProperty = $this->dispatchModel->getPropertyByName($propertyName);
		
		if ($managedProperty->isArray() && !ArrayUtils::isArrayLike($mapValue)) {
			throw new \InvalidArgumentException('Property \'' . $managedProperty->getName() . '\''
					. ' of MappingResult for ' . get_class($this->getObject())
					. ' is supposed to be an array.');
		}		
		
		try {
			$managedProperty->getMapTypeConstraint()->validate($mapValue);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new \InvalidArgumentException('Invalid value for property \'' . $propertyName 
					. '\' of MappingResult for ' . get_class($this->dispatchable), 0, $e);
		}
		
		$this->values[$propertyName] = $mapValue;
	}
	
	public function __unset($propertyName) {
		unset($this->values[$propertyName]);
	}
	
	public function setLabel($pathPartExpression, $label) {
		$this->labels[(string) PropertyPathPart::createFromExpression($pathPartExpression)]
				= $label;
	}
	
	public function setLabels(array $labels) {
		ArgUtils::valArray($labels, 'string');
		foreach ($labels as $pathPartExpression => $label) {
			$this->setLabel($pathPartExpression, $label);
		}
	}
	
	public function getLabel($pathPartExpression) {
		$pathPart = PropertyPathPart::createFromExpression($pathPartExpression);
		$pathPartStr = (string) $pathPart;
		if (isset($this->labels[$pathPartStr])) {
			return $this->labels[$pathPartStr];
		}
		
		if ($pathPart->isArray() && $pathPart->isArrayKeyResolved()) {
			$pathPartStr = (string) $pathPart->copyWithArrayKey(null);
			if (isset($this->labels[$pathPartStr])) {
				return $this->labels[$pathPartStr];
			}
		}
		
		return TypeUtils::prettyName($pathPartExpression);
	}
	/**
	 * 
	 * @return BindingErrors
	 */
	public function getBindingErrors() {
		return $this->bindingErrors;
	}
	/**
	 * @param PropertyPathPart $pathPart
	 * @param string $recursive
	 * @return boolean
	 */
	public function testErrors(PropertyPathPart $pathPart = null, $recursive = true) {
		if ($pathPart === null) {
			if (!$this->bindingErrors->isEmpty()) return true;
			
			if ($recursive) {
				foreach ($this->findPropertyMappingResults() as $propertyMappingResult) {
					if ($propertyMappingResult->testErrors()) return true;
				}
			}
			
			return false;
		}
		
		if ($this->bindingErrors->hasErrors($pathPart) || (!$pathPart->isArray() && $recursive 
				&& $this->bindingErrors->hasPropertyErrors($pathPart->getPropertyName()))) return true;
		
		if ($recursive && null !== ($propertyMappingResult = $this->findPropertyMappingResult($pathPart))) {
			return $propertyMappingResult->testErrors();
		}
		
		return false;
	}
	/**
	 * @param PropertyPathPart $pathPart
	 * @param string $recursive
	 * @return \n2n\l10n\Message[]
	 */
	public function filterErrorMessages(PropertyPathPart $pathPart = null, $recursive = true) {
		if ($pathPart === null) {
			$errorMessages = $this->bindingErrors->getAllErrors();
			
			if ($recursive) {
				foreach ($this->findPropertyMappingResults() as $propertyMappingResult) {
					$errorMessages = array_merge($errorMessages, $propertyMappingResult->filterErrorMessages()); 
				}
			}
				
			return $errorMessages;
		}
		
		$errorMessages = null;
		if ($pathPart->isArray() || !$recursive) {
			$errorMessages = $this->bindingErrors->getErrors($pathPart);
		} else {
			$errorMessages = $this->bindingErrors->getPropertyErrors($pathPart->getPropertyName());
		}
		
		if ($recursive && null != ($mappingResult = $this->findPropertyMappingResult($pathPart))) {
			$errorMessages = array_merge($errorMessages, $mappingResult->filterErrorMessages());
		}
		
		return $errorMessages;
	}
	
	private function findPropertyMappingResult(PropertyPathPart $pathPart) {
		$propertyName = $pathPart->getPropertyName();
		if (!isset($this->values[$propertyName])) return null;
		
		$value = $this->values[$propertyName];
		if (!$pathPart->isArray()) { 
			if ($value instanceof MappingResult) return $value;
			return null;
		}
		
		$arrayKey = $pathPart->getArrayKey();
		if (is_array($value) && isset($value[$arrayKey]) 
				&& $value[$arrayKey] instanceof MappingResult) {
			return $value[$arrayKey];
		}
		
		return null;
	}
	
	private function findPropertyMappingResults() {
		$mappingResults = array();
		foreach ($this->dispatchModel->getProperties() as $propertyName => $managedProperty) {
			if (!isset($this->values[$propertyName])) continue;
			
			if (!$managedProperty->isArray()) {
				if ($this->values[$propertyName] instanceof MappingResult) {
					$mappingResults[] = $this->values[$propertyName];
				}
				
				continue;
			}

			foreach ($this->values[$propertyName] as $value) {
				if ($value instanceof MappingResult) {
					$mappingResults[] = $value;
				}
			}
		}
		return $mappingResults;
	}
}
