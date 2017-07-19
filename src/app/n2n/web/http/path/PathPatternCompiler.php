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

class PathPatternCompiler {
	const PATH_PART_SEPARATOR = '/';
	const PLACEHOLDER_PREFIX = '{';
	const PLACEHOLDER_SUFFX = '}';
	const PARAM_DELIMITER = ':';
	const MODIFIER_OPTIONAL_SUBFIX = '?';
	const MODIFIER_ONE_OR_MORE = '+';
	const MODIFIER_NONE_OR_MORE = '*';
	const PARAM_PATTERN_ANYTHING_EXPRESSION = '*';
	
	private $placeholderValidators = array();
	private $placeholderNames = array();
		
	public function addPlaceholder($name, PlaceholderValidator $validator) {
		$this->placeholderValidators[$name] = $validator;
		$this->placeholderNames[self::buildPlaceholder($name)] = $name;
	}
	
	private static function buildPlaceholder($name) {
		return self::PLACEHOLDER_PREFIX . $name . self::PLACEHOLDER_SUFFX;
	}
	
	public function removePlaceholder($name) {
		unset($this->placeholderValidators[$name]);
		unset($this->placeholderNames[self::buildPlaceholder($name)]);
	}
	
	public function getPlaceholderValidators() {
		return $this->placeholderValidators;
	}
	
	public function compile($expression) {
		$pathPattern = new PathPattern();
		
		$pathParts = explode(self::PATH_PART_SEPARATOR, (string) $expression);
		foreach ($pathParts as $pathPart) {
			if (!strlen($pathPart)) continue;

			try {
				if (isset($this->placeholderNames[$pathPart])) {
					$placeholderName = $this->placeholderNames[$pathPart];
					$pathPattern->addPlaceholder($placeholderName, $this->placeholderValidators[$placeholderName],
							true, false);
					continue;
				}
				
				$pathPartParts = explode(self::PARAM_DELIMITER, $pathPart, 2);
				if (count($pathPartParts) == 1) {
					$this->extSimple($pathPattern, $pathPart);
				} else {
					$this->extAdvanced($pathPattern, $pathPartParts, $pathPart);
				}
			} catch (PathPatternComposeException $e) {
				throw new PathPatternCompileException('Error while compiling path part \'' . $pathPart 
						. '\' of pattern: ' . $expression, 0, $e);
			}
		}
		
		return $pathPattern;
	}
	
	private function extSimple(PathPattern $pathPattern, $pathPart) {
		if ($pathPart == self::PARAM_PATTERN_ANYTHING_EXPRESSION) {
			$pathPattern->addWhitechar(true, false);
			return;
		}
	
		$pattern = $pathPart;
		$required = null;
		$multiple = null;
		$this->modify($pattern, $required, $multiple);
		
		if ($pattern === null) {
			throw new PathPatternCompileException('Missing pattern in path part: ' . $pathPart);
		}
	
		if ($pattern == self::PARAM_PATTERN_ANYTHING_EXPRESSION) {
			$pathPattern->addWhitechar($required, $multiple);
			return;
		}
	
		$pathPattern->addConstant(urldecode($pattern), $required, $multiple);
	}
	
	private function extAdvanced(PathPattern $pathPattern, array $pathPartParts, $pathPart) {
		$paramName = trim($pathPartParts[0]);
		$pattern = trim($pathPartParts[1]);
		
		if (0 == mb_strlen($pattern)) {
			throw new PathPatternCompileException('Syntax error in path part \'' . $pathPart
					. '\' (empty pattern) of pattern: ' . $pathPart);
		}
		
		$required = true;
		$multiple = false;
		if (0 < mb_strlen($paramName)) {
			$this->modify($paramName, $required, $multiple);
		}		
			
		if ($pattern === self::PARAM_PATTERN_ANYTHING_EXPRESSION) {
			$pathPattern->addWhitechar($required, $multiple, $paramName);
			return;
		}
			
		$pathPattern->addRegex($pattern, $required, $multiple, $paramName);
	}
	
	private function modify(&$modifiableStr, &$required, &$multiple) {
		$modifier = mb_substr($modifiableStr, -1);
		if (in_array($modifier, array(self::MODIFIER_NONE_OR_MORE, self::MODIFIER_ONE_OR_MORE, self::MODIFIER_OPTIONAL_SUBFIX))) {
			$multiple = ($modifier == self::MODIFIER_NONE_OR_MORE
					|| $modifier == self::MODIFIER_ONE_OR_MORE);
			$required = $modifier == self::MODIFIER_ONE_OR_MORE;
			$modifiableStr = mb_substr($modifiableStr, 0, mb_strlen($modifiableStr) - 1);
		} else {
			$required = true;
			$multiple = false;
		}
			
		if (!mb_strlen($modifiableStr)) {
			$modifiableStr = null;
		}
	}
}
