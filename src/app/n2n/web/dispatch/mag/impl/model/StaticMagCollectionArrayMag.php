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
namespace n2n\web\dispatch\mag\impl\model;

use n2n\util\config\Attributes;
use n2n\web\dispatch\map\BindingConstraints;
use n2n\web\ui\view\impl\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\ManagedPropertyType;
use n2n\web\dispatch\DispatchableTypeAnalyzer;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\ui\UiComponent;

class StaticMagCollectionArrayMag extends MagAdapter {
	private $fields = array();
	
	public function __construct($propertyName, $label) {
		parent::__construct($propertyName, $label, array(), true);
	}
	
	public function createManagedProperty($propertyName, DispatchableTypeAnalyzer $typeAnalyzer) {
		$propertyType = new ManagedPropertyType($typeAnalyzer, $propertyName);
		$propertyType->setType(ManagedPropertyType::TYPE_OBJECT);
		$propertyType->setArray(true);
		return $propertyType;
	}
	
	public function setField($key, MagDispatchable $MagForm) {
		$this->fields[$key] = $MagForm;
	}
	
	public function optionValueToAttributeValue($value) {
		$attrs = array();
		foreach ((array) $value as $key => $MagForm) {
			$attrs[$key] = $MagForm->getAttributes()->toArray(); 
		}
		return $attrs;
	}
	
	public function attributeValueToOptionValue($value) {
		foreach ((array) $value as $key => $attrs) {
			if (isset($this->fields[$key])) {
				$this->fields[$key]->setAttributes(new Attributes($attrs));
			}
		}
		return $this->fields;
	}
	
	public function setupBindingDefinition(BindingConstraints $bindingConstraints) {
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		return $view->getImport('\n2n\view\option\staticMagCollectionArrayMag.html',
				array('propertyPath' => $propertyPath));
	}
}
