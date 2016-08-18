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

use n2n\web\ui\view\impl\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\reflection\ArgUtils;
use n2n\web\dispatch\map\val\impl\ValArraySize;
use n2n\web\ui\view\impl\html\HtmlUtils;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\property\impl\ObjectProperty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\MagDispatchable;

class MagCollectionArrayMag extends MagAdapter {
	const DEFAULT_INC = 10;
	
	private $creator;
	private $min;
	private $max;
	
	public function __construct($propertyName, $label, \Closure $creator, 
			$mandatory = false, array $containerAttrs = null) {
		parent::__construct($propertyName, $label, array(), 
				HtmlUtils::mergeAttrs(array('class' => 'n2n-array-option'), 
						(array) $containerAttrs));
		$this->creator = $creator;

		if ($mandatory) {
			$this->min = 1;
		}
	}
	
	public function getNum() {
		return $this->num;
	}
	
	public function setNum($num) {
		$this->num = $num;
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
	
	public function getCreator() {
		return $this->creator;
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		$property = new ObjectProperty($accessProxy, true);
		$property->setCreator($this->creator);
		return $property;
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		$bd->val($this->propertyName, new ValArraySize($this->min, null, $this->max));		
	}
	
	public function setValue($value) {
		ArgUtils::valArray($value, 'array');
		$this->value = $value;
	}
	
	public function setFormValue($value) {
		$this->value = array();
		foreach ((array) $value as $magDispatchable) {
			$this->value[] = $magDispatchable->getMagCollection()->readValues(); 
		}
	}
	
	public function getFormValue() {
		$magDispatchables = array();
		foreach ($this->value as $fieldValues) {
			$magDispatchable = $this->createFieldMagDispatchable();
			$magDispatchable->getMagCollection()->writeValues($fieldValues);
			$magDispatchables[] = $magDispatchable;
		}
		return $magDispatchables;
	}
	
	private function createFieldMagDispatchable(): MagDispatchable {
		$magDispatchable = $this->creator->__invoke();
		ArgUtils::valTypeReturn($magDispatchable, MagDispatchable::class, null, $this->creator);
		return $magDispatchable;
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		$numExisting = sizeof($view->getFormHtmlBuilder()->meta()->getMapValue($propertyPath));
		$num = $numExisting;
		if (isset($this->max) && $this->max > $num) {
			$num = $this->max;
		} else {
			$num += self::DEFAULT_INC;
		}
		$this->setContainerAttrs(
				HtmlUtils::mergeAttrs(array('data-num' => $num), $this->getContainerAttrs($view)));
					
		return $view->getImport('\n2n\web\dispatch\mag\impl\view\magCollectionArrayOption.html',
				array('propertyPath' => $propertyPath, 'numExisting' => $numExisting, 'num' => $num, 
						'min' => $this->min));
	}
}
