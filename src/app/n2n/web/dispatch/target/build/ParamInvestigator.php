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
namespace n2n\web\dispatch\target\build;

use n2n\web\dispatch\map\PropertyPath;
use n2n\web\http\UploadDefinition;

class ParamInvestigator {
	private $valueParams;
	private $uploadDefinitionParams;
	private $attrParams;
	
	public function __construct(array $valueParams, array $uploadDefinitionParams, array $attrParams) {
		$this->valueParams = $valueParams;
		$this->uploadDefinitionParams = $uploadDefinitionParams;
		$this->attrParams = $attrParams;
	}
	
	/**
	 * @return array 
	 */
	public function getValueParams() {
		return $this->valueParams;
	}
	
	/**
	 * @retun array 
	 */
	public function getUploadDefinitionParams() {
		return $this->uploadDefinitionParams;
	}
	
	public function findValue(PropertyPath $propertyPath) {
		$pathParts = $propertyPath->toArray();
		return $this->find($this->valueParams, $pathParts);
	}
	
	public function findAttr(PropertyPath $propertyPath, $optionExt = null) {
		$pathParts = $propertyPath->toArray();
		
		$attr = $this->find($this->attrParams, $pathParts);
		if ($optionExt === null) return $attr;
		
		if (is_array($attr) && isset($attr[$optionExt])) {
			return $attr[$optionExt];
		}
		
		return null;
	}
	
	public function findUploadDefinition(PropertyPath $propertyPath) {
		$pathParts = $propertyPath->toArray();
		$uploadDefinition = $this->find($this->uploadDefinitionParams, $pathParts);
		
		if ($uploadDefinition instanceof UploadDefinition) {
			return $uploadDefinition;
		}
		
		return null;
	}
	
	public function findUploadDefinitions(PropertyPath $propertyPath) {
		$uploadDefinitions = $this->find($this->uploadDefinitionParams, $propertyPath->toArray());
		
		if (!is_array($uploadDefinitions)) {
			return array();
		}
		
		foreach ($uploadDefinitions as $uploadDefinition) {
			if (!($uploadDefinition instanceof UploadDefinition)) {
				return array();
			}
		}
		return array();
	}
	
	private function find(&$param, array &$pathParts) {
		if (empty($pathParts)) return $param;
		
		if (!is_array($param)) return null;
		
		$pathPart = array_shift($pathParts);
		$propertyName = $pathPart->getPropertyName();
	
		if (!isset($param[$propertyName])) return null;
		
		if (!$pathPart->isArray()) {
			return $this->find($param[$propertyName], $pathParts);
		}
		
		$key = $pathPart->getResolvedArrayKey();
		if (!isset($param[$propertyName][$key])) return null;
		
		return $this->find($param[$propertyName][$key], $pathParts);
	}
}
