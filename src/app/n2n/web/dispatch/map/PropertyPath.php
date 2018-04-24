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
class PropertyPath implements \IteratorAggregate, \Countable {
	const PROPERTY_LEVEL_SEPARATOR = '.';
	
	private $pathParts = array();
	/**
	 * @param array $pathParts
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $pathParts) {
		foreach ($pathParts as $pathPart) {
			$this->pathParts[] = PropertyPathPart::createFromExpression($pathPart);
		}
	}
	/* (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator() {
		return new PropertyPathIterator($this->pathParts);
	}
	
	public function isEmpty() {
		return empty($this->pathParts);
	}
	
	private function ensureNotEmpty() {
		if (empty($this->pathParts)) {
			throw new IllegalStateException('Property path empty');
		}
	}
	
	/**
	 * @return PropertyPathPart
	 */
	public function getFirst() {
		$this->ensureNotEmpty();
		reset($this->pathParts);
		return current($this->pathParts);
	}
	/**
	 * @return PropertyPathPart
	 */
	public function getLast() {
		$this->ensureNotEmpty();
		return end($this->pathParts);
	}

	/**
	 * @return PropertyPathPart[]
	 */
	public function toArray() {
		return $this->pathParts;
	}

	public function __toString(): string {
		return self::implodePathParts($this->pathParts);
	}
	
	public static function implodePathParts(array $pathParts) {
		return implode(self::PROPERTY_LEVEL_SEPARATOR, $pathParts);
	}

	public function count() {
		return sizeof($this->pathParts);
	}
	
	public function createReducedPath($length) {
		$this->ensureNotEmpty();
		return new PropertyPath(array_slice($this->pathParts, 0, count($this->pathParts) - $length));
	}
	
	public function reduced($length) {
		return $this->createReducedPath($length);
	}
	
	public function fieldReduced() {
		$this->ensureNotEmpty();
		
		$lastPathPart = $this->getLast();
		if (!$lastPathPart->isArray()) return $this;
		
		if (1 == $this->count()) {
			return new PropertyPath(array(new PropertyPathPart($this->getLast()->getPropertyName())));
		}
		
		return $this->reduced(1)->ext(new PropertyPathPart($this->getLast()->getPropertyName()));
	}
	/**
	 * 
	 * @param array $pathParts
	 * @return PropertyPath
	 */
	public function createExtendedPath(array $pathParts) {
		return new PropertyPath(array_merge($this->pathParts, $pathParts));
	}

	public function ext($pathExtExpression) {
		return $this->createExtendedPath(self::createFromPropertyExpression($pathExtExpression)->toArray());	
	}
	
	public function fieldExt($key) {
		return $this->createArrayFieldExtendedPath($key);
	}
	/**
	 * 
	 * @param array $pathParts
	 * @return PropertyPath
	 */
	public function createArrayFieldExtendedPath($key) {
		$this->ensureNotEmpty();
		
		$pathParts = $this->toArray();
		$lastPathPart = array_pop($pathParts);
		$pathParts[] = new PropertyPathPart($lastPathPart->getPropertyName(), true, $key);
		return new PropertyPath($pathParts);
	} 
	/**
	 * 
	 * @param string $propertyExpression
	 * @throws InvalidPropertyExpressionException
	 * @return PropertyPath
	 */
	public static function createFromPropertyExpression($propertyExpression): PropertyPath {
		$expressionParts = array();
		
		if ($propertyExpression instanceof PropertyPath) {
			$expressionParts = $propertyExpression->toArray();
		} else if ($propertyExpression instanceof PropertyPathPart) {
			$expressionParts[] = $propertyExpression;
		} else if (is_array($propertyExpression)) {
			$expressionParts = $propertyExpression;
		} else if (preg_match_all('/[^\.\[]+(\[[^\]]*\]?[^\.]*)*/', (string) $propertyExpression, $matches)) {
			foreach ($matches[0] as $expressionPart) {
				$expressionParts[] = $expressionPart;
			}
		}
		
// 		if (!$emptyAllowed && !sizeof($expressionParts)) {
// 			throw new InvalidPropertyExpressionException(
// 					'Property expression is empty.');
// 		}
		
		return new PropertyPath($expressionParts);
	}
	
	public static function createFromPropertyExpressionArray(array $pathPartExpressionArray) {
		if (!sizeof($pathPartExpressionArray)) {
			throw new InvalidPropertyExpressionException(
					'Property expression is empty.');
		}
		
		return new PropertyPath($pathPartExpressionArray);
	}
	
	public static function createFromArray(array $pathParts) {
		return new PropertyPath($pathParts);
	}
	 
}

class PropertyPathIterator implements \Iterator {
	private $array;
	private $position = 0;

	public function __construct(array $pathParts) {
		$this->array = $pathParts;
		$this->position = 0;
	}

	function rewind() {
		$this->position = 0;
	}

	function current() {
		return $this->array[$this->position];
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return isset($this->array[$this->position]);
	}
}
