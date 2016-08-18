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

use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\map\PropertyPath;
use n2n\dispatch\map\val\impl\ValNumeric;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\property\impl\ScalarProperty;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\map\val\impl\ValNotEmpty;
use n2n\dispatch\property\ManagedProperty;
use n2n\ui\UiComponent;

class NumericMag extends MagAdapter {
	
	private $mandatory;
	private $minValue;
	private $maxValue;
	private $decimalPlaces;
	private $inputAttrs;
	
	public function __construct($propertyName, $label, $value = null, $mandatory = false, 
			$minValue = null, $maxValue = null, $decimalPlaces = 0, array $containerAttrs = null, array $inputAttrs = null) {
		parent::__construct($propertyName, $label, $value, $containerAttrs);
		$this->mandatory = (bool) $mandatory;
		$this->value = $value;
		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
		$this->decimalPlaces = (int) $decimalPlaces;
		$this->inputAttrs = $inputAttrs;
	}	
	
	public function isMandatory() {
		return $this->mandatory;
	}
	
	public function setMandatory($mandatory) {
		$this->mandatory = (bool) $mandatory;
	}
	
	public function setMinValue($minValue) {
		$this->minValue = $minValue;
	}
	
	public function getMinValue() {
		return $this->minValue;
	}
	
	public function setMaxValue($maxValue) {
		$this->maxValue = $maxValue;
	}
	
	public function getMaxValue() {
		return $this->maxValue;
	}
	
	public function setDecimalPlaces($decimalPlace) {
		$this->decimalPlaces = $decimalPlace;
	}
	
	public function getDecimalPlaces() {
		return $this->decimalPlaces;
	}
		
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		return $view->getFormHtmlBuilder()->getInput($propertyPath, 
				array('min' => $this->minValue, 'max' => $this->maxValue), ($this->decimalPlaces > 0 ? null : 'number'));
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}

	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->getPropertyName(), new ValNotEmpty());
		}
		$bd->val($this->getPropertyName(), new ValNumeric(null, $this->minValue, null, 
				$this->maxValue, null, $this->decimalPlaces));
	}
}
