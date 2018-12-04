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
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\dispatch\map\val;

use n2n\web\dispatch\map\MappingResult;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\map\bind\BindingErrors;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\l10n\Message;

abstract class SinglePropertyValidator extends PropertyValidatorAdapter {
	private $mappingResult;
	private $n2nContext;
	private $managedProperty;
	
	public function validate(MappingResult $mappingResult, N2nContext $n2nContext) {
		$this->mappingResult = $mappingResult;
		$this->n2nContext = $n2nContext;
		
		foreach ($this->getManagedProperties() as $managedProperty) {
			$this->managedProperty = $managedProperty;
			if ($mappingResult->getBindingErrors()->hasErrors($managedProperty->getName())
					|| !$mappingResult->containsPropertyName($managedProperty->getName())) continue;
			$this->validateProperty($mappingResult->__get($managedProperty->getName()));			
		}
		
		$this->managedProperty = $managedProperty;
		$this->mappingResult = null;
		$this->n2nContext = null;
	}
	/**
	 * @return BindingErrors 
	 */
	protected function getBindingErrors() {
		return $this->getMappingResult()->getBindingErrors();
	}
	/**
	 * @return MappingResult
	 * @throws IllegalStateException
	 */
	protected function getMappingResult() {
		if ($this->mappingResult === null) {
			throw new IllegalStateException('Validator not active.');
		}
		
		return $this->mappingResult;
	}
	/**
	 * @return N2nContext
	 * @throws IllegalStateException
	 */
	protected function getN2nContext() {
		if ($this->n2nContext === null) {
			throw new IllegalStateException('Validator not active.');
		}
		
		return $this->n2nContext;
	}
	/**
	 * @return ManagedProperty
	 * @throws IllegalStateException
	 */
	protected function getManagedProperty() {
		if ($this->managedProperty === null) {
			throw new IllegalStateException('Validator not active.');
		}
		
		return $this->managedProperty;
	}
	
	protected function getPathPart() {
		return new PropertyPathPart($this->getManagedProperty()->getName());
	}
	
	protected function getLabel(): string {
		return $this->getMappingResult()->getLabel($this->getPathPart());
	}
	
	protected function failed($errorMessage, $fallbackTextCode = null, array $fallbackArgs = null, $fallbackNs = null) {
		ValidationUtils::registerErrorMessage($this->getMappingResult(), array($this->getPathPart()), 
				$fallbackTextCode, (array) $fallbackArgs, $fallbackNs, 
				ValidationUtils::createMessage($errorMessage));
	}
	
	protected function failedCode($textCode, array $args = null, $module = null, $num = null) {
		$this->getBindingErrors()->addError($this->getPathPart(), 
				Message::createCodeArg($textCode, $args, Message::SEVERITY_ERROR, $module, $num));
	}
	
	protected abstract function validateProperty($mapValue);
}
