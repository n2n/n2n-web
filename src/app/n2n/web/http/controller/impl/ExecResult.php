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

use n2n\util\magic\TaskResult;
use n2n\util\magic\MagicArray;

/**
 * @template T
 */
class ExecResult implements TaskResult {

	/**
	 * @param TaskResult<T> $origTaskResult
	 * @param ControllingUtils $cu
	 */
	function __construct(private TaskResult $origTaskResult, private ControllingUtils $cu) {
	}

	function isValid(): bool {
		return $this->origTaskResult->isValid();
	}
	
	function hasErrors(): bool {
		return !$this->origTaskResult->isValid();
	}
	
	function getErrorMap(): MagicArray {
		return $this->origTaskResult->getErrorMap();
	}
	
	/**
	 * Sends a default error report as json if de ValidationResult contains any errors. 
	 * @return boolean true if the ValidationResult contains and an error report has been sent.
	 */
	function sendErrJson(): bool {
		if (!$this->hasErrors()) {
			return false;
		}
		
		$this->cu->sendJson([
			'status' => 'ERR',
			'errorMap' => $this->getErrorMap()->toArray($this->cu->getN2nContext())
		]);
		
		return true;
	}

	/**
	 * @return T
	 */
	function get(): mixed {
		return $this->origTaskResult->get();
	}
}
