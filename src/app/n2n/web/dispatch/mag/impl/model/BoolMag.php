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

use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\view\impl\html\HtmlView;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\property\impl\ScalarProperty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;

class BoolMag extends MagAdapter {
	private $inputAttrs;
	
	public function __construct($propertyName, $labelLstr, bool $value = false, 
			array $containerAttrs = null, array $inputAttrs = null) {
		parent::__construct($propertyName, $labelLstr, $value, $containerAttrs);
		$this->inputAttrs = $inputAttrs;
	}	
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
	}

	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		return $view->getFormHtmlBuilder()->getInputCheckbox($propertyPath, true, $this->inputAttrs);
	}
	
	public function setFormValue($formValue) {
		$this->value = (bool) $formValue;
	}
	
	public function setValue($value) {
		$this->value = (bool) $value;
	}
	
	public function optionValueToAttributeValue($value) {
		return (boolean) $value;
	}
}
