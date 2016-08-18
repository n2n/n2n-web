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

use n2n\dispatch\map\bind\BindingDefinition;
use n2n\reflection\ArgUtils;
use n2n\util\uri\Url;
use n2n\dispatch\map\val\impl\ValUrl;
use n2n\util\ex\UnsupportedOperationException;
use n2n\dispatch\map\bind\MappingDefinition;

class UrlMag extends StringMag {
	private $allowedSchemes;
	private $autoScheme;
	private $relativeAllowed = false;
	
	public function __construct(string $propertyName, $labelLstr, Url $value = null, bool $mandatory = false, 
			int $maxlength = null, array $containerAttrs = null, array $inputAttrs = null) {
		parent::__construct($propertyName, $labelLstr, $value, $mandatory, $maxlength, false, $containerAttrs, 
				$inputAttrs);
	}
	
	public function setMultiline(bool $multiline) {
		throw new UnsupportedOperationException();
	}
	
	public function setAllowedSchemes(array $allowedSchemes = null) {
		$this->allowedSchemes = $allowedSchemes;
	}
	
	public function getAllowedSchemes() {
		return $this->allowedSchemes;
	}
	
	public function setRelativeAllowed(bool $relativeAllowed) {
		$this->relativeAllowed = $relativeAllowed;
	}
	
	public function isRelativeAllowed(): bool {
		return $this->relativeAllowed;
	}
	
	public function setAutoScheme(string $autoScheme = null) {
		$this->autoScheme = $autoScheme;
	}
	
	public function getAutoScheme() {
		return $this->autoScheme;
	}
	
	public function setValue($value) {
		ArgUtils::valType($value, Url::class, true);
		$this->value = $value;
	}
	
	public function setFormValue($formValue) {
		$this->value = null;
		if ($formValue !== null) {
			$this->value = Url::create($formValue);
		}
	}
	
	public function getFormValue() {
		if ($this->value === null) return null;
		return (string) $this->value;
	}
	
	public function setupMappingDefinition(MappingDefinition $md) {
		if ($this->autoScheme === null || !$md->isDispatched() || $this->relativeAllowed) return;
		
		$urlStr = $md->getDispatchedValue($this->propertyName);
		if (!strlen($urlStr)) return;
		
		$url = Url::create($urlStr, true);
		if (!$url->hasScheme() && $url->getAuthority()->isEmpty()) {
			$md->getMappingResult()->__set($this->propertyName, $this->autoScheme . '://' . $urlStr);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\dispatch\mag\Mag::setupBindingDefinition($bindingDefinition)
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		parent::setupBindingDefinition($bd);
		
		$bd->val($this->propertyName, new ValUrl($this->allowedSchemes, null, $this->relativeAllowed));
	}

	

}
