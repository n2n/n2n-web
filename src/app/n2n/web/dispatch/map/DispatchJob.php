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
namespace n2n\web\dispatch\map;

use n2n\web\dispatch\target\DispatchTarget;
use n2n\web\dispatch\Dispatchable;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\bind\ObjectMapper;
use n2n\web\dispatch\map\bind\BindingTree;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\model\UnknownManagedMethodException;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\web\dispatch\target\build\ParamInvestigator;

class DispatchJob {
	private $methodName;
	private $dispatchTarget;
	private $paramInvestigator;
	private $bindingTree;
	private $mappingResult;
	private $returnValue;
	
	public function __construct(DispatchTarget $dispatchTarget, ParamInvestigator $paramInvestigator, $methodName) {
		$this->dispatchTarget = $dispatchTarget;
		$this->paramInvestigator = $paramInvestigator;
		$this->methodName = $methodName;
		$this->bindingTree = new BindingTree();
	}
	/**
	 * @return boolean
	 */
	public function isExecuted() {
		return $this->mappingResult !== null;
	}
	/**
	 * @return MappingResult 
	 */
	public function getMappingResult() {
		return $this->mappingResult;
	}
	
	/**
	 * @return string|null
	 */
	public function getMethodName() {
		return $this->methodName;
	}
	
	/**
	 * @return \n2n\web\dispatch\target\DispatchTarget
	 */
	public function getDispatchTarget() {
		return $this->dispatchTarget;
	}
	
	/**
	 * @return mixed 
	 */
	public function getReturnValue() {
		return $this->returnValue;
	}
	/**
	 * @param Dispatchable $dispatchable
	 * @param string $methodName
	 * @return boolean
	 */
	public function matches(Dispatchable $dispatchable, string $methodName = null) {
		return $this->dispatchTarget->getDispatchClassName() == get_class($dispatchable)
				&& $this->methodName === $methodName;
	}
	/**
	 * @param Dispatchable $dispatchable
	 * @param string $methodName
	 * @param N2nContext $n2nContext
	 * @throws IllegalStateException
	 * @throws CorruptedDispatchException
	 * @return mixed
	 */
	public function execute(Dispatchable $dispatchable, $methodName, N2nContext $n2nContext) {
		if ($this->isExecuted()) {
			throw new IllegalStateException('DispatchJob already executed.');
		}
		
		if (!$this->matches($dispatchable, $methodName)) return null;
		
		$objectMapper = new ObjectMapper($this->dispatchTarget->getObjectItem(), $this->methodName, new PropertyPath(array()));
		
		$this->mappingResult = $objectMapper->createMappingResult($dispatchable, 
				$this->bindingTree, $this->paramInvestigator, $n2nContext);
		
		foreach ($this->bindingTree->getAll() as $bindingDefinition) {
			if (!$this->bindingTree->containsPropertyPath($bindingDefinition->getPropertyPath())) continue;
			
			$bindingDefinition->getMappingResult()->getDispatchModel()->getDispatchItemFactory()
					->setupBindingDefinition($bindingDefinition, $n2nContext);
		}
		
		// need to lookup again because BindingDefinitions could be removed
		$bindingDefinitions = $this->bindingTree->getAll();
		
		foreach (array_reverse($bindingDefinitions) as $bindingDefinition) {
			$this->validateObject($bindingDefinition, $n2nContext);
		}
		
		foreach ($bindingDefinitions as $bindingDefinition) {
			if (!$bindingDefinition->getMappingResult()->getBindingErrors()->isEmpty()) {
				return false;
			}
		}
		
		$method = null;
		if ($this->methodName !== null) {
			try {
				$method = $this->mappingResult->getDispatchModel()->getMethodByName($this->methodName);
			} catch (UnknownManagedMethodException $e) {
				throw new CorruptedDispatchException('Invalid method.', 0, $e);
			}	
		}
		
		foreach (array_reverse($bindingDefinitions) as $bindingDefinition) {
			$this->bindObject($bindingDefinition, $n2nContext);
		}
		
		if ($method !== null) {
			$invoker = new MagicMethodInvoker($n2nContext);
			$this->returnValue = $invoker->invoke($this->mappingResult->getObject(), $method);
		}
		
		return true;
	}
	
	private function validateObject(BindingDefinition $bindingDefinition, N2nContext $n2nContext) {
		$mappingResult = $bindingDefinition->getMappingResult();
		foreach ($bindingDefinition->getValidators() as $validator) {
			$validator->validate($mappingResult, $n2nContext);
		}
		return $mappingResult->getBindingErrors()->isEmpty();
	}
	
	private function bindObject(BindingDefinition $bindingDefinition, N2nContext $n2nContext) {
		$mappingResult = $bindingDefinition->getMappingResult();
		$dispatchModel = $mappingResult->getDispatchModel();
		$dispatchable = $mappingResult->getObject();
		foreach ($dispatchModel->getProperties() as $propertyName => $managedProperty) {
			if (!$mappingResult->containsPropertyName($propertyName)) continue;
	
			$managedProperty->writeValue($dispatchable,
					$managedProperty->readValueFromMappingResult($mappingResult, $n2nContext));
		}
	}
	
	
// 	private $dispatchContext;
// 	private $dispatchTarget;
// 	private $rawValues;
// 	private $methodName;
// 	private $mappingResult;
// 	private $executed = false;
// 	private $dispatchListeners = array();
	
// 	public function __construct(DispatchContext $dispatchContext, DispatchTarget $dispatchTarget, array $httpParams) {
// 		$this->dispatchContext = $dispatchContext;
// 		$this->dispatchTarget = $dispatchTarget;
// 		$this->dispatchTarget->applyHttpParams($httpParams, $this->rawValues, $this->methodName);
// 		$this->mappingResult = null;
// 	}  
	
// 	public function execute(Dispatchable $dispatchableObject, $methodName) {
// 		if ($this->methodName != $methodName) return false;
// 		$this->executed = true;
		
// 		$methodType = null;
// 		$typeAnalyzer = null;
// 		if (isset($methodName)) {
// 			$typeAnalyzer = $this->getDispatchContext()->getDispatchableTypeAnalyzer($dispatchableObject);
// 			$methodType = $typeAnalyzer->getMethodTypeByName($methodName);
// 		}
				
// 		$objectMapper = new ObjectMapper($this, null, $dispatchableObject);
// 		$this->mappingResult = $objectMapper->mapObject($this->rawValues, new PropertyPath(array()), 
// 				$this->dispatchTarget->getProps(), $dispatchableObject, $methodName);
		
// 		foreach ($this->dispatchListeners as $dispatchListener) {
// 			$dispatchListener->mappingCompleted($this);
// 		}
		
// 		if ($this->mappingResult->hasErrors()) return false;
// 		$this->applyToObject($this->mappingResult);
		
// 		foreach ($this->dispatchListeners as $dispatchListener) {
// 			$dispatchListener->bindingCompleted($this);
// 		}
		
// 		if (isset($methodType)) {
// 			$returnValue = $typeAnalyzer->executeMethod($dispatchableObject, $methodType->getName());
// 			if (isset($returnValue)) {
// 				return $returnValue;
// 			}
// 		}
		
// 		return true;
// 	}
	
// 	public function isExecuted() {
// 		return $this->executed;
// 	}
	
// 	private function applyToObject(MappingResult $mappingResult) {
// 		$dispatchableObject = $mappingResult->getObject();
// 		$typeAnalyzer = $this->dispatchContext->getDispatchableTypeAnalyzer($dispatchableObject);
		
// 		foreach ($mappingResult->getPropertyValues() as $name => $value) {
// 			if ($value instanceof MappingResult) {
// 				$value = $this->applyToObject($value);
// 			} else if (is_array($value) || $value instanceof \ArrayObject) {
// 				foreach ($value as $key => $valueField) {
// 					if ($valueField instanceof MappingResult) {
// 						$value[$key] = $this->applyToObject($valueField);
// 					}		
// 				}
// 			}
						
// 			$typeAnalyzer->setPropertyValue($dispatchableObject, $name, $value);
// 		}
		
// 		return $dispatchableObject;
// 	}
	
// 	public function isCompatibleWith(Dispatchable $object) {
// 		return get_class($object) == $this->dispatchTarget->getClassName();
// 	}
// 	/**
// 	 * 
// 	 * @return DispatchContext
// 	 */
// 	public function getDispatchContext() {
// 		return $this->dispatchContext;
// 	}
// 	/**
// 	 * 
// 	 * @return DispatchTarget
// 	 */
// 	public function getDispatchTarget() {
// 		return $this->dispatchTarget;
// 	}
	
// 	public function getMethodName() {
// 		return $this->methodName;
// 	}
// 	/**
// 	 * 
// 	 * @return MappingResult
// 	 */
// 	public function getMappingResult() {
// 		return $this->mappingResult;
// 	}
	
// 	public function registerDispatchListener(DispatchListener $dispatchListener) {
// 		$this->dispatchListeners[spl_object_hash($dispatchListener)] = $dispatchListener;
// 	}
	
// 	public function unregisterDispatchListener(DispatchListener $dispatchListener) {
// 		unset($this->dispatchListeners[spl_object_hash($dispatchListener)]);
// 	}
}
