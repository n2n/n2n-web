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
namespace n2n\web\dispatch\property\impl;

use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\target\ObjectItem;
use n2n\web\dispatch\target\ObjectArrayItem;
use n2n\web\dispatch\map\CorruptedDispatchException;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\map\MappingResult;
use n2n\util\col\ArrayUtils;
use n2n\reflection\ArgUtils;
use n2n\reflection\property\TypeConstraint;
use n2n\web\dispatch\map\bind\ObjectMapper;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\web\dispatch\map\PropertyPath;
use n2n\reflection\magic\CanNotFillParameterException;
use n2n\web\dispatch\DispatchErrorException;
use n2n\web\dispatch\target\build\ParamInvestigator;
use n2n\web\dispatch\target\DispatchTargetException;
use n2n\reflection\ReflectionUtils;
use n2n\web\dispatch\DispatchContext;

class ObjectProperty extends ManagedPropertyAdapter {
	const CREATOR_KEY_PARAM = 'key';
	const OPTION_OBJECT_ACTIVATED = 'optAct';
	const OPTION_OBJECT_ENABLED = 'objEna';
	
	private $creator;
	
	public function __construct($accessProxy, $array, $useArrayObject = null) {
		$constraints = $accessProxy->getConstraint();
		if (!$array && ($constraints->isEmpty() || !is_subclass_of($constraints->getTypeName(), 
				'n2n\web\dispatch\Dispatchable'))) {
			CommonManagedPropertyProvider::restrictConstraints($accessProxy, 
					'n2n\web\dispatch\Dispatchable', $array, $useArrayObject);
		}
		
		if ($array) {
			$arryFieldTypeConstraint = $constraints->getArrayFieldTypeConstraint();
			if ($arryFieldTypeConstraint === null || $arryFieldTypeConstraint->isEmpty()
					|| !is_subclass_of($arryFieldTypeConstraint->getTypeName(), Dispatchable::class)) {
			 	CommonManagedPropertyProvider::restrictConstraints($accessProxy, 
					'n2n\web\dispatch\Dispatchable', $array, $useArrayObject);
			 	
			}
		}
		
		if (!$array) {
			$this->mapTypeConstraint = TypeConstraint::createSimple('n2n\web\dispatch\map\MappingResult');
		} else {
			$type = $accessProxy->getConstraint()->getTypeName();
			if ($type === null) {
				$type = ($useArrayObject ? 'ArrayObject' : 'array');
			}
			
			$this->mapTypeConstraint = TypeConstraint::createArrayLike($type, 
					false, TypeConstraint::createSimple('n2n\web\dispatch\map\MappingResult'));
		}
		
		$accessProxy->setNullReturnAllowed(true);
		
		parent::__construct($accessProxy, $array);
		$this->useArrayObject = $useArrayObject;
	}
	/**
	 * @param \Closure $creator
	 */
	public function setCreator(\Closure $creator = null) {
		$this->creator = $creator;
	}
	/**
	 * @return \Closure
	 */
	public function getCreator() {
		return $this->creator;
	}
	
	public function dispatch(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {
		
		$mappingResult = $bindingDefinition->getMappingResult();
		
		if (!$this->isArray()) {
			try {
				$propertyObjectItem = $objectItem->createObjectItem($this->getName());
				
				$mappingResult->__set($this->getName(),
						$this->dispatchObject($propertyObjectItem, $bindingDefinition, $paramInvestigator, $n2nContext));
				return;
			} catch (DispatchTargetException $e) {
				throw new CorruptedDispatchException('No matching object item.', 0, $e);
			}
		}
		
		try {
			$propertyObjectArrayItem = $objectItem->createObjectArrayItem($this->getName());

			$mappingResult->__set($this->getName(), $this->dispatchObjectArray($propertyObjectArrayItem, 
					$paramInvestigator, $bindingDefinition, $n2nContext));
		} catch (DispatchTargetException $e) {
			throw new CorruptedDispatchException('No matching object array item.', 0, $e);
		}
	}
	
	private function dispatchObject(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {
		$mappingResult = $bindingDefinition->getMappingResult();
		
		$propertyPath = null;
		if (null !== ($bdPropertyPath = $bindingDefinition->getPropertyPath())) {
			$propertyPath = $bdPropertyPath->ext($this->getName());
		} else {
			$propertyPath = new PropertyPath(array(new PropertyPathPart($this->getName())));
		}
		
		$currentValue = $this->readValue($mappingResult->getObject());
		
		if ($this->creator !== null && null !== $objectItem->getAttr(self::OPTION_OBJECT_ACTIVATED)) {
			if (!$paramInvestigator->findAttr($propertyPath, self::OPTION_OBJECT_ENABLED)) {
				return null;
			}
			
			if ($currentValue === null) {
				$currentValue = $this->createDispatchable($mappingResult, $n2nContext);
			}
		} 
		
		if ($currentValue === null) return null;
		
		$objectMapper = new ObjectMapper($objectItem, null, $propertyPath);
		return $objectMapper->createMappingResult($currentValue,
				$bindingDefinition->getBindingTree(), $paramInvestigator, $n2nContext);
	}
	
	private function dispatchObjectArray(ObjectArrayItem $objectArrayItem, 
			ParamInvestigator $paramInvestigator, BindingDefinition $bindingDefinition, N2nContext $n2nContext) {
		$mappingResult = $bindingDefinition->getMappingResult();
		$currentValue = $this->readValue($mappingResult->getObject());
		if ($currentValue === null) {
			$currentValue = array();
		}
		
		$mapValue = $this->createEmptyValue();
		$propertyPath = null;
		if (null !== ($bdPropertyPath = $bindingDefinition->getPropertyPath())) {
			$propertyPath = $bdPropertyPath->ext($this->getName());
		} else {
			$propertyPath = new PropertyPath(array(new PropertyPathPart($this->getName())));
		}
				
		if ($this->creator === null || null === $objectArrayItem->getAttr(self::OPTION_OBJECT_ACTIVATED)) {
			foreach ($currentValue as $key => $valueField) {
				$objectMapper = new ObjectMapper($objectArrayItem->createObjectItem($key), null, 
						$propertyPath->fieldExt($key));
				$mapValue[$key] = $objectMapper->createMappingResult($valueField,
						$bindingDefinition->getBindingTree(), $paramInvestigator, $n2nContext);
			}
			return $mapValue;
		}
		
		foreach ($objectArrayItem->getObjectItems() as $key => $objectItem) {
			if (null === $paramInvestigator->findAttr($propertyPath->fieldExt($key), self::OPTION_OBJECT_ENABLED)) continue;

			$objectMapper = new ObjectMapper($objectItem, null, $propertyPath->fieldExt($key));
			$dispatchable = null;
			if (isset($currentValue[$key])) {
				$dispatchable = $currentValue[$key];
			} else {
				$dispatchable = $this->createArrayFieldDispatchable($key, $mappingResult, $n2nContext);
			}
			
			if ($dispatchable === null) continue;
	
			$mapValue[$key] = $objectMapper->createMappingResult($dispatchable,
					$bindingDefinition->getBindingTree(), $paramInvestigator, $n2nContext);
		}
		
		return $mapValue;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::convertValueToMapValue()
	 */
	public function writeValueToMappingResult($value, MappingResult $mappingResult, N2nContext $n2nContext) {
		if ($this->isArray()) {
			$mapValue = $this->createEmptyValue();
			
			if ($value !== null) {
				ArgUtils::valArrayLike($value);
				foreach ($value as $key => $valueField) {
					$mapValue[$key] = $this->createMappingResult($valueField, $n2nContext);
				}
			}
			 
			$mappingResult->__set($this->getName(), $mapValue);
			return;
		}

		if ($value !== null) {
			$mappingResult->__set($this->getName(), $this->createMappingResult($value, $n2nContext));
			return;
		}
		
		$mappingResult->__set($this->getName(), null);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\property\ManagedProperty::createMapValueField()
	 */
	public function resolveMapValue(PropertyPathPart $pathPart, MappingResult $mappingResult, N2nContext $n2nContext) {
		$mapValue = null;
		if (!$mappingResult->containsPropertyName($this->name)) {
			$this->writeValueToMappingResult($this->readValue($mappingResult->getObject()), 
					$mappingResult, $n2nContext);
		}
		
		if ($this->creator === null) return;
		
		$mapValue = $mappingResult->__get($this->name);	
		if (!$this->isArray()) {
			if ($mapValue !== null) return;
			
			$dispatchable = $this->createDispatchable($mappingResult, $n2nContext);
			if ($dispatchable === null) {
				$mapValue = null;
			} else {
				$mapValue = $this->createMappingResult($dispatchable, $n2nContext);
				$mapValue->setAttrs(true);
			}
			$mappingResult->__set($this->name, $mapValue);
			return;
		} 
		
		if ($pathPart->isArray() && !array_key_exists($pathPart->getResolvedArrayKey(), $mapValue)) {
			$key = $pathPart->getResolvedArrayKey();
			$dispatchable = $this->createArrayFieldDispatchable($key, $mappingResult, $n2nContext);
			if ($dispatchable === null) return;
			$mapValue[$key] = $this->createMappingResult($dispatchable, $n2nContext);
			$mapValue[$key]->setAttrs(true);
			$mappingResult->__set($this->name, $mapValue);
		}
	}
	
	private function createDispatchable(MappingResult $mappingResult, N2nContext $n2nContext) {
		$invoker = new MagicMethodInvoker($n2nContext);
		$invoker->setClassParamObject($mappingResult->getDispatchModel()->getClass()->getName(),
				$mappingResult->getObject());
		$dispatchable = null;
		try {
			$dispatchable = $invoker->invoke(null, new \ReflectionFunction($this->creator));
		} catch (CanNotFillParameterException $e) {
			$func = new \ReflectionFunction($this->creator);
			throw new DispatchErrorException('Failed to call closure: ' . ReflectionUtils::prettyReflMethName($func),
					$func->getFileName(), $func->getStartLine(), null, null, $e);
		}
		
		ArgUtils::valTypeReturn($dispatchable, $this->accessProxy->getConstraint(), null, $this->creator, true);
		
		return $dispatchable;
	}
		
	private function createArrayFieldDispatchable($key, MappingResult $mappingResult, N2nContext $n2nContext) {
		$invoker = new MagicMethodInvoker($n2nContext);
		$invoker->setParamValue(self::CREATOR_KEY_PARAM, $key);
		$invoker->setClassParamObject($mappingResult->getDispatchModel()->getClass()->getName(),
				$mappingResult->getObject());
		
		$dispatchable = null;
		$function = new \ReflectionFunction($this->creator);
		try {
			$dispatchable = $invoker->invoke(null, $function);
			if ($dispatchable === null) return null;
			ArgUtils::valTypeReturn($dispatchable, array('null', Dispatchable::class), null, $function);
		} catch (CanNotFillParameterException $e) {
			throw new DispatchErrorException('Invalid creator closure.', $function->getFileName(), $function->getStartLine(),
					null, null, $e);
		}
		
		if (null !== ($constraints = $this->accessProxy->getConstraint()
				->getArrayFieldTypeConstraint())) {
					
			ArgUtils::valTypeReturn($dispatchable, $constraints, null, $this->creator);
		}
		
		return $dispatchable;
	}
	
	private function createMappingResult($value, N2nContext $n2nContext) {
		ArgUtils::assertTrue($value instanceof Dispatchable);
		
		return $n2nContext->lookup(DispatchContext::class)->getDispatchModelManager()
				->getDispatchModel($value)->getDispatchItemFactory()->createMappingResult($value, $n2nContext);
	}

	public function readValueFromMappingResult(MappingResult $mappingResult, N2nContext $n2nContext) {
		$mapValue = $mappingResult->__get($this->getName());
		
		if (!$this->isArray()) {
			if ($mapValue === null) return null;
			ArgUtils::assertTrue($mapValue instanceof MappingResult);
			return $mapValue->getObject();	
		}
		
		ArgUtils::assertTrue(ArrayUtils::isArrayLike($mapValue));
		$value = $this->createEmptyValue();
		foreach ($mapValue as $key => $mapValueField) {
			ArgUtils::assertTrue($mapValueField instanceof MappingResult);
			$value[$key] = $mapValueField->getObject();
		}
		return $value;
	}
}
