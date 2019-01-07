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

use n2n\l10n\Message;
use n2n\web\dispatch\map\MappingResult;
use n2n\util\type\ArgUtils;
use n2n\l10n\impl\TextCodeMessage;

class ValidationUtils {
	const FIELD_ARG_KEY = 'field';
	
	public static function createMessage($messageExpression) {
		if ($messageExpression === null || $messageExpression instanceof Message) {
			return $messageExpression;
		}
		
		return Message::create($messageExpression);
	}
	
	public static function buildErrorMessage(MappingResult $mappingResult, array $invalidPathParts,	
			$fallbackTextCode, array $args, $textCodeModuleNs, Message $errorMessage = null) {
		if ($errorMessage !== null && !($errorMessage instanceof TextCodeMessage
				&& !array_key_exists(self::FIELD_ARG_KEY, $errorMessage->getArgs()))) {
			return $errorMessage;
		}
		
		$labels = array();
		foreach ($invalidPathParts as $invalidPathPart) {
			$labels[] = $mappingResult->getLabel($invalidPathPart);
		}
		$field = implode(', ', $labels);
		
		if ($errorMessage !== null) {
			$args = $errorMessage->getArgs();
			$args[self::FIELD_ARG_KEY] = $field;
			$errorMessage->setArgs($args);
			return $errorMessage;
		}
		
		$args[self::FIELD_ARG_KEY] = $field;
		return Message::createCodeArg($fallbackTextCode, $args, 
				Message::SEVERITY_ERROR, $textCodeModuleNs);
	}
	
	public static function registerErrorMessage(MappingResult $mappingResult, $invalidPathParts, 
			$fallbackTextCode, array $args, $textCodeModuleNs, Message $errorMessage = null) {
		$invalidPathParts = ArgUtils::toArray($invalidPathParts);
		$errorMessage = self::buildErrorMessage($mappingResult, $invalidPathParts, $fallbackTextCode, 
				$args, $textCodeModuleNs, $errorMessage);
		
		foreach ($invalidPathParts as $invalidPathPart) {
			$mappingResult->getBindingErrors()->addError($invalidPathPart, $errorMessage);
		}
	}
}
