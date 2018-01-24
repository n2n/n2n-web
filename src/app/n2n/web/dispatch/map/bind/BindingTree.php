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

use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\UnresolvablePropertyPathException;
use n2n\util\StringUtils;

class BindingTree {
	private $bindingDefinitions = array();
	
	public function registerBase(BindingDefinition $binding) {
		if (isset($this->bindingDefinitions[null])) {
			throw new BindingTreeConflictException('Base Binding already defined.');
		}
		$this->bindingDefinitions[null] = $binding;
	}
	
	public function register(PropertyPath $propertyPath, BindingDefinition $bindingDefinition) {
		if ($propertyPath->isEmpty()) {
			$this->registerBase($bindingDefinition);
			return;
		}
		
		$lastPart = $propertyPath->getLast();
		if (!$lastPart->isArray()) {
			if (isset($this->bindingDefinitions[(string) $propertyPath])) {
				throw new BindingTreeConflictException('Binding for property path already defined:'
						. (string) $propertyPath);
			}
			
			$this->bindingDefinitions[(string) $propertyPath] = $bindingDefinition;
			return;
		}
		
		$basePropertyPathStr = (string) $propertyPath->fieldReduced();
		if (!isset($this->bindingDefinitions[$basePropertyPathStr])) {
			$this->bindingDefinitions[$basePropertyPathStr] = array();
		} else if (!is_array($this->bindingDefinitions[$basePropertyPathStr])) {
			throw new BindingTreeConflictException('Non-array Binding defined for property path:' 
					. $basePropertyPathStr);
		}
		
		$arrayKey = $lastPart->getResolvedArrayKey();
		if (!isset($this->bindingDefinitions[$basePropertyPathStr][$arrayKey])) {
			$this->bindingDefinitions[$basePropertyPathStr][$arrayKey] = $bindingDefinition;
		}
	}
	
	public function containsPropertyPath(PropertyPath $propertyPath) {
		if ($propertyPath->isEmpty()) {
			return isset($this->bindingDefinitions[null]);
		}
		
		$lastPart = $propertyPath->getLast();
		$fieldReducedPropertyPathStr = (string) $propertyPath->fieldReduced();
		
		if (!$lastPart->isArray()) {
			return isset($this->bindingDefinitions[$fieldReducedPropertyPathStr]);
		}
		
		return isset($this->bindingDefinitions[$fieldReducedPropertyPathStr][$lastPart->getResolvedArrayKey()]);
	}
	
	public function unregisterByPropertyPath(PropertyPath $propertyPath) {
		if ($propertyPath->isEmpty()) {
			$this->bindingDefinitions = array();
			return;
		}
		
		$propertyPathStr = (string) $propertyPath;
		$fieldReducedPropertyPathStr = (string) $propertyPath->fieldReduced();
		$lastPathPart = $propertyPath->getLast();
		foreach ($this->bindingDefinitions as $bdPropertyPathStr => $bindingDefinition) {
			if (StringUtils::startsWith($propertyPathStr, $bdPropertyPathStr)) {
				unset($this->bindingDefinitions[$bdPropertyPathStr]);
				continue;
			}
			
			if (!$lastPathPart->isArray()) continue;
			
			if ($fieldReducedPropertyPathStr == $bdPropertyPathStr) {
				if (!is_array($bindingDefinition)) {
					throw new UnresolvablePropertyPathException(
							'No binding array defined for property path: ' . (string) $propertyPath);
				}
				
				unset($this->bindingDefinitions[$bdPropertyPathStr][$lastPathPart->getResolvedArrayKey()]);
			}
		}
	}
	
	public function lookupAll(PropertyPath $propertyPath) {
		if ($propertyPath->isEmpty()) {
			return $this->bindingDefinitions;
		}
		
		$bindingDefinitions = array();
		$basePropertyPathStr = (string) $propertyPath;
		foreach ($this->bindingDefinitions as $propertyPathStr => $bd) {
			if (!StringUtils::startsWith($basePropertyPathStr, $propertyPathStr)) continue;
			
			if (!is_array($bd)) {
				$bindingDefinitions[] = $bd;
				continue;
			}
			
			foreach ($bd as $bindingDefinition) {
				$bindingDefinitions[] = $bindingDefinition;
			}
		}
		return $bindingDefinitions;
	}
	
	public function lookup(PropertyPath $propertyPath) {
		if ($propertyPath->isEmpty()) {
			if (isset($this->bindingDefinitions[null])) {
				return $this->bindingDefinitions[null];
			}
		} else if (!$propertyPath->getLast()->isArray()) {
			$propertyPathStr = (string) $propertyPath;
			if (isset($this->bindingDefinitions[$propertyPathStr]) 
					&& !is_array($this->bindingDefinitions[$propertyPathStr])) {
				return $this->bindingDefinitions[$propertyPathStr];
			}
		} else {
			$fieldKey = $propertyPath->getLast()->getResolvedArrayKey();
			$propertyPathStr = (string) $propertyPath->fieldReduced();
			if (isset($this->bindingDefinitions[$propertyPathStr])
					&& is_array($this->bindingDefinitions[$propertyPathStr])
					&& isset($this->bindingDefinitions[$propertyPathStr][$fieldKey])) {
				return $this->bindingDefinitions[$propertyPathStr][$fieldKey];
			}
			
		}
		
		throw new UnresolvablePropertyPathException(
				'No Binding defined for property path: ' . (string) $propertyPath); 
	}
	
	public function lookupArray(PropertyPath $propertyPath) {
		$propertyPathStr = (string) $propertyPath;
		if (isset($this->bindingDefinitions[$propertyPathStr])
				&& is_array($this->bindingDefinitions[$propertyPathStr])) {
			return $this->bindingDefinitions[$propertyPathStr];
		}
		
		throw new UnresolvablePropertyPathException(
				'No Binding array defined for property path: ' . (string) $propertyPath);
	}
	
	public function getAll() {
		$bindingDefinitions = array();
		foreach ($this->bindingDefinitions as $bindingDefinition) {
			if (!is_array($bindingDefinition)) {
				$bindingDefinitions[] = $bindingDefinition;
				continue;
			}
				
			foreach ($bindingDefinition as $bindingDefinitionField) {
				$bindingDefinitions[] = $bindingDefinitionField;
			}
		}
		return $bindingDefinitions;
	}
}
