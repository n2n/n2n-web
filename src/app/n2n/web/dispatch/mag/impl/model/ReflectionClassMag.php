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

use n2n\web\dispatch\map\val\impl\ValNotEmpty;
use n2n\web\dispatch\map\val\impl\ValMaxLength;
use n2n\web\ui\view\impl\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\val\impl\ValReflectionClass;
use n2n\reflection\ReflectionUtils;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\property\impl\ScalarProperty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;

/**
 * It is !!!VERY DANGEROUS!!! to use this Option 
 * Please use this only for ModuleConfiguration!!!
 * 
 * @author thomas
 *
 */
class ReflectionClassMag extends MagAdapter {
	
	private $mandatory;
	private $maxlength;
	private $inputAttrs;
	private $isAClass;
		
	public function __construct($propertyName, $label, \ReflectionClass $isAClass, $value = null, 
			$mandatory = false, $maxlength = null, array $inputAttrs = null) {
		parent::__construct($propertyName, $label, $value);
		
		$this->mandatory = $mandatory;
		$this->maxlength = $maxlength;
		$this->inputAttrs = $inputAttrs;
		$this->isAClass = $isAClass;
	}
	
	public function setMaxlength($maxlength) {
		$this->maxlength = $maxlength;
	}
	
	public function getMaxlength() {
		return $this->maxlength;
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValNotEmpty());
		}

		if ($this->maxlength !== null) {
			$bd->val($this->propertyName, new ValMaxLength((int) $this->maxlength));
		}
		$bd->val($this->propertyName, new ValReflectionClass($this->isAClass));
	}
	
	public function optionValueToAttributeValue($value) {
		if ($value) {
			return ReflectionUtils::createReflectionClass($value)->getName();
		}
		
		return null;
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $htmlView): UiComponent {
		return $htmlView->getFormHtmlBuilder()->getInput($propertyPath, $this->inputAttrs);
	}
}
