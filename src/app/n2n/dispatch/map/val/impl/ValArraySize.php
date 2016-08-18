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
use n2n\dispatch\map\val\ValidationUtils;
use n2n\dispatch\map\val\SinglePropertyValidator;

class ValArraySize extends SinglePropertyValidator {
	const MIN_DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValArraySize.min';
	const MAX_DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValArraySize.max';
	
	private $min;
	private $minErrorMessage;
	private $max;
	private $maxErrorMessage;
	
	public function __construct($min = 1, $minErrorMessage = null, $max = null, $maxErrorMessage = null) {
		$this->min = $min;
		$this->minErrorMessage = ValidationUtils::createMessage($minErrorMessage);
		$this->max = $max;
		$this->maxErrorMessage = ValidationUtils::createMessage($maxErrorMessage);
		
		$this->restrictType(null, true);
	}
	
	public static function minMax($min = null, $max = null, $minErrorMessage = null, 
			$maxErrorMessage = null) {
		return new ValArraySize($min, $minErrorMessage, $max, $maxErrorMessage);
	}

	protected function validateProperty($mapValue) {
		$size = sizeof($mapValue);
		
		if ($this->min !== null && $size < $this->min) {
			$this->failed($this->minErrorMessage, self::MIN_DEFAULT_ERROR_TEXT_CODE, array(), N2N::NS);
		}
		
		if ($this->max !== null && $size > $this->max) {
			$this->failed($this->maxErrorMessage, self::MAX_DEFAULT_ERROR_TEXT_CODE, array(), N2N::NS);
		}
	}
}
