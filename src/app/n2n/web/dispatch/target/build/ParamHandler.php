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
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\util\type\ArgUtils;
use n2n\util\StringUtils;

class ParamHandler {
	const TYPE_PROPERTY = 'prop-';
	const TYPE_ATTR = 'opt-';
	const TYPE_PROPERTY_TARGET = 'property-target';
	
	const DISPATCH_TARGET_NAME = 'dispatch-target';
	const METHOD_PREFIX = 'meth-';
	/**
	 *
	 * @param PropertyPath $propertyPath
	 */
	public static function build(string $type, PropertyPath $propertyPath, bool $useEmptyBrackets, 
			string $optionExt = null) {
		if ($type == self::TYPE_PROPERTY_TARGET) {
			return $type . '[]';
		}
		
		$pathParts = $propertyPath->toArray();
		$firstPathPart = array_shift($pathParts);
		$paramName = $type . $firstPathPart->getPropertyName()
				. self::buildArrayExt($firstPathPart, !$useEmptyBrackets || !empty($pathParts));
	
		while (null !== ($pathPart = array_shift($pathParts))) {
			$paramName .= '[' . $pathPart->getPropertyName() . ']'
					. self::buildArrayExt($pathPart, !$useEmptyBrackets || !empty($pathParts));
		}
		
		if ($optionExt !== null) {
			ArgUtils::assertTrue(PropertyPathPart::isNameValid($optionExt));
			$paramName .= '[' . $optionExt . ']';
		}
	
		return $paramName;
	}
	
	private static function buildArrayExt(PropertyPathPart $pathPart, $useResolvedArrayKey) {
		if ($useResolvedArrayKey && $pathPart->isArrayKeyResolved()) {
			return '[' . $pathPart->getResolvedArrayKey() . ']';
		}

		if ($pathPart->isArray()) {
			return '[' . $pathPart->getArrayKey() . ']';
		}
	
		return '';
	}
	
	public static function buildDispatchTargetName() {
		return self::DISPATCH_TARGET_NAME;
	}
	
	public static function buildForMethod($methodName) {
		return self::METHOD_PREFIX . $methodName;
	}
	
	/**
	 * @param array $params
	 * @return string
	 */
	public static function extractMethodName(array $params) {
		foreach ($params as $name => $value) {
			if (!StringUtils::startsWith(self::METHOD_PREFIX, $name)) continue;
			
			return mb_substr($name, mb_strlen(self::METHOD_PREFIX));
		}
		
		return null;
	}
	
	/**
	 * @param array $params
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public static function extractPropertyTargetCodes(array $params) {
		if (!isset($params[self::TYPE_PROPERTY_TARGET])) return array();
		
		ArgUtils::valArrayLike($params[self::TYPE_PROPERTY_TARGET], 'scalar');
		
		return $params[self::TYPE_PROPERTY_TARGET];
	}
	
	/**
	 * @param array $params
	 * @return string
	 * @throws \InvalidArgumentException
	 */						
	public static function extractDispatchTargetCode(array $params) {
		if (isset($params[self::DISPATCH_TARGET_NAME])) {
			ArgUtils::valType($params[self::DISPATCH_TARGET_NAME], 'string');
			return $params[self::DISPATCH_TARGET_NAME];
		}
		
		return null;
	}
	
	public static function extractParamInvestigator(array $params, array $uploadDefinitions) {
		$propertyValues = array();
		$propertyUploadDefinitions = array();
		$attrs = array();

		$propertyStrLen = mb_strlen(self::TYPE_PROPERTY);
		$attrStrLen = mb_strlen(self::TYPE_ATTR);
		
		foreach ($params as $name => $value) {
			if (StringUtils::startsWith(self::TYPE_PROPERTY, $name)) {
				$propertyValues[mb_substr($name, $propertyStrLen)] = $value;
				continue;
			}
			
			if (StringUtils::startsWith(self::TYPE_ATTR, $name)) {
				$attrs[mb_substr($name, $attrStrLen)] = $value;
				continue;
			}
		}

		foreach ($uploadDefinitions as $name => $value) {
			if (StringUtils::startsWith(self::TYPE_PROPERTY, $name)) {
				$propertyUploadDefinitions[mb_substr($name, $propertyStrLen)] = $value;
				continue;
			}
		}
		
		return new ParamInvestigator($propertyValues, $propertyUploadDefinitions, $attrs);
	}
	
	public static function extract(array $params) {
		$results = array();
		foreach ($params as $name => $value) {
			if (StringUtils::startsWith(self::TYPE_PROPERTY, $name)) {
				array_merge($results, self::buildResults(PropertyPath::create(array(new PropertyPathPart()))));
				continue;
			} 
			
			if (StringUtils::startsWith(self::TYPE_ATTR, $name)) {
				
			}
		}	
	}
	
// 	public static function buildResults(PropertyPath $propertyPath, $value) {
		
// 	}
	
// 	public static function extractParams(PropertyPath $propertyPath, array $params) {
// 		return self::findParam($propertyPath->toArray(), $params);
// 	}
	
// 	private static function findParam(array $pathParts, $params) {
// 		if (empty($pathParts)) {
// 			return $params;
// 		}
		
// 		if (!is_array($params)) {
// 			return null;
// 		}
	
// 		$pathPart = array_shift($pathParts);
// 		$name = $pathPart->getPropertyName();
		
// 		if (!isset($params[$name])) {
// 			return null;
// 		}
		
// 		$value = null;
// 		if (!$pathPart->isArray()) {
// 			return $this->findParam($pathParts, $params[$name]);
// 		} 
		
// 		$key = $pathPart->getResolvedArrayKey();
// 		if (is_array($params[$name]) && isset($params[$name][$key])) {
// 			return $this->findParam($pathParts, $params[$name][$key]);
// 		}
		
// 		return null;
// 	}
}
