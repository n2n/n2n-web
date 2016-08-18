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
use n2n\dispatch\mag\MagCollection;
use n2n\ui\view\impl\html\HtmlUtils;
use n2n\dispatch\property\impl\ObjectProperty;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\reflection\ArgUtils;
use n2n\ui\UiComponent;
use n2n\dispatch\property\ManagedProperty;

class MagCollectionMag extends MagAdapter {
	private $magCollection;
	
	public function __construct($propertyName, $label, MagCollection $magCollection, 
			array $containerAttrs = null) {
		parent::__construct($propertyName, $label, 
				HtmlUtils::mergeAttrs(array('class' => 'n2n-option-collection-option'), (array) $containerAttrs));
		$this->magCollection = $magCollection;
	}
	
	public function getMagCollection() {
		return $this->magCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\dispatch\mag\Mag::createUiField($propertyPath, $view)
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent {
		return $view->getImport('\n2n\dispatch\mag\impl\view\magCollectionOption.html',
				array('propertyPath' => $propertyPath));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\dispatch\mag\Mag::createManagedProperty($accessProxy)
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ObjectProperty($accessProxy, false);
	}
	
	public function getValue() {
		return $this->magCollection->readValues();
	}
	
	public function setValue($value) {
		ArgUtils::valType($value, 'array');
		
		$this->magCollection->writeValues($value);
	}
	
	public function getFormValue() {
		return new MagForm($this->magCollection);
	}
	
	public function setFormValue($formValue){
		ArgUtils::valObject($formValue, MagForm::class);
		$this->magCollection = $formValue->getMagCollection();
	}
	
	public function setupBindingDefinition(BindingDefinition $bindingDefinition) {
		
	}
}
