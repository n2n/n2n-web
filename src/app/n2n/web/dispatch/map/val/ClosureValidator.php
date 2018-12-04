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
namespace n2n\web\dispatch\map\val;

use n2n\core\container\N2nContext;
use n2n\web\dispatch\map\MappingResult;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\reflection\magic\CanNotFillParameterException;
use n2n\web\dispatch\DispatchErrorException;
use n2n\l10n\Message;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\l10n\impl\TextCodeMessage;

class ClosureValidator implements Validator {
	const SINGULAR_DEFAULT_ERROR_TEXT_CODE = 'n2n.model.dispatch.val.ClosureValidator.singular';
	const PLURAL_DEFAULT_ERROR_TEXT_CODE = 'n2n.model.dispatch.val.ClosureValidator.plural';
	const FIELD_ARG_KEY = 'field';
	
	private $closureFunction;
	private $errMsg;
	
	public function __construct(\Closure $closure, Message $errMsg = null) {
		$this->closureFunction = new \ReflectionFunction($closure);
		$this->errMsg = $errMsg;
	}	
	
	public function validate(MappingResult $mappingResult, N2nContext $n2nContext) {
		
		$bindingErrors = $mappingResult->getBindingErrors();
		$dispatchModel = $mappingResult->getDispatchModel();
	
		$invoker = new MagicMethodInvoker($n2nContext);
		$invoker->setMethod($this->closureFunction);
		$invoker->setClassParamObject(get_class($bindingErrors), $bindingErrors);
		$invoker->setClassParamObject(get_class($mappingResult), $mappingResult);
		
		$propertyNames = array();
		foreach ($this->closureFunction->getParameters() as $parameter) {
			$parameterName = $parameter->getName();
			
			if (!$dispatchModel->containsPropertyName($parameterName)) continue;

			if ($bindingErrors->hasPropertyErrors($parameterName)) {
				return;
			}
			
			if (!$mappingResult->containsPropertyName($parameterName)) {
				return;
// 				throw new ValidationConflictException('MappingResult for ' 
// 								. get_class($mappingResult->getObject()) 
// 								. ' contains no value for property \'' . $parameterName . '\'.',
// 						null, null, 1);
			}
			
			$propertyNames[] = $parameterName;
			$invoker->setParamValue($parameterName, $mappingResult->__get($parameterName));
		}
		
		try {
			if (false !== $invoker->invoke()) return;
		} catch (CanNotFillParameterException $e) {
			throw new DispatchErrorException('Validator closure contains invalid siganture: ' 
							. $this->closureFunction->getName(), 
					$this->closureFunction->getFileName(), $this->closureFunction->getStartLine(), 
					null, null, $e);
		}
		
		$errorMessage = $this->createMessage($mappingResult, $propertyNames);
		foreach ($propertyNames as $propertyName) {
			$bindingErrors->addError(new PropertyPathPart($propertyName), $errorMessage);
		}
	}
	
	private function createMessage(MappingResult $mappingResult, array $invalidPathParts) {
		if ($this->errMsg !== null && !($this->errMsg instanceof TextCodeMessage 
				&& !array_key_exists(self::FIELD_ARG_KEY, $this->errMsg->getArgs()))) {
			return $this->errMsg;
		}
		
		$labels = array();
		foreach ($invalidPathParts as $invalidPropertyName) {
			$labels[] = $mappingResult->getLabel($invalidPropertyName);
		}
		$field = implode(', ', $labels);
		
		if ($this->errMsg !== null) {
			$args = $this->errMsg->getArgs();
			$args[self::FIELD_ARG_KEY] = $field;
			$this->errMsg->setArgs($args);
			return $this->errMsg;
		}
		
		if (count($invalidPathParts) == 1) {
			return Message::createCodeArg(self::SINGULAR_DEFAULT_ERROR_TEXT_CODE, 
					array('field' => current($labels)), Message::SEVERITY_ERROR, 
					'n2n\impl\web\dispatch');
		}
		
		return Message::createCodeArg(self::PLURAL_DEFAULT_ERROR_TEXT_CODE,
				array('field' => implode(', ', $labels)), Message::SEVERITY_ERROR,
				'n2n\impl\web\dispatch');
	}
		
}
