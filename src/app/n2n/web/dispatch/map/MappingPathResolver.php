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

use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\DispatchException;
use n2n\core\container\N2nContext;
use n2n\impl\web\dispatch\ui\Form;
use n2n\web\dispatch\DispatchContext;

class MappingPathResolver {
	private $baseMappingResult;
	private $form;
	private $n2nContext;
	private $dispatchModelManager;
	private $arrayIndexCounters = array();
	
	private $propertyPath;
	private $donePathParts;
	private $nextPathParts;
	private $allowedPropertyClassNames;
	private $arrayRequired;	
	/**
	 * @param MappingResult $baseMappingResult
	 * @param N2nContext $n2nContext
	 */
	public function __construct(Form $form, MappingResult $baseMappingResult) {
		$this->baseMappingResult = $baseMappingResult;
		$this->form = $form;
		$this->n2nContext = $form->getView()->getN2nContext();
		$this->dispatchModelManager = $this->n2nContext->lookup(DispatchContext::class)
				->getDispatchModelManager();
	}
	
	public function getN2nContext() {
		return $this->n2nContext;
	}
	/**
	 * @return MappingResult
	 */
	public function getBaseMappingResult() {
		return $this->baseMappingResult;
	}
	/**
	 * @param PropertyPath $propertyPath
	 * @param array $allowedPropertyClassNames
	 * @param string $arrayRequired
	 * @throws IllegalStateException
	 * @return \n2n\web\dispatch\map\AnalyzerResult
	 */
	public function analyze(PropertyPath $propertyPath, array $allowedPropertyClassNames = null, 
			$arrayRequired = null, $prepareForm = true): AnalyzerResult {
		if ($propertyPath->isEmpty()) {
			throw new \InvalidArgumentException('Passed PropertyPath is empty.');
		}
		
		$this->propertyPath = $propertyPath;
		$this->donePathParts = array();
		$this->nextPathParts = $propertyPath->toArray();
		$this->allowedPropertyClassNames = $allowedPropertyClassNames;
		$this->arrayRequired = $arrayRequired;
		$result = null;
		try {
			$result = $this->analyzeNextPart($this->baseMappingResult, $prepareForm);
		} catch (DispatchException $e) {
			throw $this->decorateException($e);
		}
		$this->propertyPath = null;
		$this->donePathParts = null;
		$this->nextPathParts = null;
		$this->allowedPropertyClassNames = null;
		$this->arrayRequired = null;
		return $result;
	}
	
	private function decorateException(\Exception $e) {
		return new UnresolvablePropertyPathException('Cannot resolve property path: ' 
				. $this->propertyPath->__toString(), 0, $e);
	}
		
	private function analyzeNextPart(MappingResult $mappingResult, $prepareForm): AnalyzerResult {
		$pathPart = array_shift($this->nextPathParts);
		$this->donePathParts[] = $pathPart;
		
		$dispatchModel = $this->dispatchModelManager->getDispatchModel($mappingResult->getObject());
		$propertyName = $pathPart->getPropertyName();
		$managedProperty = $dispatchModel->getPropertyByName($propertyName);
		
		if ($pathPart->isArray()) {
			if (!$managedProperty->isArray()) {
				$propertyPath = new PropertyPath($this->donePathParts);
				throw new UnresolvablePropertyPathException('Property expression ' 
						. $propertyPath->__toString() . ' could not be resolved because property ' 
						. $managedProperty->getName() . ' of ' . get_class($mappingResult->getObject()) 
						. ' is no array.');
			}
			
			if (!$pathPart->isArrayKeyResolved()) {
				$pathPart->resolveArrayKey($this->createArrayKey());
			} else {
				$this->registerArrayKey($pathPart->getResolvedArrayKey());
			}
		}
		
		$managedProperty->resolveMapValue($pathPart, $mappingResult, $this->n2nContext);
		
		if (0 == sizeof($this->nextPathParts)) {
			$this->validateType($mappingResult, $managedProperty);
			$this->validateArray($mappingResult, $managedProperty);
			$analyzerResult = new AnalyzerResult($mappingResult, $managedProperty, $this->propertyPath);
			
			if ($prepareForm) $managedProperty->prepareForm($this->form, $analyzerResult);
			
			return $analyzerResult;
		}
			
		$mapValue = $mappingResult->__get($propertyName);
		$mappingResult = $this->determineNextMappingResult($mappingResult, $managedProperty, 
				$pathPart, $mapValue);
		if ($prepareForm) $managedProperty->prepareForm($this->form);
		return $this->analyzeNextPart($mappingResult, $managedProperty);
	}
	
	private function validateType(MappingResult $mappingResult, ManagedProperty $managedProperty) {
		if ($this->allowedPropertyClassNames === null) return;
		
		foreach ($this->allowedPropertyClassNames as $propertyClassName) {
			if (is_a($managedProperty, $propertyClassName)) return;
		}
		
		throw new PropertyTypeMissmatchException(get_class($mappingResult->getObject()) . ' - ' 
				. $managedProperty->getName() . ' is a \'' . get_class($managedProperty) 
				. '\', only following types allowed: ' . implode(', ', $this->allowedPropertyClassNames));
	}

	private function validateArray(MappingResult $mappingResult, ManagedProperty $managedProperty) {
		$lastPathPart = $this->propertyPath->getLast();
		if ($lastPathPart->isArray()) { 
			if ($this->arrayRequired) {
				throw new PropertyTypeMissmatchException(get_class($mappingResult->getObject()) . ' - '
						. $lastPathPart->__toString() . ' is no array.');
			}
			return;
		}
		
		if ($this->arrayRequired !== null && $this->arrayRequired !== $managedProperty->isArray()) {
			throw new PropertyTypeMissmatchException(get_class($mappingResult->getObject()) . ' - '
					. $lastPathPart->__toString() . ' is an array, non-array required.');
		}
	}
	
	private function createArrayKey() {
		$propertyPath = new PropertyPath($this->donePathParts);
		$key = $propertyPath->__toString();
		if (!isset($this->arrayIndexCounters[$key])) {
			$this->arrayIndexCounters[$key] = array();
		}
		$this->arrayIndexCounters[$key][] = 1;
		end($this->arrayIndexCounters[$key]);
		return key($this->arrayIndexCounters[$key]);
	}
	
	private function registerArrayKey($arrayKey) {
		$propertyPath = new PropertyPath($this->donePathParts);
		$key = $propertyPath->fieldExt(null)->__toString();
		if (!isset($this->arrayIndexCounters[$key])) {
			$this->arrayIndexCounters[$key] = array();
		}
		$this->arrayIndexCounters[$key][$arrayKey] = 1;
	}
	
	private function determineNextMappingResult(MappingResult $mappingResult, 
			ManagedProperty $managedProperty, PropertyPathPart $pathPart, $mapValue) {
		if (!$pathPart->isArray()) {
			if ($mapValue instanceof MappingResult) {
				return $mapValue;
			}
			
			if ($mapValue === null) {
				throw $this->createNotSetException($mappingResult, $managedProperty);
			}
			
			throw $this->createNoObjectPropertyException($mappingResult, $managedProperty);
		}
		
		$key = $pathPart->getResolvedArrayKey();
		if (!array_key_exists($key, $mapValue)) {
			throw $this->createNotSetException($mappingResult, $managedProperty);
		}
		
		if (!($mapValue[$key] instanceof MappingResult)) {
			throw $this->createNoObjectPropertyException($mappingResult, $managedProperty);
		}
		
		return $mapValue[$key];
	}
	
	private function createNotSetException(MappingResult $mappingResult, 
			ManagedProperty $managedProperty, \Exception $previous = null) {
		$propertyPath = new PropertyPath($this->donePathParts);
		throw new UnresolvablePropertyPathException('Property ' . $propertyPath->__toString() 
				. ' (' . get_class($mappingResult->getObject()) . ' - ' 
				. $managedProperty->getName() . ') is null.', 0, $previous);
	}
	
	private function createNoObjectPropertyException(MappingResult $mappingResult, 
			ManagedProperty $managedProperty) {
		$propertyPath = new PropertyPath($this->donePathParts);
		throw new UnresolvablePropertyPathException($propertyPath->__toString() . ' is not an object.' 
				. ' (' . get_class($mappingResult->getObject()) . ' - ' 
				. $managedProperty->getName() . ').');
	}
	
}
