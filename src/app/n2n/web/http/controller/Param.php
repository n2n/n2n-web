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
namespace n2n\http\controller;

use n2n\http\StatusException;
use n2n\http\Response;

abstract class Param {
	private $value;
	/**
	 * 
	 * @param string $value
	 */
	public function __construct($value) {
		$this->value = $value;
	}
	
	public function toInt() {
		return (int) $this->value;
	}
	
	public function toBool() {
		return !empty($this->value);
	}
	
	public function toIntOrReject($status = Response::STATUS_404_NOT_FOUND) {
		if (false !== filter_var($this->value, FILTER_VALIDATE_INT)) {
			return (int) $this->value;
		}
		
		throw new StatusException($status);
	}

	public function isNumeric() {
		return is_numeric($this->value);
	}
	
	public function rejectIfNotNumeric() {
		if ($this->isNumeric()) return;
		
		throw new StatusException('Param not numeric');
	}
	
	public function toNumericOrReject($status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNumeric();
		
		return $this->value;
	}
	
	public function rejectIfNotNotEmptyString($status = Response::STATUS_404_NOT_FOUND) {
		if ($this->isNotEmptyString()) return;
		
		throw new StatusException($status, 'Param not numeric');
	}
	
	public function isNotEmptyString() {
		return is_scalar($this->value) && mb_strlen($this->value) > 0;
	}
	
	public function toNotEmptyStringOrReject($status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNotEmptyString($status);
		
		return $this->value;
	}
	
	public function toStringArrayOrReject($status = Response::STATUS_404_NOT_FOUND): array {
		if (!is_array($this->value)) {
			throw new StatusException($status);
		}

		foreach ($this->value as $fieldValue) {
			if (!is_string($fieldValue)) {
				throw new StatusException($status);
			}
		}
		
		return $this->value;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->value;
	}
}
