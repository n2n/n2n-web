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

use n2n\web\dispatch\property\SimpleProperty;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\CorruptedDispatchException;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\target\build\ParamInvestigator;
use n2n\web\dispatch\target\ObjectItem;
use n2n\web\dispatch\target\DispatchTargetException;

class ScalarProperty extends ManagedPropertyAdapter implements SimpleProperty {
	public function __construct($accessProxy, $arrayLike, $useArrayObject = null) {
		CommonManagedPropertyProvider::restrictConstraints($accessProxy, 'scalar', $arrayLike, $useArrayObject);
		parent::__construct($accessProxy, $arrayLike);
		$this->useArrayObject = $useArrayObject;		
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\SimpleProperty::convertMapValueToScalar()
	 */
	public function convertMapValueToScalar($mapValue, N2nContext $n2nContext) {
		return $mapValue;
	}

	public function dispatch(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {
		
		$submitted = $objectItem->containsItemPropertyName($this->getName());
				
		$propertyPath = null;
		try {
			if ($this->isArray()) {
				$propertyPath = $objectItem->createArrayItem($this->getName())->getPropertyPath();
			} else {
				$propertyPath = $objectItem->createPropertyItem($this->getName())->getPropertyPath();
			}
		} catch (DispatchTargetException $e) {
			throw new CorruptedDispatchException('', 0, $e);
		}
		
		$rawValue = $paramInvestigator->findValue($propertyPath);
		if ($rawValue === null && !$submitted) {
			$rawValue = $this->readValue($bindingDefinition->getMappingResult()->getObject());
			if ($rawValue === null) $rawValue = $this->createEmptyValue();
			
			$bindingDefinition->getMappingResult()->__set($this->getName(), $rawValue);
			return;
		}
		
		if (!$this->isArray()) {
			$mapValue = null;
			if ($rawValue !== null && strlen($rawValue) > 0) {
				CorruptedDispatchException::assertTrue(is_scalar($rawValue));
				$mapValue = $rawValue;
			}
			$bindingDefinition->getMappingResult()->__set($this->getName(), $mapValue);
			return;
		}
		
		if ($rawValue === null) $rawValue = array();
		
		CorruptedDispatchException::assertTrue(is_array($rawValue));
		$mapValue = $this->createEmptyValue();
		foreach ($rawValue as $key => $rawFieldValue) {
			CorruptedDispatchException::assertTrue(is_scalar($rawFieldValue));
			if (strlen($rawFieldValue) > 0) {
				$mapValue[$key] = $rawFieldValue;
			}
		}
		$bindingDefinition->getMappingResult()->__set($this->getName(), $mapValue);
	}
	
	private function dispatchNonArray() {
		
	}
	
	private function dispatchArrayObject() {
		
	}
	
	
	
}
