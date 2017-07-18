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

class ObjectArrayItem extends TargetItem {
	private $objectItems = array();
	
	/**
	 * @param string $key
	 * @return ObjectItem
	 */	
	public function createObjectItem($key) {
		if (!isset($this->objectItems[$key])) {
			$this->objectItems[$key] = new ObjectItem($this->propertyPath->fieldExt($key));
		}
		
		return $this->objectItems[$key]; 
	}
	
	/**
	 * @param string $key
	 */
	public function containsObjectItemKey($key) {
		return isset($this->objectItems[$key]);
	}

	/**
	 * @param string $key
	 * @return ObjectItem
	 */
	public function getObjectItemByKey($key) {
		if ($this->containsObjectItemKey($key)) {
			return $this->objectItems[$key];
		}
		
		return null;
	}

	/**
	 * @return ObjectItem[]
	 */
	public function getObjectItems() {
		return $this->objectItems;
	}
}
