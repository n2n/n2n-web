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
namespace n2n\web\dispatch\map\val\impl;

use n2n\web\dispatch\map\val\SinglePropertyValidator;
use n2n\web\dispatch\map\val\ValidationUtils;
use n2n\reflection\ArgUtils;
use n2n\core\N2N;

class ValMandatoryArrayKeys extends SinglePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValMandatoryArrayKeys'; 
	
	private $mandatoryKeys;
	private $errorMessage;
	
	public function __construct(array $mandatoryKeys, $errorMessage = null) {		
		$this->mandatoryKeys = $mandatoryKeys;
		$this->errorMessage = ValidationUtils::createMessage($errorMessage);
		
		$this->restrictType(null, true);
	}
	
	protected function validateProperty($mapValue) {
		ArgUtils::valArrayLike($mapValue);
		
		foreach ($this->mandatoryKeys as $mandatoryKey) {
			if (array_key_exists($mandatoryKey, $mapValue)) continue;

			$this->failed($this->errorMessage, self::DEFAULT_ERROR_TEXT_CODE, array('key' => $mandatoryKey), N2N::NS);
		}
	}
}
