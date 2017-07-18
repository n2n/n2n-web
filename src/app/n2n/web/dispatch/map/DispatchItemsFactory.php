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

use n2n\web\dispatch\Dispatchable;
use n2n\core\container\N2nContext;
use n2n\reflection\ReflectionUtils;
use n2n\web\dispatch\DispatchErrorException;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\model\DispatchModel;
use n2n\web\dispatch\target\ObjectItem;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\web\dispatch\target\build\ParamInvestigator;
use n2n\web\dispatch\map\bind\BindingErrors;

class DispatchItemsFactory {
	const MAPPING_METHOD = '_mapping';
	const VALIDATION_METHOD = '_validation';
	
	private $class;
	private $mappingMethods;
	private $validationMethods;
	
	public function __construct(DispatchModel $dispatchModel) {
		$this->dispatchModel = $dispatchModel;
		$this->class = $this->dispatchModel->getClass();
	}
	
	public function createMappingResult(Dispatchable $dispatchable, N2nContext $n2nContext) {
		return $this->createMappingDefinition($dispatchable, $n2nContext)->getMappingResult();
	}
	
	public function createMappingDefinition(Dispatchable $dispatchable, N2nContext $n2nContext, 
			ObjectItem $objectItem = null, $methodName = null, ParamInvestigator $paramInvestigator = null) {
		$mappingDefinition = new MappingDefinition(new MappingResult($dispatchable, 
				$this->dispatchModel), $objectItem, $methodName, $paramInvestigator);
		$this->setupMappingDefinition($mappingDefinition, $n2nContext);
		return $mappingDefinition;
	}
	
	public function setupMappingDefinition(MappingDefinition $mappingDefinition, N2nContext $n2nContext) {
		if ($this->mappingMethods === null) {
			$this->mappingMethods = $this->extractMethods(self::MAPPING_METHOD, false);
		}
		
		$mappingResult = $mappingDefinition->getMappingResult();
		foreach ($this->mappingMethods as $method) {
			$methodInvoker = new MagicMethodInvoker($n2nContext);
			$methodInvoker->setMethod($method);
			$methodInvoker->setClassParamObject(get_class($mappingDefinition), $mappingDefinition);
			$methodInvoker->setClassParamObject(get_class($mappingResult), $mappingResult);
			$methodInvoker->setClassParamObject(BindingErrors::class, $mappingResult->getBindingErrors());
			$methodInvoker->invoke($mappingResult->getObject());
		}
	}
	
	public function setupBindingDefinition(BindingDefinition $bindingDefinition, 
			N2nContext $n2nContext) {
		if ($this->validationMethods === null) {
			$this->validationMethods = $this->extractMethods(self::VALIDATION_METHOD, true);
		}

		$mappingResult = $bindingDefinition->getMappingResult();
		foreach ($this->validationMethods as $method) {
			$methodInvoker = new MagicMethodInvoker($n2nContext);
			$methodInvoker->setClassParamObject(get_class($bindingDefinition), $bindingDefinition);
			$methodInvoker->setClassParamObject(get_class($mappingResult), $mappingResult);
			$methodInvoker->invoke($mappingResult->getObject(), $method);
		}
	}
	
	private function extractMethods($methodName, $required) {
		$methods = ReflectionUtils::extractMethodHierarchy($this->class, $methodName);
		if ($required && !sizeof($methods)) {
			throw new DispatchErrorException('Method missing in dispatchable: ' 
							. $this->class->getName() . '::' . $methodName . '()',
					$this->class->getFileName(), $this->class->getStartLine());
		}
		
		foreach ($methods as $method) {
			if (!$method->isPrivate() || $method->isStatic() || $method->isAbstract()) {
				throw new DispatchErrorException('Invalid method signature for ' 
								. $method->getDeclaringClass()->getName() . '::' . $method->getName() 
								. '(). Correct signature:  private function ' . $methodName . '(...)',
						$method->getFileName(), $method->getStartLine());
			}
			
			$method->setAccessible(true);
		}
		
		return $methods;
	}
// 	public function createBinding() {
		
// 	}
	
// 	public function setupBinding() {
		
// 	}
}
