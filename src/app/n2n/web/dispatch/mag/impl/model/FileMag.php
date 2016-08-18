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

use n2n\dispatch\map\val\impl\ValImageFile;
use n2n\dispatch\map\val\impl\ValFileExtensions;
use n2n\dispatch\map\PropertyPath;
use n2n\io\managed\File;
use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\map\val\impl\ValImageResourceMemory;
use n2n\dispatch\property\impl\FileProperty;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\map\val\impl\ValNotEmpty;
use n2n\dispatch\property\ManagedProperty;
use n2n\ui\UiComponent;

class FileMag extends MagAdapter {
	
	private $mandatory;
	private $allowedExtensions;
	private $inputAttrs;
	private $checkImageResourceMemory;
	
	public function __construct($propertyName, $label, array $allowedExtensions = null, $checkImageResourceMemory = false, 
			File $value = null, $mandatory = false, array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($propertyName, $label, $value, $containerAttrs);
		$this->inputAttrs = $inputAttrs;
		$this->allowedExtensions = $allowedExtensions;
		$this->inputAttrs = $inputAttrs;
		$this->checkImageResourceMemory = (boolean) $checkImageResourceMemory;
		$this->mandatory = (boolean) $mandatory;
	}

	public function setMandatory($mandatory) {
		$this->mandatory = (boolean) $mandatory;
	}
	
	public function isMandatory() {
		return $this->mandatory;
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new FileProperty($accessProxy, false);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValNotEmpty());
		}
	
		if (null !== $this->allowedExtensions) {
			$bd->val($this->propertyName, new ValFileExtensions($this->allowedExtensions));
		}
		
		if ($this->checkImageResourceMemory) {
			$bd->val($this->propertyName, new ValImageResourceMemory());
		}
		
		$bd->val($this->propertyName, new ValImageFile(false));
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $htmlView): UiComponent {
		return $htmlView->getFormHtmlBuilder()->getInputFileWithLabel($propertyPath, $this->inputAttrs);
	}
}
