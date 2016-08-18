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
use n2n\ui\view\impl\html\HtmlElement;
use n2n\dispatch\map\PropertyPath;
use n2n\ui\view\impl\html\HtmlView;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\property\impl\ScalarProperty;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\ui\view\impl\html\HtmlBuilderMeta;
use n2n\ui\UiComponent;
use n2n\dispatch\property\ManagedProperty;

class StringArrayMag extends MagAdapter {
	const DEFAULT_NUM_ADDITIONS = 5;
	private $inputAttrs;
	private $mandatory;
	
	public function __construct($propertyName, $label, array $values = array(), 
			$mandatory = false, array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($propertyName, $label, $values, $containerAttrs);
		$this->mandatory = (bool) $mandatory;
		$this->inputAttrs = $inputAttrs;
	}

	public function setMandatory($mandatory) {
		$this->mandatory = (boolean) $mandatory;
	}
	
	public function isMandatory() {
		return $this->mandatory;
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		$formHtml = $view->getFormHtmlBuilder();
		
		$view->getHtmlBuilder()->meta()->addJs('js/array-option.js', 'n2n', false, false, null, HtmlBuilderMeta::TARGET_BODY_END);
		
		$StringMags = $formHtml->meta()->getMapValue($propertyPath);
		$uiComponent = new HtmlElement('ul', array('class' => 'n2n-option-array', 'data-num-existing' => count($StringMags)));
		
		foreach ($StringMags as $key => $value) {
			if (!isset($value)) continue;
			$uiComponent->appendContent(new HtmlElement('li', null, 
					$formHtml->getInput($propertyPath->createArrayFieldExtendedPath($key))));
		}
		
		for ($i = 0; $i < self::DEFAULT_NUM_ADDITIONS; $i++) {
			$uiComponent->appendContent(new HtmlElement('li', null,
					$formHtml->getInput($propertyPath->createArrayFieldExtendedPath(null))));
		} 
		return new HtmlElement('div', array('class' => 'n2n-array-option'), $uiComponent);
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, true);
	}

	/* (non-PHPdoc)
	 * @see \n2n\dispatch\mag\Mag::setupBindingDefinition()
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValNotEmpty());
		}
	}
}
