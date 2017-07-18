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
namespace n2n\web\http\controller;

use n2n\web\http\StatusException;
use n2n\web\http\Response;
use n2n\reflection\ArgUtils;

abstract class Param {
	private $value;
	/**
	 * 
	 * @param string $value
	 */
	public function __construct($value) {
		ArgUtils::assertTrue($value !== null);
		$this->value = $value;
	}
	
	public function toInt() {
		return (int) $this->value;
	}
	
	public function toBool() {
		return !empty($this->value);
	}
	
	public function toIntOrReject($status = Response::STATUS_404_NOT_FOUND) {
		if (false !== ($value = filter_var($this->value, FILTER_VALIDATE_INT))) {
			return $value;
		}
		
		throw new StatusException($status);
	}
	
	public function toIntOrNull($rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		return $this->toIntOrReject($rejectStatus);
	}
	
	public function toFloatOrReject($status = Response::STATUS_404_NOT_FOUND) {
		if (false !== ($value = filter_var($this->value, FILTER_VALIDATE_FLOAT))) {
			return $value;
		}
		
		throw new StatusException($status);
	}
	
	public function toFloatOrNull($rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		return $this->toFloatOrReject($rejectStatus);
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
	
	public function rejectIfNot($value, $status = Response::STATUS_404_NOT_FOUND) {
		if ($this->value === $value) return;
		
		throw new StatusException($status, 'Param invalid');
	}
	
	public function rejectIfNotNotEmptyString($status = Response::STATUS_404_NOT_FOUND) {
		if ($this->isNotEmptyString()) return;
		
		throw new StatusException($status, 'Param not numeric');
	}
	
	public function isEmptyString() {
		return $this->value === '';
	}
	
	public function isNotEmptyString() {
		return is_scalar($this->value) && mb_strlen($this->value) > 0;
	}
	
	private function valNotEmptyString($value) {
		return is_scalar($value) && mb_strlen($value) > 0;
	}
	
	public function toNotEmptyStringOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNotEmptyString($status);
		
		return $this->value;
	}
	
	public function toNotEmptyStringOrNull(int $rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		// value could be an array
		return $this->toNotEmptyStringOrReject($rejectStatus);
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
	
	public function toIntArrayOrReject($status = Response::STATUS_404_NOT_FOUND): array {
		if (!is_array($this->value)) {
			throw new StatusException($status);
		}
	
		$values = array();
		
		foreach ($this->value as $key => $fieldValue) {
			if (false !== ($value = filter_var($fieldValue, FILTER_VALIDATE_INT))) {
				$values[$key] = $value;
				continue;
			}
			
			throw new StatusException($status);
		}
	
		return $this->value;
	}
	
	
	
	public function splitToStringArrayOrReject(string $separator = ',', bool $sorted = true, int $status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNotEmptyString();
		
		$values = explode($separator, $this->value);
		
		if (!$sorted) return $values;
		
		$values2 = $values;
		sort($values);
		
		if ($values !== $values2) {
			throw new StatusException($status);
		}
		
		return $values;
	}
	
	
	public function splitToIntArrayOrReject(string $separator = '-', bool $sorted = true, int $status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNotEmptyString();
		
		$values = array();
		foreach (explode($separator, $this->value) as $key => $fieldValue) {
			if (false !== ($value = filter_var($fieldValue, FILTER_VALIDATE_INT))) {
				$values[$key] = $value;
				continue;
			}
			
			throw new StatusException($status);
		}
		
		if (!$sorted) return $values;
		
		$values2 = $values;
		sort($values2, SORT_NUMERIC);
		
		if ($values !== $values2) {
			throw new StatusException($status);
		}
		
		return $values;
	}
	
	public function toNotEmptyStringArrayOrReject($status = Response::STATUS_404_NOT_FOUND) {
		$values = $this->toArrayOrReject($status);
		foreach ($values as $value) {
			if (!self::valNotEmptyString($value)) {
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
