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
namespace n2n\dispatch\map\val\impl;

use n2n\N2N;
use n2n\dispatch\map\PropertyPathPart;
use n2n\dispatch\map\val\SimplePropertyValidator;
use n2n\dispatch\map\MappingResult;
use n2n\dispatch\map\val\ValidationUtils;

class ValNumeric extends SimplePropertyValidator {
	const DEFAULT_NUMERIC_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValNumeric.numeric';
	const DEFAULT_MIN_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValNumeric.min';
	const DEFAULT_MAX_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValNumeric.max';
	const DEFAULT_DECIMAL_PLACES_ERROR_MSG_CODE = 'n2n.dispatch.val.ValNumeric.decimalPlaces';
	
	private $numericErrorMessage;
	private $min;
	private $minErrorMessage;
	private $max;
	private $maxErrorMessage;
	private $decimalPlaces;
	private $decimalPlacesErrorMessage;
	
	public function __construct($numericErrorMessage = null, $min = null, $minErrorMessage = null, 
			$max = null, $maxErrorMessage = null, $decimalPlaces = null, 
			$decimalPlacesErrorMessage = null) {
		$this->numericErrorMessage = $numericErrorMessage;
		$this->min = $min;
		$this->minErrorMessage = $minErrorMessage;
		$this->max = $max;
		$this->maxErrorMessage = $maxErrorMessage;
		$this->decimalPlaces = $decimalPlaces;
		$this->decimalPlacesErrorMessage = $decimalPlacesErrorMessage;
	}
	
	public static function minMax($min = null, $max = null, $minErrorMessage = null, 
			$maxErrorMessage = null) {
		return new ValNumeric(null, $min, $minErrorMessage, $max, $maxErrorMessage);
	}
	
	protected function validateValue($mapValue) {
		if ($mapValue === null) return;
		
		if (!is_numeric($mapValue)) {
			$this->failed($this->numericErrorMessage, self::DEFAULT_NUMERIC_ERROR_TEXT_CODE, array(), N2N::NS);
			return;
		}
		
		if ($this->min !== null && $mapValue < $this->min) {
			$this->failed($this->minErrorMessage, 
					self::DEFAULT_MIN_ERROR_TEXT_CODE, array(), N2N::NS);
			return;
		}
		
		if ($this->max !== null && $mapValue > $this->max) {
			$this->failed($this->maxErrorMessage, 
					self::DEFAULT_MAX_ERROR_TEXT_CODE, array(), N2N::NS);
			return;
		}
		
		if ($this->decimalPlaces !== null) {
// 			// @todo Thomas, mach mal öpis!
		}
	}
}
