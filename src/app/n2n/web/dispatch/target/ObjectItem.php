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

class ObjectItem extends TargetItem {
	private $items = array();

	private function extPropertyPath($propertyName) {
		if ($this->propertyPath === null) {
			return PropertyPath::createFromPropertyExpression($propertyName);
		}
	
		return $this->propertyPath->ext($propertyName);
	}
	/**	
	 * 
	 * @param string $propertyName
	 * @return bool
	 */
	public function containsItemPropertyName(string $propertyName) {
		return isset($this->items[$propertyName]);
	} 
	/**
	 * 
	 * @param string $propertyName
	 * @return TargetItem
	 */
	public function getItemByPropertyName(string $propertyName) {
		if (!$this->containsItemPropertyName($propertyName)) return null;
		
		return $this->items[$propertyName];
	}
	
	public function getItems() {
		return $this->items;
	}
	
	public function createPropertyItem($propertyName) {
		if (!isset($this->items[$propertyName])) {
			$this->items[$propertyName] = new PropertyItem($this->extPropertyPath($propertyName));
		}

		$prop = $this->items[$propertyName];
		if (!($prop instanceof PropertyItem)) {
			throw new PropertyPathMissmatchException();
		}
		return $prop;
	}
	
	public function createArrayItem($propertyName) {
		if (!isset($this->items[$propertyName])) {
			$this->items[$propertyName] = new ArrayItem($this->extPropertyPath($propertyName));
		}
		
		$arr = $this->items[$propertyName];
		if (!($arr instanceof ArrayItem)) {
			throw new PropertyPathMissmatchException($this->getPropertyPath()->ext($propertyName) . ' is ' . get_class($arr) . ' not ' . ArrayItem::class);
		}
		return $arr;
	}
	
	public function createObjectItem($propertyName) {
		if (!isset($this->items[$propertyName])) {
			$this->items[$propertyName] = new ObjectItem($this->extPropertyPath($propertyName));
		}
			
		$disp = $this->items[$propertyName];
		if (!($disp instanceof ObjectItem)) {
			throw new PropertyPathMissmatchException('Property ' . $this->extPropertyPath($propertyName) 
					. ' path already taken by non ObjectItem: ' . get_class($disp));
		}
		
		return $disp;
	}
	/**
	 * 
	 * @param string $propertyName
	 * @throws PropertyPathMissmatchException
	 * @return ObjectArrayItem
	 */
	public function createObjectArrayItem($propertyName) {
		if (!array_key_exists($propertyName, $this->items)) {
			$this->items[$propertyName] = new ObjectArrayItem($this->extPropertyPath($propertyName));
		}
		
		$dispArr = $this->items[$propertyName];
		if (!($dispArr instanceof ObjectArrayItem)) {
			throw new PropertyPathMissmatchException();
		}
			
		return $dispArr;
	}
}
