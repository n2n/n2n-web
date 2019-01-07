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

use n2n\web\dispatch\map\val\PropertyValidator;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\MappingResult;
use n2n\util\type\ArgUtils;
use n2n\web\dispatch\map\val\Validator;
use n2n\util\col\GenericArrayObject;
use n2n\web\dispatch\map\val\ClosureValidator;

class BindingDefinition {
	private $bindingTree;
	private $dispatchModel;
	private $mappingResult;
	private $propertyPath;
	private $validators;
	/**
	 * @param BindingTree $bindingTree
	 * @param MappingResult $mappingResult
	 * @param PropertyPath $propertyPath
	 */
	public function __construct(BindingTree $bindingTree, MappingResult $mappingResult, 
			PropertyPath $propertyPath) {
		$this->bindingTree = $bindingTree;
		$this->dispatchModel = $mappingResult->getDispatchModel();
		$this->mappingResult = $mappingResult;
		$bindingTree->register($propertyPath, $this);
		
		$this->propertyPath = $propertyPath;
		$this->validators = new GenericArrayObject(null, 'n2n\web\dispatch\map\val\Validator');
	}
	/**
	 * @return MappingResult
	 */
	public function getMappingResult() {
		return $this->mappingResult;
	}
	/**
	 * @return BindingTree
	 */
	public function getBindingTree() {
		return $this->bindingTree;
	}
	/**
	 * @return PropertyPath
	 */
	public function getPropertyPath() {
		return $this->propertyPath;
	}
	/**
	 * @param mixed $propertyNames
	 * @param PropertyValidator $propertyValidator
	 * @param PropertyValidator $propertyValidator2
	 */
	public function val($propertyNames, PropertyValidator $propertyValidator, 
			PropertyValidator $propertyValidator2 = null) {
		$managedProperties = array();
		foreach ((array) $propertyNames as $propertyName) {
			$managedProperties[] = $this->dispatchModel->getPropertyByName($propertyName);
		}
		
		$args = func_get_args();
		array_shift($args);
		if (count($args) > 2) {
			ArgUtils::valArray($args, 'n2n\web\dispatch\map\val\PropertyValidator');
		}
		
		foreach ($args as $propertyValidator) {
			$propertyValidator->initialize($managedProperties);
			$this->validators->append($propertyValidator);
		}
	}
	
	public function reset($propertyName = null) {
		if ($propertyName !== null) {
			$this->mappingResult->__unset($propertyName);
			$this->mappingResult->getBindingErrors()->removePropertyErrors($propertyName);
		} else {
			$this->mappingResult->unsetPropertyValues();
			$this->mappingResult->getBindingErrors()->removeAllErrors();
		}
		
		$this->bindingTree->unregisterByPropertyPath($this->extPropertyPath($propertyName));
	}
	
	/**
	 * @param Validator $validator
	 */
	public function valo(Validator $validator) {
		$this->validators->append($validator);
	}
	/**
	 * @param \Closure $closure
	 */
	public function closure(\Closure $closure) {
		$this->validators->append(new ClosureValidator($closure));
	}
	/**
	 * @return \n2n\util\col\GenericArrayObject
	 */
	public function getValidators() {
		return $this->validators;
	}
	/**
	 * @return BindingDefinition
	 */
	public function getParent() {
		if ($this->propertyPath === null) {
			return null;
		}
		return $this->bindingTree->lookup($this->propertyPath->reduced(1));
	}
	
	private function extPropertyPath($propertyExpression) {
		if ($propertyExpression === null) {
			return $this->propertyPath;
		}
		
		if ($this->propertyPath !== null) {
			return $this->propertyPath->ext($propertyExpression);
		}
		
		return PropertyPath::createFromPropertyExpression($propertyExpression);
	}
	
	/**
	 * @return BindingDefinition
	 */
	public function getDescendant($propertyExpression) {
		return $this->bindingTree->lookup($this->extPropertyPath($propertyExpression));
	}
	/**
	 * @return BindingDefinition
	 */
	public function getDescendants($propertyExpression) {
		return $this->bindingTree->lookupArray($this->extPropertyPath($propertyExpression));
	}
}
