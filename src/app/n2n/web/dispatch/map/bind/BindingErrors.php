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
namespace n2n\web\dispatch\map\bind;

use n2n\web\dispatch\map\PropertyPathPart;
use n2n\l10n\Message;
use n2n\web\dispatch\map\val\ValidationUtils;

class BindingErrors {
	private $invalidRawValues = array();
	private $errorCollections = array();
	
	public function hasInvalidRawValues() {
		return (bool)sizeof($this->invalidRawValues);
	}
	
	public function hasInvalidRawValue(PropertyPathPart $pathPart) {
		return isset($this->invalidRawValues[$pathPart->__toString()]);
	}
	
	public function getInvalidRawValue(PropertyPathPart $pathPart) {
		if ($this->hasInvalidRawValue($pathPart)) {
			return $this->invalidRawValues[$pathPart->__toString()];
		}
	
		return null;
	}
	
	public function setInvalidRawValue(PropertyPathPart $pathPart, $value) {
		$this->invalidRawValues[$pathPart->__toString()] = $value;
	}
	
	public function addError($propertyExpression, $errorMessage) {
		$pathPart = PropertyPathPart::createFromExpression($propertyExpression);
		
		if (!isset($this->errorCollections[$pathPart->getPropertyName()])) {
			$this->errorCollections[$pathPart->getPropertyName()] = new PropertyErrorCollection();
		}
		
		$this->errorCollections[$pathPart->getPropertyName()]->add($pathPart->getArrayKey(),
				ValidationUtils::createMessage($errorMessage));
	}
	
	public function addErrors($propertyExpression, array $errorMessages) {
		$pathPart = PropertyPathPart::createFromExpression($propertyExpression);
		
		foreach ($errorMessages as $errorMessage) {
			$this->addError($pathPart, $errorMessage);
		}
	}
	
	public function addErrorCode($propertyExpression, $errorCode, array $args = null, $moduleNamespace = null) {
		$this->addError($propertyExpression, Message::createCodeArg($errorCode, $args, 
				Message::SEVERITY_ERROR, $moduleNamespace));
		
	}
	
	public function isEmpty() {
		return empty($this->errorCollections);
	}
	
	public function getErrorPropertyNames() {
		return array_keys($this->errorCollections);
	}
	
	public function hasErrors($propertyExpression) {
		$pathPart = PropertyPathPart::createFromExpression($propertyExpression);
		return isset($this->errorCollections[$pathPart->getPropertyName()]) 
				&& $this->errorCollections[$pathPart->getPropertyName()]->has($pathPart->getArrayKey());
	}
	
	public function getErrors(PropertyPathPart $pathPart) {
		if ($this->hasErrors($pathPart)) {
			return $this->errorCollections[$pathPart->getPropertyName()]->get($pathPart->getArrayKey());
		}
		
		return array();
	}
	
	public function hasPropertyErrors($propertyName) {
		return isset($this->errorCollections[(string) $propertyName]);
	}
	
	public function getPropertyErrors($propertyName) {
		if ($this->hasPropertyErrors($propertyName)) {
			return $this->errorCollections[(string) $propertyName]->getAll();
		}
		return array();
	}
	
	public function getAllErrors() {
		$errorCollections = array();
		foreach ($this->errorCollections as $errorCollection) {
			$errorCollections = array_merge($errorCollections, $errorCollection->getAll());
		}
		return $errorCollections;
	}
	
	public function removePropertyErrors($propertyName) {
		unset($this->errorCollections[(string) $propertyName]);
	}
	
	public function removeErrors($pathPartExpression) {
		$pathPart = PropertyPathPart::createFromExpression($pathPartExpression);
		$propertyName = $pathPart->getPropertyName();
		if (!$pathPart->isArray()) {
			$this->removePropertyErrors($propertyName);
			return;
		}
		
		if (isset($this->errorCollections[$propertyName])) {
			$this->errorCollections[$propertyName]->removeAll($pathPart->getArrayKey());
		}
		
	}
	
	public function removeAllErrors() {
		$this->errorCollections = array();
	}
}

class PropertyErrorCollection {
	private $propErrors = array();
	private $arrFieldErrors = array();
	
	public function add($arrayKey, Message $errorMessage) {
		if (is_null($arrayKey)) {
			$this->propErrors[] = $errorMessage;
			return;
		}
		
		if (!array_key_exists($arrayKey, $this->arrFieldErrors)) {
			$this->arrFieldErrors[$arrayKey] = array();
		}
		$this->arrFieldErrors[$arrayKey][] = $errorMessage;
	}
	
	public function removeArrayErrors($arrayKey) {
		if ($arrayKey !== null) {
			unset($this->arrFieldErrors[$arrayKey]);
			return;
		}
		
		$this->arrFieldErrors = array();
	}
	
	public function has($arrayKey) {
		if (is_null($arrayKey)) {
			return (bool)sizeof($this->propErrors);
		}
		
		return isset($this->arrFieldErrors[$arrayKey]);
	}
	
	public function get($arrayKey) {
		if (is_null($arrayKey)) {
			return $this->propErrors;
		}
		
		if (isset($this->arrFieldErrors[$arrayKey])) {
			return $this->arrFieldErrors[$arrayKey];
		}
		
		return array();
	}
	
	public function getAll() {
		$errorMessages = $this->propErrors;
		foreach ($this->arrFieldErrors as $fieldErrors) {
			$errorMessages = array_merge($errorMessages, $fieldErrors);
		}
		
		return $errorMessages;
	}
}
