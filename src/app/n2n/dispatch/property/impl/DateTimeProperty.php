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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\dispatch\property\impl;

use n2n\dispatch\property\SimpleProperty;
use n2n\core\container\N2nContext;
use n2n\l10n\L10nUtils;
use n2n\l10n\ParseException;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\target\TargetItem;
use n2n\dispatch\target\ValueItem;
use n2n\dispatch\map\CorruptedDispatchException;
use n2n\l10n\MessageCode;
use n2n\dispatch\map\PropertyPathPart;
use n2n\dispatch\map\MappingResult;
use n2n\l10n\Message;
use n2n\N2N;
use n2n\dispatch\target\ObjectItem;
use n2n\dispatch\target\build\ParamInvestigator;

class DateTimeProperty extends ManagedPropertyAdapter implements SimpleProperty {
	const PARSE_ERROR_CODE = 'n2n.dispatch.property.impl.DateTimeProperty.parseDateTime';
	
	private $dateStyle;
	private $timeStyle;
	private $icuPattern;
		
	public function __construct($accessProxy, $array, $useArrayObject = null) {
		CommonManagedPropertyProvider::restrictConstraints($accessProxy, 'DateTime', $array,
				$useArrayObject);
		parent::__construct($accessProxy, $array);
		$this->useArrayObject = $useArrayObject;
	}
	
	public function getDateStyle() {
		return $this->dateStyle;
	}	
	
	public function setDateStyle($dateStyle) {
		$this->dateStyle = $dateStyle;
	}
	
	public function getTimeStyle() {
		return $this->timeStyle;
	}
	
	public function setTimeStyle($timeStyle) {
		$this->timeStyle = $timeStyle;
	}
	
	public function getIcuPattern() {
		return $this->icuPattern;
	}
	
	public function setIcuPattern($icuPattern) {
		$this->icuPattern = $icuPattern;
	}
	/* (non-PHPdoc)
	 * @see \n2n\dispatch\property\SimpleProperty::convertMapValueToScalar()
	 */
	public function convertMapValueToScalar($mapValue, N2nContext $n2nContext) {
		if ($mapValue instanceof \DateTime) {
			return $this->formatDateTime($mapValue, $n2nContext->getN2nLocale());
		}
		
		return null;
	}

	public function dispatch(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {

		$mappingResult = $bindingDefinition->getMappingResult();
		$rawValue = $paramInvestigator->findValue($bindingDefinition->getPropertyPath()->ext($this->getName()));
		
		if ($rawValue === null) {
			$bindingDefinition->getMappingResult()->__set($this->getName(), 
					$this->readValue($bindingDefinition->getMappingResult()->getObject()));
			return;
		}
	
		if (!$this->isArray()) {
			CorruptedDispatchException::assertTrue(is_scalar($rawValue));
			
			$mappingResult->__set($this->getName(), 
					$this->parseDateTime($rawValue, $n2nContext->getN2nLocale(), 
							new PropertyPathPart($this->getName()), $mappingResult));
			return;
		}
	
		CorruptedDispatchException::assertTrue(is_array($rawValue));
		
		$mapValue = $this->createEmptyValue();
		foreach ($rawValue as $key => $rawFieldValue) {
			CorruptedDispatchException::assertTrue(is_scalar($rawFieldValue));

			if (null !== ($mapFieldValue = $this->parseDateTime($rawValue, $n2nContext->getN2nLocale(),
					new PropertyPathPart($this->getName(), true, $key), $mappingResult))) {
				$mapValue[$key] = $mapFieldValue;
			}
		}
		$mappingResult->__set($this->getName(), $mapValue);
	}
	
	private function parseDateTime($expression, $n2nLocale, PropertyPathPart $pathPart, 
			MappingResult $mappingResult) {
		if (mb_strlen($expression) == 0) return null;
		
		try {
			return L10nUtils::parseDateTimeInput($expression, $n2nLocale, 
					$this->dateStyle, $this->timeStyle);
		} catch (ParseException $e) {
			$bindingErrors = $mappingResult->getBindingErrors();
			$bindingErrors->setInvalidRawValue($pathPart, $expression);
			$bindingErrors->addError($pathPart, new MessageCode(self::PARSE_ERROR_CODE, 
					array('field' => $mappingResult->getLabel($pathPart), 
							'example' => $this->formatDateTime(new \DateTime(), $n2nLocale)),
					Message::SEVERITY_ERROR, N2N::NS));
			return null;
		}
	}
	
	private function formatDateTime(\DateTime $dateTime, $n2nLocale) {
		if ($this->icuPattern !== null) {
			return L10nUtils::formatDateTimeWithIcuPattern($dateTime, $n2nLocale, $this->icuPattern);
		}
		
		return L10nUtils::formatDateTimeInput($dateTime, $n2nLocale, $this->dateStyle, $this->timeStyle);
	}
}
