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
namespace n2n\web\http\path;

use n2n\util\StringUtils;
use n2n\util\RegexSyntaxException;
use n2n\util\uri\Path;

class PathPattern {	
	private $extensionIncluded = true;
	private $allowedExtensions = null;
	private $patternDefs = array();
	private $multiple = false;
	
	public function setExtensionIncluded($extensionIncluded) {
		$this->extensionIncluded = (boolean) $extensionIncluded;
	}
	
	public function setAllowedExtensions(array $allowedExtensions = null) {
		$this->allowedExtensions = $allowedExtensions;
	}
	
	private function addPatternDef(array $customDef, $required, $multiple, $paramName) {
		if ($this->multiple) {
			throw new PathPatternComposeException('Cannot add path part after multiple modifier.');
		}
				
		$customDef['required'] = (boolean) $required;
		$this->multiple = $customDef['multiple'] = (boolean) $multiple;
		$customDef['paramName'] = $paramName;
		$this->patternDefs[] = $customDef;
	}
	
	public function addConstant($constant, $required, $multiple, $paramName = null) {
		$this->addPatternDef(array('constant' => (string) $constant), $required, $multiple, $paramName);
	}
	
	public function addWhitechar($required, $multiple, $paramName = null) {
		$this->addPatternDef(array('whitechar' => '*'), $required, $multiple, $paramName);
	}
	
	public function addRegex($pattern, $required, $multiple, $paramName = null) {
		$this->addPatternDef(array('regexPattern' => $pattern), $required, $multiple, $paramName);
	}
	
	public function addPlaceholder($name, PlaceholderValidator $validator, $required, $multiple, 
			$paramName = null) {
		$this->addPatternDef(array('placeholder' => $name, 'validator' => $validator), 
				$required, $multiple, $paramName);
	}
	
	public function size() {
		return count($this->patternDefs);
	}
	
	public function hasMultiple() {
		$lastPatternDef = end($this->patternDefs);
		return $lastPatternDef !== false && $lastPatternDef['multiple'];
	}
		
	private function matchesPatternDef(array $patternDef, $pathPart) {
		if (isset($patternDef['constant'])) {
			return $patternDef['constant'] === (string) $pathPart;
		} 
		
		if (isset($patternDef['placeholder'])) {
			return $patternDef['validator']->matches((string) $pathPart);
		}
		
		if (isset($patternDef['regexPattern'])) {
			try {
				return StringUtils::pregMatch($patternDef['regexPattern'], $pathPart);
			} catch (RegexSyntaxException $e) {
				throw new PathPatternComposeException('Error while using regex pattern: ' . $patternDef['regexPattern'], null, $e);
			}
		}
		
		// whitechar
		return true;
	}
	
	private function applyValues(array $patternDef, array &$paramValues, array &$placeholderValues, $pathPart) {
		if (isset($patternDef['paramName'])) {
			if (!$patternDef['multiple']) {
				$paramValues[$patternDef['paramName']] = $pathPart;
			} else if (isset($paramValues[$patternDef['paramName']]) 
					&& is_array($paramValues[$patternDef['paramName']])) {
				$paramValues[$patternDef['paramName']][] = $pathPart;
			} else {
				$paramValues[$patternDef['paramName']] = array($pathPart);
			}
		}

		if (isset($patternDef['placeholder'])) {
			$placeholderValues[$patternDef['placeholder']] = $pathPart;
		}
	}
	
	private function extractPathParts(Path $path, &$extension) {
		$pathParts = $path->getPathParts();
		if ($this->extensionIncluded && $this->allowedExtensions === null) {
			return $pathParts;
		} 
		
		$lastPathPart = array_pop($pathParts);
		if ($lastPathPart === null) return null;
		
		$lppInfo = pathinfo($lastPathPart);
		if (!isset($lppInfo['extension'])) return null;

		$extension = $lppInfo['extension'];
		if ($this->allowedExtensions !== null && !in_array($extension, $this->allowedExtensions)) {
			return null;
		}
		
		if (!$this->extensionIncluded) {
			$pathParts[] = $lppInfo['filename'];
			return $pathParts;
		} 
		
		$pathParts[] = $lastPathPart;
		return $pathParts;
	}
	
	public function matchesPath(Path $path, $prefixOnly = false) {
		$extension = null;
		$pathParts = $this->extractPathParts($path, $extension);
		if ($pathParts === null) return null;
		
		$paramValues = array();
		$placeholderValues = array();
		$usedPathParts = array();
		
		$multipleDef = null;
		foreach ($this->patternDefs as $patternDef) {
			$pathPart = array_shift($pathParts);
			if ($pathPart === null) {
				if (!$patternDef['required']) continue;
				return null;
			}
			
			$matches = $this->matchesPatternDef($patternDef, $pathPart);
			if (!$matches && $patternDef['required']) return null;
			
			if (!$matches) {
				$pathParts[] = $pathPart; 
				continue;
			}
			
			$usedPathParts[] = $pathPart;
			$this->applyValues($patternDef, $paramValues, $placeholderValues, $pathPart);
			
			if ($patternDef['multiple']) {
				$multipleDef = $patternDef;
			}
		}
		
		if ($multipleDef === null && 0 < count($pathParts)) {
			if (!$prefixOnly) return null;
		} else {
			while (null !== ($pathPart = array_shift($pathParts))) {
				$matches = $this->matchesPatternDef($patternDef, $pathPart);
				if (!$matches) {
					if (!$prefixOnly) return null;
					$pathParts[] = $pathPart;
					break;
				}
				
				$usedPathParts[] = $pathPart;
				$this->applyValues($patternDef, $paramValues, $placeholderValues, $pathPart);
			}
		}
		
		return new MatchResult($paramValues, $placeholderValues, new Path($usedPathParts), 
				new Path($pathParts), $extension);
	}
}
