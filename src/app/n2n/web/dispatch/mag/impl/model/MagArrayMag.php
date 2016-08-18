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
namespace n2n\dispatch\mag\impl\model;

use n2n\dispatch\map\BindingConstraints;
use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\map\PropertyPath;
use n2n\dispatch\DispatchableTypeAnalyzer;
use n2n\dispatch\map\val\impl\ValArraySize;
use n2n\dispatch\mag\Mag;
use n2n\util\ex\IllegalStateException;
use n2n\dispatch\ManagedPropertyType;
use n2n\ui\view\impl\html\HtmlUtils;
use n2n\ui\UiComponent;

class MagArrayMag extends MagAdapter {
	const DEFAULT_INC = 10;
	private $fieldOption;
	private $min;
	private $max;
	private $dynamicArray = true;
	private $fieldCreateor = null;
	private $objectOption;
	
	public function __construct($label, Option $fieldOption, $required = false, array $containerAttrs = null) {
		parent::__construct($label, array(), $required);
		$this->fieldOption = $fieldOption;
		if ($this->isRequired()) {
			$this->min = 1;
		}
		
		$this->setContainerAttrs(
				HtmlUtils::mergeAttrs(array('class' => 'n2n-array-option'),
						(array) $containerAttrs));
	}
	
	public function setDynamicArray($dynamicArray) {
		$this->dynamicArray = $dynamicArray;
	}
	
	public function isDynamicArray() {
		return $this->dynamicArray;
	}
	
	public function getMin() {
		return $this->min;
	}
	
	public function setMin($min) {
		$this->min = $min;
	}
	
	public function getMax() {
		return $this->max;
	}
	
	public function setMax($max) {
		$this->max = $max;
	}
	
	public function setFieldCreator(\Closure $fieldCreator = null) {
		$this->fieldCreateor = $fieldCreator;
	}
	
	public function createManagedProperty($propertyName, DispatchableTypeAnalyzer $typeAnalyzer) {
		$propertyType = $this->fieldOption->createManagedPropertyType($propertyName, $typeAnalyzer);
		
		if ($propertyType->isArray()) {
			throw new IllegalStateException('FieldOption must not be an array');
		}
		$propertyType->setArray(true);
		
		$this->objectOption = $propertyType->getType() == ManagedPropertyType::TYPE_OBJECT;
		if ($this->objectOption && $this->dynamicArray) {
			if ($this->fieldCreateor !== null) {
				$that = $this;
				$propertyType->setAttr(ManagedPropertyType::ATTR_CREATOR, function () use ($that) {
					return $that->fieldOption->attributeValueToOptionValue($that->fieldCreateor->__invoke());
				});
			} else {
				$that = $this;
				$propertyType->setAttr(ManagedPropertyType::ATTR_CREATOR, function() use ($that) {
					return $that->fieldOption->getDefault();  
				});
			}
		}
		
		return $propertyType;
	}
	
	public function optionValueToAttributeValue($value) {
		$attributeValue = array();
		foreach ((array) $value as $key => $MagForm) {
			$attributeValue[$key] = $this->fieldOption->optionValueToAttributeValue($MagForm); 
		}
		return $attributeValue;
	}
	
	public function attributeValueToOptionValue($value) {
		$optionValue = array();
		foreach ((array) $value as $key => $field) {
			$optionValue[$key] = $this->fieldOption->attributeValueToOptionValue($field);
		}
		return $optionValue;
	}
	
	public function setupBindingDefinition($propertyName, BindingConstraints $bc) {
		$bc->val($propertyName, new ValArraySize($this->min, null, $this->max));
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		$num = null;
		$optionalObjects = $this->objectOption && $this->dynamicArray;
		$numExisting = sizeof($view->getFormHtmlBuilder()->getValue($propertyPath));
		if ($this->dynamicArray) {
			$num = $numExisting;
			if (isset($this->max)) {
				if ($this->max > $num) {
					$num = $this->max; 
				}
			} else if (isset($this->min) && $this->min > $num) {
				$num = $this->min + self::DEFAULT_INC;
			} else {
				$num += self::DEFAULT_INC;
			}
		}
		
		return $view->getImport('\n2n\view\option\arrayOption.html',
				array('propertyPath' => $propertyPath, 'num' => $num, 'optionalObjects' => $optionalObjects, 'dynamicArray' => $this->dynamicArray,
						'fieldOption' => $this->fieldOption, 'numExisting' => $numExisting, 'min' => $this->min));
	}
}
