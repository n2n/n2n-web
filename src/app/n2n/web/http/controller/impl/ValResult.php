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
namespace n2n\web\http\controller\impl;

use n2n\validation\build\ErrorMap;
use n2n\validation\build\ValidationResult;

class ValResult implements ValidationResult {
	private $origValidationResult;
	private $cu;
	
	function __construct(ValidationResult $origValidationResult, ControllingUtils $cu) {
		$this->origValidationResult = $origValidationResult;
		$this->cu = $cu;
	}
	
	function hasErrors(): bool {
		return $this->origValidationResult->hasErrors();
	}
	
	function getErrorMap(): ErrorMap {
		return $this->origValidationResult->getErrorMap();	
	}
	
	/**
	 * Sends a default error report as json if de ValidationResult contains any errors. 
	 * @return boolean true if the ValidationResult contains and a error report has been sent.
	 */
	function sendErrJson() {
		if (!$this->hasErrors()) {
			return false;
		}
		
		$this->cu->sendJson([
			'status' => 'ERR',
			'errorMap' => $this->getErrorMap()->toArray($this->cu->getN2nContext())
		]);
		
		return true;
	}
}
