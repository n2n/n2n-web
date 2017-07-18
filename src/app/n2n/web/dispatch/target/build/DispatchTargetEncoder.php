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

use n2n\web\dispatch\target\TargetItem;
use n2n\web\dispatch\target\PropertyItem;
use n2n\web\dispatch\target\ArrayItem;
use n2n\web\dispatch\target\ObjectItem;
use n2n\web\dispatch\target\ObjectArrayItem;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\target\DispatchTarget;

class DispatchTargetEncoder {
	private $dispatchClassName;
	private $coder;
	private $pseudoBasePropertyPath;
	
	public function __construct(DispatchTargetCoder $coder) {
		$this->coder = $coder;
	}
	
	public function buildRealPropertyPath(PropertyPath $propertyPath): PropertyPath {
		if ($this->pseudoBasePropertyPath === null) return $propertyPath;
		
		return $this->pseudoBasePropertyPath->ext($propertyPath);
	}
	
	public function getPseudoBasePropertyPath() {
		return $this->pseudoBasePropertyPath;
	}
	
	public function setPseudoBasePropertyPath(PropertyPath $pseudoBasePropertyPath = null) {
		$this->pseudoBasePropertyPath = $pseudoBasePropertyPath;
	}
	
	public function buildValueParamName(PropertyPath $propertyPath, bool $useEmptyBrackets): string {
		return ParamHandler::build(ParamHandler::TYPE_PROPERTY, $this->buildRealPropertyPath($propertyPath), 
				$useEmptyBrackets);
	}
	
	public function buildAttrParamName(PropertyPath $propertyPath, string $name = null): string {
		return ParamHandler::build(ParamHandler::TYPE_ATTR, $this->buildRealPropertyPath($propertyPath), 
				 false, $name);
	}
	
	public function buildMethodParamName($methodName) {
		return ParamHandler::buildForMethod($methodName);
	}

	public function encodeTargetItem(TargetItem $targetItem) {
		$propertyPath = $this->buildRealPropertyPath($targetItem->getPropertyPath());
		return array(
				'name' => ParamHandler::build(ParamHandler::TYPE_PROPERTY_TARGET, $propertyPath, false),
				'value' => $this->coder->encode($this->buildArray($targetItem, $propertyPath)));
	}
	
	public function encodeDispatchTarget(DispatchTarget $dispatchTarget) {
		return array(
				'name' => ParamHandler::buildDispatchTargetName(),
				'value' => $this->coder->encode($this->buildDispatchTargetArray($dispatchTarget)));
	}
	
	private function buildDispatchTargetArray(DispatchTarget $dispatchTarget) {
		return array(Prop::KEY_DISPATCH_CLASS_NAME => $dispatchTarget->getDispatchClassName());
	}
	
	private function buildArray(TargetItem $targetItem, PropertyPath $propertyPath) {
		$props = array(
				Prop::KEY_TYPE => $this->determineType($targetItem),
				Prop::KEY_PROPERTY_PATH => (string) $propertyPath);
		
		$attrs = $targetItem->getAttrs();
		if (!empty($attrs)) { 
			$props[Prop::KEY_ATTRS] = $attrs;
		}
		
		return $props;
	}
	
	private function determineType(TargetItem $targetItem) {
		if ($targetItem instanceof PropertyItem) return Prop::TYPE_PROPERTY;
	
		if ($targetItem instanceof ArrayItem) return Prop::TYPE_ARRAY;
	
		if ($targetItem instanceof ObjectItem) return Prop::TYPE_OBJECT;
	
		if ($targetItem instanceof ObjectArrayItem) return Prop::TYPE_OBJECT_ARRAY;
	
		throw new \InvalidArgumentException();
	}
}
