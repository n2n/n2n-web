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

use n2n\util\StringUtils;
use n2n\util\ex\IllegalStateException;

class PropertyPathPart {
	private $propertyName;
	private $array = false;
	private $arrayKey = null;
	private $resolvedArrayKey = null;
	/**
	 * @param string $propertyName
	 * @param string $array
	 * @param string $arrayKey
	 */
	public function __construct($propertyName, $array = false, $arrayKey = null) {
		$this->propertyName = (string) $propertyName;
		$this->array = (boolean) $array;
		$this->arrayKey = $arrayKey;
		$this->resolvedArrayKey = $arrayKey;
	}
	
	public static function isNameValid($name) {
		return false === strrchr((string) $name, '[]/\\');
	}
	/**
	 * @return string
	 */
	public function getPropertyName(): string {
		return $this->propertyName;
	}
	/**
	 * @return boolean
	 */
	public function isArray() {
		return $this->array;
	}
	/**
	 * @return string
	 */
	public function getArrayKey() {
		return $this->arrayKey;
	}
	/**
	 * @return boolean
	 */
	public function isArrayKeyResolved() {
		return $this->resolvedArrayKey !== null;
	}
	/**
	 * @param string $resolvedArrayKey
	 * @throws IllegalStateException
	 */
	public function resolveArrayKey($resolvedArrayKey) {
		if ($this->resolvedArrayKey !== null) {
			throw new IllegalStateException('Array key for path part already resolved: ' 
					. $this->resolvedArrayKey);
		}
		$this->resolvedArrayKey = $resolvedArrayKey;
	}
	/**
	 * @throws IllegalStateException
	 * @return string
	 */
	public function getResolvedArrayKey() {
		if ($this->isArray() && !$this->isArrayKeyResolved()) {
			throw new IllegalStateException('Path part is no array or has unresolved array key: ' 
					. $this->__toString());
		}
		return $this->resolvedArrayKey;
	}
	/**
	 * @param string $arrayKey
	 */
	public function copyWithArrayKey($arrayKey = null) {
		return new PropertyPathPart($this->propertyName, true, $arrayKey);
	}
	/**
	 * @return string
	 */
	public function __toString(): string {
		if (!$this->array) {
			return $this->propertyName;
		}

		return $this->propertyName . '[' . $this->resolvedArrayKey . ']';
	}
	/**
	 * @param string $expression
	 * @throws InvalidPropertyExpressionException
	 * @return \n2n\web\dispatch\map\PropertyPathPart
	 */
	public static function createFromExpression($expression) {
		if ($expression instanceof PropertyPathPart) {
			return $expression;
		}
		
		$arrOpenNum = substr_count($expression, '[');
		$arrCloseNum = substr_count($expression, ']');

		if (!mb_strlen($expression) || $arrOpenNum != $arrCloseNum 
				|| ($arrOpenNum > 0 && !StringUtils::endsWith(']', $expression))) {
			throw new InvalidPropertyExpressionException('Invalid property expression part:' . $expression);
		}

		if ($arrOpenNum > 1) {
			throw new InvalidPropertyExpressionException(
					'Multidimensioal arrays not supported: ' . $expression);
		}

		if ($arrOpenNum > 0) {
			$pos = strpos($expression, '[');
			$arrayKey = trim(mb_substr($expression, $pos), '[]');
			return new PropertyPathPart(mb_substr($expression, 0, $pos), true, 
					(mb_strlen($arrayKey) ? $arrayKey : null));
		}

		return new PropertyPathPart($expression, false, null);
	}
}
