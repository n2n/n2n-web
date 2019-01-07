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
namespace n2n\web\dispatch\target;

use n2n\web\dispatch\map\PropertyPath;

class DispatchTarget {
// 	const PARAM_TYPE_OPTION = 'opt-'; 	
// 	const PARAM_CLASS_NAME = 'className';
// 	const PARAM_PROPS = 'props';
// 	const PARAM_METHOD_NAMES = 'methods';
		
	private $dispatchClassName;
	private $objectItem;
// 	private $methodItems;
	
	/**
	 * @param string $dispatchableClassName
	 * @param bool $unbounded
	 */
	public function __construct($dispatchableClassName) {
		$this->dispatchClassName = (string) $dispatchableClassName;
		$this->objectItem = new ObjectItem(new PropertyPath(array()));
// 		$this->methodItems = array();
	}
	
	/**
	 * @return string
	 */
	public function getDispatchClassName() {
		return $this->dispatchClassName;
	}
	
	/**
	 * @return ObjectItem
	 */
	public function getObjectItem() {
		return $this->objectItem;
	}
	
// 	/**
// 	 * 
// 	 * @return MethodItem[]
// 	 */
// 	public function getMethodItems() {
// 		return $this->methodItems;
// 	}
	
	/**
	 * 
	 * @param PropertyPath $propertyPath
	 * @return string
	 */
	public function registerProperty(PropertyPath $propertyPath) {
		$pathParts = $propertyPath->toArray();
		$lastPathPart = array_pop($pathParts);
		$objectItem = $this->extendObjectItem($this->objectItem, $pathParts, array());
		
		if (!$lastPathPart->isArray()) {
			return $objectItem->createPropertyItem($lastPathPart->getPropertyName());
		} 
	
		$arrayItem = $objectItem->createArrayItem($lastPathPart->getPropertyName());
		return $arrayItem->createPropertyItem($lastPathPart->getResolvedArrayKey());
	}
	
	public function registerArray(PropertyPath $propertyPath) {
		$pathParts = $propertyPath->toArray();
		$lastPathPart = array_pop($pathParts);
		$objectItem = $this->extendObjectItem($this->objectItem, $pathParts, array());
		
		if ($lastPathPart->isArray()) {
			throw new PropertyPathMissmatchException('Multidimensional arrays not supported.');
		}
		
		return $objectItem->createArrayItem($lastPathPart->getPropertyName());
	}
	
	public function registerObject(PropertyPath $propertyPath, $array = false) {
		return $this->extendObjectItem($this->objectItem, $propertyPath->toArray(), array());
	}
	
	public function registerObjectArray(PropertyPath $propertyPath) {
		$pathParts = $propertyPath->toArray();
		$lastPathPart = array_pop($pathParts);
		$objectItem = $this->extendObjectItem($this->objectItem, $pathParts, array());
		
		if ($lastPathPart->isArray()) {
			throw new PropertyPathMissmatchException('Multidimensional arrays not supported.');
		}
		
		return $objectItem->createObjectArrayItem($lastPathPart->getPropertyName());
	}
	/**
	 * 
	 * @param ObjectItem $contextObjectItem
	 * @param array $remainingPathParts
	 * @param array $parentPathParts
	 * @param bool $followUnbounded
	 * @return ObjectItem
	 */
	private function extendObjectItem(ObjectItem $contextObjectItem, array $remainingPathParts, array $parentPathParts) {
		if (!sizeof($remainingPathParts)) {
			return $contextObjectItem;
		}
		
		$pathPart = array_shift($remainingPathParts);
		$parentPathParts[] = $pathPart;
		
		$objectItem = null;
		if (!$pathPart->isArray()) {
			$objectItem = $contextObjectItem->createObjectItem($pathPart->getPropertyName());
		} else {
			$objectArrayItem = $contextObjectItem->createObjectArrayItem($pathPart->getPropertyName());
			$objectItem = $objectArrayItem->createObjectItem($pathPart->getResolvedArrayKey());
		}
		
		return $this->extendObjectItem($objectItem, $remainingPathParts, $parentPathParts);
	}
	
// 	public function registerCustomOption(PropertyPath $propertyPath, $name) {
// 		$dtItem = $this->getItemForOption($name, $propertyPath);
// 		$dtItem->registerCustomOption($name);
		
// 		return self::buildHttpParamName(self::TYPE_OPTION, $propertyPath) . '[' . self::TYPE_OPTION . $name . ']';
// 	}
	
// 	public function setOption(PropertyPath $propertyPath, $name, $value) {
// 		$dtItem = $this->getItemForOption($name, $propertyPath);
// 		$dtItem->setOption($name, $value);
// 	}
	
// 	private function getItemForOption($name, PropertyPath $propertyPath) {
// 		$dtItem = $this->findItem($propertyPath);
// 		if (is_null($dtItem)) {
// 			throw new PropertyPathMissmatchException(
// 					SysTextUtils::get('n2n_error_model_dispatch_target_property_not_registered',
// 							array('path' => $propertyPath->__toString())));
// 		}
		
// 		if ($dtItem->hasOption($name) || $dtItem->hasRegisteredCustomOption($name)) {
// 			throw new OptionAmbiguousException(
// 					SysTextUtils::get('n2n_error_model_dispatch_target_option_was_already_set',
// 							array('option' => $name, 'path' => $propertyPath->__toString())));
// 		}
		
// 		return $dtItem;
// 	}
	
// 	/**
// 	 * 
// 	 * @param PropertyPath $propertyPath
// 	 * @return TargetItem
// 	 */
// 	public function findItem(PropertyPath $propertyPath)  {
// 		return $this->findItemR($this->objectItem, $propertyPath->toArray(), array());
// 	}
	
// 	private function findItemR(ObjectItem $dtItem, array $remainingPathParts, array $parentPathParts) {
// 		$pathPart = array_shift($remainingPathParts);
		
// // 		if (!($dtItem instanceof ObjectItem)) {
// // 			throw new PropertyPathMissmatchException(
// // 					SysTextUtils::get('n2n_error_model_dispatch_target_path_references_to_non_object_item',
// // 							array('path' => PropertyPath::implodePathParts($parentPathParts))));
// // 		}
		
// 		if (!$dtItem->hasField($pathPart->getPropertyName())) {
// 			return null;
// 		}
		
// 		$nextItem = null;
// 		if (!$pathPart->isArray()) {
// 			$nextItem = $dtItem->getField($pathPart->getPropertyName());
// 		} else {
// 			$arrItem = $dtItem->getField($pathPart->getPropertyName());
			
// 			if (!($arrItem instanceof ArrayTargetItem)) {
// 				$parentPathParts[] = new PropertyPathPart($pathPart->getPropertyName(), false, null);
// 				throw new PropertyPathMissmatchException(
// 						SysTextUtils::get('n2n_error_model_dispatch_target_path_references_to_non_array_item',
// 								array('path' => PropertyPath::implodePathParts($parentPathParts))));
// 			}
			
// 			if (!$pathPart->isArrayKeyResolved() && !sizeof($remainingPathParts)) {
// 				return $arrItem;
// 			}
			
// 			$key = $pathPart->getResolvedArrayKey();
// 			if (!$arrItem->hasField($key)) {
// 				return null;
// 			}
			
// 			$nextItem = $arrItem->getField($key);
// 		}
		
// 		if (sizeof($remainingPathParts)) {
// 			$parentPathParts[] = $pathPart;
// 			return $this->findItemR($nextItem, $remainingPathParts, $parentPathParts);
// 		}
		
// 		return $nextItem;
// 	}
	
// 	private function applyValue(TargetItem $targetItem, $value) {
// 		if ($targetItem instanceof ValueItem) {
// 			$targetItem->setValue($value);
// 		}
		
// 		if ($targetItem instanceof BranchTargetItem && is_array($value)) {
// 			foreach ($targetItem->getFields() as $name => $targetItem) {
// 				if (!array_key_exists($name, $value)) continue;
					
// 				$this->applyValue($targetItem, $value[$name]);
// 			}
// 		}
// 	}
	
// 	private function applyUploadDefinition(TargetItem $targetItem, $uploadDefinition) {
// 		if ($targetItem instanceof ValueItem) {
// 			$targetItem->setUploadDefinition($uploadDefinition);
// 			return;
// 		}
		
// 		if ($targetItem instanceof BranchTargetItem) {
// 			if (!is_array($uploadDefinition)) {
// 				throw new UnexpectedParameterException('Corrupted file definition.');
// 			}
			
// 			foreach ($targetItem->getFields() as $key => $targetItem) {
// 				if (isset($uploadDefinition[$key])) {
// 					$this->applyUploadDefinition($targetItem, $uploadDefinition[$key]);
// 				}
// 			}
// 		}
// 	}
	
// 	private function applyCustomOptions(TargetItem $targetItem, array $values) {		
// 		foreach ($values as $name => $value) {
// 			if (StringUtils::startsWith(self::PARAM_TYPE_OPTION, $name)) {
// 				$optionName = mb_substr($name, mb_strlen(self::PARAM_TYPE_OPTION));
// 				if (!$targetItem->hasRegisteredCustomOption($optionName) || !is_scalar($value)) {
// 					throw new UnexpectedParameterException(
// 							SysTextUtils::get('n2n_error_model_dispatch_target_unexpected_option',
// 									array('name' => $optionName, 'value' => TypeUtils::getTypeInfo($value))));
// 				}
// 				$targetItem->setOption($optionName, $value);
// 				continue;
// 			}
			
// 			if (!($targetItem instanceof BranchTargetItem)) {
// 				throw new PropertyPathMissmatchException();
// 			}
			
// 			$this->applyCustomOptions($targetItem->getField($name), $value);
// 		}
// 	}
// 	/**
// 	 * 
// 	 * @return string
// 	 */
// 	public function toHttpParamValue() {
//  		$encoder = new DispatchTargetEncoder($this);
//  		if (self::CRYPT_ENABLED) {
//  			$encoder->setCipher(self::getCipherForEncryption());
//  		}
//  		return $encoder->encode();
// 	}
}
