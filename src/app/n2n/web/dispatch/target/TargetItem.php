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

use n2n\util\type\ArgUtils;
use n2n\web\dispatch\map\PropertyPath;

abstract class TargetItem {
	protected $propertyPath;
	protected $attrs = array();

	public function __construct(PropertyPath $propertyPath) {
		$this->propertyPath = $propertyPath;
	}
	
	public function getPropertyPath() {		
		return $this->propertyPath;
	}
	
	public function constainsAttrName($name) {
		return array_key_exists($name, $this->attrs);
	}
	
	public function setAttr($name, $value) {
		ArgUtils::valType($value, 'string');
		$this->attrs[(string) $name] = $value;
	} 
	
	public function getAttr($name) {
		if (isset($this->attrs[(string) $name])) {
			return $this->attrs[(string) $name];
		}
		return null;
	}
	
	public function setAttrs(array $attrs) {
		ArgUtils::valArray($attrs, 'string');
		$this->attrs = $attrs;
	}
	
	public function getAttrs() {
		return $this->attrs;
	}
}
