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

use n2n\dispatch\map\val\impl\ValNotEmpty;
use n2n\dispatch\map\PropertyPath;
use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\map\val\impl\ValMaxLength;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\property\impl\ScalarProperty;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\property\ManagedProperty;
use n2n\ui\UiComponent;
use n2n\dispatch\map\bind\MappingDefinition;

class StringMag extends MagAdapter {
	private $mandatory;
	private $maxlength;
	private $multiline;
	private $inputAttrs;
	
	public function __construct(string $propertyName, $label, $value = null, bool $mandatory = false, 
			int $maxlength = null, bool $multiline = false, array $containerAttrs = null, array $inputAttrs = null) {
		parent::__construct($propertyName, $label, $value, $containerAttrs);
		$this->mandatory = $mandatory;
		$this->maxlength = $maxlength;
		$this->multiline = $multiline;
		$this->inputAttrs = $inputAttrs;
	}	
	
	public function setMandatory(bool $mandatory) {
		$this->mandatory = $mandatory;
	} 
	
	public function isMandatory(): bool {
		return $this->mandatory;
	}
	
	public function setMaxlength(int $maxlength = null) {
		$this->maxlength = $maxlength;
	}
	
	public function getMaxlength() {
		return $this->maxlength;
	}
	
	public function setMultiline(bool $multiline) {
		$this->multiline = $multiline;
	}
	
	public function isMultiline() {
		return $this->multiline;
	}
	
	public function setInputAttrs(array $inputAttrs) {
		$this->inputAttrs = $inputAttrs;
	}
	
	public function getInputAttrs(): array {
		return $this->inputAttrs;
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
	}

	public function createUiField(PropertyPath $propertyPath, HtmlView $htmlView): UiComponent {
		if ($this->isMultiline()) {
			return $htmlView->getFormHtmlBuilder()->getTextarea($propertyPath, $this->inputAttrs);
		}
		return $htmlView->getFormHtmlBuilder()->getInput($propertyPath, $this->inputAttrs);
	}
}
