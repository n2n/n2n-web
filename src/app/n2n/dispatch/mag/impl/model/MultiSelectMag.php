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

use n2n\dispatch\map\val\impl\ValIsset;
use n2n\dispatch\map\PropertyPath;
use n2n\ui\view\impl\html\HtmlView;
use n2n\ui\view\impl\html\HtmlElement;
use n2n\dispatch\map\val\impl\ValEnum;
use n2n\dispatch\map\val\impl\ValArraySize;
use n2n\dispatch\property\impl\ScalarProperty;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\property\ManagedProperty;
use n2n\ui\UiComponent;

class MultiSelectMag extends MagAdapter {
	private $choicesMap;
	private $min;
	private $max;
	private $inputAttrs;
	
	public function __construct($propertyName, $label, array $choicesMap, array $default = null, $min = 0, $max = null, 
			array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($propertyName, $label, (array) $default, $containerAttrs);
		$this->choicesMap = $choicesMap;
		$this->min = $min;
		$this->max = $max;
		$this->inputAttrs = $inputAttrs;
	}	
	

	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, true);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->min > 0) {
			$bd->val($this->getPropertyName(), new ValIsset());
		}
		
		$bd->val($this->getPropertyName(), new ValArraySize($this->min, null, $this->max, null));
		
		$bd->val($this->getPropertyName(), new ValEnum(array_keys($this->choicesMap)));
	}
	
	public function setChoicesMap($choicesMap) {
		$this->choicesMap = $choicesMap;
	}
	
	public function getChoicesMap() {
		return $this->choicesMap;
	}

	public function createUiField(PropertyPath $propertyPath, HtmlView $htmlView): UiComponent {
		$ul = new HtmlElement('ul', array('class' => 'n2n-multiselect-option'));
		
		$formHtml = $htmlView->getFormHtmlBuilder();
		foreach ($this->choicesMap as $key => $label) {
			$ul->appendContent(new HtmlElement('li', null, 
					$formHtml->getInputCheckbox($propertyPath->fieldExt($key), $key, null, $label)));
		}
		
		return $ul;
	}

	public function attributeValueToOptionValue($value) {
		return array_combine($value, $value);
	}
}
