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

class ArrayItem extends TargetItem {
	private $propertyItems = array();
	/**
	 * 
	 * @param string $key
	 * @return PropertyItem
	 */
	public function createPropertyItem(string $key) {
		if (!isset($this->propertyItems[$key])) {
			$this->propertyItems[$key] = new PropertyItem($this->propertyPath->fieldExt($key));
		}
		
		return $this->propertyItems[$key]; 
	}
	
	public function containsPropertyItemKey($key) {
		return isset($this->propertyItems[$key]);
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\context\dispatch\target.ArrayTargetItem::getField($key)
	 */
	public function getPropertyItem($key) {
		if ($this->containsPropertyItemKey($key)) {
			return $this->propertyItems[$key];	
		}
	
		return $this->createPropertyItem($key);
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\context\dispatch\target.BranchTargetItem::getFields()
	 */
	public function getPropertyItems() {
		return $this->propertyItems;
	}
}
