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

use n2n\web\dispatch\target\DispatchTargetException;
use n2n\web\dispatch\target\DispatchTarget;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\InvalidPropertyExpressionException;
use n2n\web\dispatch\target\TargetItem;
use n2n\web\dispatch\map\CorruptedDispatchException;

class DispatchTargetExtractor {
	private $coder;
	private $params = array();
	private $uploadDefinitions = array();
	
	private $dispatchTarget;
	private $executedMethodName;
	
	public function __construct(DispatchTargetCoder $coder) { 
		$this->coder = $coder;	
	}
	
	public function setParams(array $params) {
		$this->params = $params;
	}
	
	public function getParams() {
		return $this->params;
	}
	
	public function setUploadDefinitions(array $uploadDefinitions) {
		$this->uploadDefinitions = $uploadDefinitions;
	}
	
	public function getUploadDefinitions() {
		return $this->uploadDefinitions;
	}
	
	public function getDispatchTarget() {
		return $this->dispatchTarget;
	}
	
	public function getExecutedMethodName() {
		return $this->executedMethodName;
	}
	
	public function extractDispatchTarget() {
		$code = ParamHandler::extractDispatchTargetCode($this->params);
		if ($code === null) return;
		
		$props = $this->coder->decode($code);
		if (!isset($props[Prop::KEY_DISPATCH_CLASS_NAME]) || !is_string($props[Prop::KEY_DISPATCH_CLASS_NAME])) {
			throw $this->createException('Invalid dispatch class name.');
		}
		
		$this->dispatchTarget = new DispatchTarget($props[Prop::KEY_DISPATCH_CLASS_NAME]);
		
		$codes = null;
		try {
			$codes = ParamHandler::extractPropertyTargetCodes($this->params);
		} catch (\InvalidArgumentException $e) {
			throw new CorruptedDispatchException('Invalid dispatch targets', 0, $e);
		}
	
		foreach ($codes as $code) {
			$props = $this->coder->decode($code);
				
			try {
				$this->applyItem($props);
			} catch (DispatchTargetException $e) {
				throw $this->createException('Target item conflict.', $e);
			}	
		}
		
		$this->executedMethodName = ParamHandler::extractMethodName($this->params);
		
		return $this->dispatchTarget;
	}
	
	public function extractParamInvestigator() {
		return ParamHandler::extractParamInvestigator($this->params, $this->uploadDefinitions);
	}

// 	public function applyParams() {
// 		$methodName = null;
	
// 		foreach (ParamHandler::extract($this->params) as $result) {
			
// 		}
		
		
		
// 		foreach ($this->params as $paramName => $value) {
// 			if (StringUtils::startsWith(self::TYPE_PROPERTY, $paramName)
// 					&& null !== ($field = $this->objectItem->getField(mb_substr($paramName, mb_strlen(self::TYPE_PROPERTY))))) {
// 						$this->applyValue($field, $value);
// 						continue;
// 					}
						
// 					if (StringUtils::startsWith(self::TYPE_OPTION, $paramName) && is_array($value)
// 							&& null !== ($field = $this->objectItem->getField(mb_substr($paramName, mb_strlen(self::TYPE_OPTION))))) {
// 								$this->applyCustomOptions($field, $value);
// 								continue;
// 							}
								
// 							if (StringUtils::startsWith(self::TYPE_METHOD, $paramName)) {
// 								// @todo throw UnexpectedOptionException if there are unregistered method names
// 								$methodName = mb_substr($paramName, mb_strlen(self::TYPE_METHOD));
// 							}
// 		}
	
// 		foreach ($uploadDefinitions as $paramName => $uploadDefinition) {
// 			if (StringUtils::startsWith(self::TYPE_PROPERTY, $paramName)
// 					&& null !== ($field = $this->objectItem->getField(mb_substr($paramName, mb_strlen(self::TYPE_PROPERTY))))) {
// 						$this->applyUploadDefinition($field, $uploadDefinition);
// 						continue;
// 					}
// 		}
// 	}
	
	public function applyItem($props) {
		if (!isset($props[Prop::KEY_TYPE])) {
			throw $this->createException('Unknown type.');
		}	
		
		$propertyPath = null;
		try {
			$propertyPathStr = $this->extractScalar(Prop::KEY_PROPERTY_PATH, $props);
			$propertyPath = PropertyPath::createFromPropertyExpression($propertyPathStr);
		} catch (InvalidPropertyExpressionException $e) {
			throw $this->createException('Invalid property path given.', 0, $e);
		}
		
		$targetItem = null;
		switch ($props[Prop::KEY_TYPE]) {
			case Prop::TYPE_PROPERTY:
				$propertyItem = $this->dispatchTarget->registerProperty($propertyPath);
// 				$this->applyPropertyValue($props, $propertyItem);
				$this->applyAttrs($props, $propertyItem);
				return;
			case Prop::TYPE_ARRAY:
				$arrayItem = $this->dispatchTarget->registerArray($propertyPath);
// 				$this->applyArrayValue($props, $arrayItem);
				$this->applyAttrs($props, $arrayItem);
				return;
			case Prop::TYPE_OBJECT:
				$objectItem = $this->dispatchTarget->registerObject($propertyPath);
				$this->applyAttrs($props, $objectItem);
				return;
			case Prop::TYPE_OBJECT_ARRAY:
				$objectArrayItem = $this->dispatchTarget->registerObjectArray($propertyPath);
				$this->applyAttrs($props, $objectArrayItem);
				return;
			default:
				throw $this->createException('Invalid target item');
		}
	}
	
	private function applyAttrs($props, TargetItem $targetItem) {
		$attrs = array();
		if (isset($props[Prop::KEY_ATTRS])) {
			$attrs = $props[Prop::KEY_ATTRS];
		}

// 		foreach ($this->extractArray(Prop::KEY_EXTERNAL_ATTR_NAMES, $props) as $name) {
// 			$targetItem->registerExternalAttrName($name);
// 			$attrs[$name] = ParamHandler::findExternalAttr($targetItem->getPropertyPath(), $name, $this->params);
// 		}
		
		try {
			$targetItem->setAttrs($attrs);
			return;
		} catch (\InvalidArgumentException $e) {}
		
		throw $this->createException('Invalid attrs');
	}
	
// 	private function applyPropertyValue($props, PropertyItem $propertyItem) {
// 		$propertyPath = $propertyItem->getPropertyPath();
		
// 		$propertyItem->setValue(ParamHandler::extractParams($propertyPath, $this->params));
		
// 		$uploadDefinition = ParamHandler::extractParam($propertyPath, $this->uploadDefinitions);
// 		if ($uploadDefinition !== null && !($uploadDefinition instanceof UploadDefinition)) {
// 			throw $this->createException('Invalid upload definition.');
// 		}
// 		$propertyItem->setUploadDefinition($uploadDefinition);
// 	}
	
// 	private function applyArrayValue($props, ArrayItem $arrayItem) {
// 		$propertyPath = $arrayItem->getPropertyPath();
		
// 		$arrayItem->setValue(ParamHandler::extractParam($propertyPath, $this->params));
		
// 		$uploadDefinitions = ParamHandler::extractParam($propertyPath, $this->getUploadDefinitions());
// 		if ($uploadDefinitions === null) return;
		
// 		try {
// 			ArgUtils::valArray($uploadDefinitions, 'n2n\web\http\UploadDefinition');
// 		} catch (\InvalidArgumentException $e) {
// 			throw $this->createException('Invalid upload definition.', $e);
// 		}
		
// 		foreach ($uploadDefinitions as $key => $uploadDefinition) {
// 			$propertyItem = $arrayItem->createPropertyItem($key);
// 			$propertyItem->setUploadDefinition($uploadDefinition);
// 		}
// 	}
	
	private function extractScalar($key, array $props) {
		if (!isset($props[$key])) {
			return null;
		}
		
		if (is_scalar($props[$key])) {
			return $props[$key];
		}
		
		throw $this->createException('Invalid key' . $key);
	}
	
	private function extractArray($key, array $props) {
		if (!isset($props[$key])) {
			return array();
		}
		
		if (is_array($props[$key])) {
			return $props[$key];
		}
	
		throw $this->createException('Invalid key' . $key);
	}
			
	private function createException($reason, \Exception $e = null) {
		return new DispatchTargetDecodingException('Corrupted dispatch target. Reason: ' . $reason, 0, $e);
	}			
			
				
// 			if ($props[self::PROP_TYPE_KEY] == self::TYPE_METHOD) {
// 				$dispatchTarget->registerMethod($this->extractString(self::TYPE_METHOD_NAME));
// 			}
				
// 			switch ($props[self::PROP_TYPE_KEY]) {
// 				case self::TYPE_PROPERTY:
// 					$dispatchTarget->registerProperty($propertyPath);
// 					break;
// 				case self::TYPE_ARRAY:
// 					break;
// 				case self::TYPE_OBJECT:
// 					break;
// 				case self::TYPE_OBJECT_ARRAY:
// 					break;
	
// 			}
}
