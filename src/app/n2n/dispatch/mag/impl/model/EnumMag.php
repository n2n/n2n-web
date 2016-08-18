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

use n2n\dispatch\map\val\impl\ValEnum;
use n2n\dispatch\map\PropertyPath;
use n2n\ui\view\impl\html\HtmlView;
use n2n\N2N;
use n2n\dispatch\property\impl\ScalarProperty;
use n2n\l10n\MessageCode;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\ui\UiComponent;
use n2n\dispatch\property\ManagedProperty;
use n2n\l10n\N2nLocale;
use n2n\l10n\Lstr;

class EnumMag extends MagAdapter {
	private $mandatory;
	private $options;
	private $inputAttrs;
	
	public function __construct($propertyName, $label, array $options, $value = null, 
			$mandatory = false, array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($propertyName, $label, $value, $containerAttrs);
		$this->mandatory = (bool) $mandatory;
		$this->setOptions($options);
		$this->inputAttrs = $inputAttrs;
	}	

	public function isMandatory() {
		return $this->mandatory;
	}
	
	public function setMandatory($mandatory) {
		$this->mandatory = (bool) $mandatory;
	}
	
	public function getOptions(): array {
		return $this->options;
	}
		
	public function setOptions(array $options) {
		if (!$this->mandatory && !isset($options[null])) {
			$this->options = array(null => null) + $options;
		} else {
			$this->options = $options;
		}
	}
	
	public function buildOptions(N2nLocale $n2nLocale) {
		$options = array();
		foreach ($this->options as $key => $value) {
			$options[$key] = Lstr::create($value)->t($n2nLocale);
		}
		return $options;
	}
	
	public function getInputAttrs() {
		return $this->inputAttrs;
	}
	
	public function setInputAttrs($inputAttrs) {
		$this->inputAttrs = $inputAttrs;
	}

	public function setFormValue($formValue) {
		if (!strlen($formValue)) {
			$this->value = null;
			return;
		}
		$this->value = $formValue;
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		return $view->getFormHtmlBuilder()->getSelect($propertyPath, $this->buildOptions($view->getN2nLocale()), $this->inputAttrs);
	}

	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}

	public function setupBindingDefinition(BindingDefinition $bd) {
		$bd->val($this->getPropertyName(), new ValEnum(array_keys($this->options),
				new MessageCode(ValEnum::DEFAULT_ERROR_TEXT_CODE, array('field' => $this->labelLstr), 
						N2N::NS)));
	}
}
