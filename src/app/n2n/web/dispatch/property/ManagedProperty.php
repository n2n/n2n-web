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
namespace n2n\web\dispatch\property;

use n2n\web\dispatch\Dispatchable;
use n2n\core\container\N2nContext;
use n2n\impl\web\dispatch\ui\Form;
use n2n\web\dispatch\map\AnalyzerResult;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\map\CorruptedDispatchException;
use n2n\web\dispatch\map\MappingResult;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\web\dispatch\target\ObjectItem;
use n2n\web\dispatch\target\build\ParamInvestigator;

interface ManagedProperty {
	/**
	 * @return string 
	 */
	public function getName();
	/**
	 * @return boolean 
	 */
	public function isArray();
	/**
	 * @param Dispatchable $dispatchable
	 * @return mixed
	 */
	public function readValue(Dispatchable $dispatchable);
	/**
	 * @param Dispatchable $dispatchable
	 * @param mixed $value
	 */
	public function writeValue(Dispatchable $dispatchable, $value);
	/**
	 * @param mixed $value
	 * @param MappingResult $mappingResult
	 * @param N2nContext $n2nContext
	 */
	public function writeValueToMappingResult($value, MappingResult $mappingResult, N2nContext $n2nContext);
	/**
	 * @param MappingResult $mappingResult
	 * @param N2nContext $n2nContext
	 * @return mixed
	 */
	public function resolveMapValue(PropertyPathPart $pathPart, MappingResult $mappingResult, N2nContext $n2nContext);
	/**
	 * @return \n2n\util\type\TypeConstraint null there are no constriants
	 */
	public function getMapTypeConstraint();
	/**
	 * @param MappingResult $mappingResult
	 * @param N2nContext $n2nContext
	 */
	public function readValueFromMappingResult(MappingResult $mappingResult, N2nContext $n2nContext);
	/**
	 * @param Form $form
	 * @param AnalyzerResult $analyzerResult
	 */
	public function prepareForm(Form $form, AnalyzerResult $analyzerResult = null);
	/**
	 * Throw only CorruptedDispatchException if data sent from client are invalid. You must prevent 
	 * this method from throwing any other Exception type or triggering errors or warinings.
	 * 
	 * @param ObjectItem $targetItem
	 * @param BindingDefinition $bindingDefinition
	 * @param N2nContext $n2nContext
	 * @throws CorruptedDispatchException
	 */
	public function dispatch(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext);
}
