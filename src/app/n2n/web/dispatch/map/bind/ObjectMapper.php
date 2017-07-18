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
namespace n2n\web\dispatch\map\bind;

use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\target\ObjectItem;
use n2n\web\dispatch\map\PropertyPath;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\target\build\ParamInvestigator;
use n2n\web\dispatch\DispatchContext;

class ObjectMapper {
	private $objectItem;
	private $methodName;
	private $propertyPath;
	
	private $mappingDefinition;
	private $bindingDefinition;
	
	public function __construct(ObjectItem $objectItem, $methodName, PropertyPath $propertyPath) {
		$this->objectItem = $objectItem;
		$this->methodName = $methodName;
		$this->propertyPath = $propertyPath;
	}
	
	public function createMappingResult(Dispatchable $dispatchable, BindingTree $bindingTree, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {
		
		$dispatchModel = $n2nContext->lookup(DispatchContext::class)->getDispatchModelManager()
				->getDispatchModel($dispatchable);
		
		$mappingDefinition = $dispatchModel->getDispatchItemFactory()->createMappingDefinition($dispatchable, 
				$n2nContext, $this->objectItem, $this->methodName, $paramInvestigator);
		$mappingResult = $mappingDefinition->getMappingResult();
		
		$this->bindingDefinition = new BindingDefinition($bindingTree, $mappingResult, $this->propertyPath);
		
		foreach ($dispatchModel->getProperties() as $propertyName => $managedProperty) {
			if ($mappingResult->containsPropertyName($propertyName) 
					|| $mappingDefinition->isPropertyIgnored($propertyName)) continue;
			
			$managedProperty->dispatch($this->objectItem, $this->bindingDefinition, $paramInvestigator, $n2nContext);
		}
		
		return $mappingResult;
	}
	
	public function getMappingDefinition() {
		return $this->mappingDefinition;
	}
	
	public function getBindingDefinition() {
		return $this->bindingDefinition;
	}
}
