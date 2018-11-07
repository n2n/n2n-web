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

use n2n\web\dispatch\target\ObjectItem;
use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\map\MappingResult;
use n2n\web\dispatch\target\build\ParamInvestigator;

class MappingDefinition {
	private $mappingResult;
	private $objectItem;
	private $methodName;
	private $paramInvestigator;
	private $ignoredPropertyNames = array();
	
	public function __construct(MappingResult $mappingResult, ObjectItem $objectItem = null, 
			$methodName = null, ParamInvestigator $paramInvestigator = null) {
		$this->mappingResult = $mappingResult;
		$this->objectItem = $objectItem;
		$this->methodName = $methodName;
		$this->paramInvestigator = $paramInvestigator;
	}
	
	/**
	 * @return \n2n\web\dispatch\map\MappingResult
	 */
	public function getMappingResult() {
		return $this->mappingResult;
	}
	
	public function ignore($propertyName) {
		$this->ignoredPropertyNames[$propertyName] = $propertyName;
	}
	
	public function removeIgnore($propertyName) {
		unset($this->ignoredPropertyNames[$propertyName]);
	}
	
	public function isPropertyIgnored($propertyName) {
		return isset($this->ignoredPropertyNames[$propertyName]);
	}
	
	public function isDispatched() {
		return $this->objectItem !== null;
	}
	
	public function getDispatchedObjectItem() {
		if ($this->objectItem === null) {
			throw new IllegalStateException('No dispatch happend.');
		}
		 
		return $this->objectItem;
	}
	
	public function getDispatchedValue($propertyName) {
		$propertyPath = $this->getDispatchedObjectItem()->getPropertyPath()->ext($propertyName);

		return $this->paramInvestigator->findValue($propertyPath);
	}
	
	public function getDispatchedUploadDefinition($propertyName) {
		$propertyPath = $this->getDispatchedObjectItem()->getPropertyPath()->ext($propertyName);
		
		return $this->paramInvestigator->findUploadDefinition($propertyPath);
	}
	
	public function getMethodName() {
		return $this->methodName;
	}
}
