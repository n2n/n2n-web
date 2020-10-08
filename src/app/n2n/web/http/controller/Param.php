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
use n2n\util\type\ArgUtils;
use n2n\util\StringUtils;
use n2n\util\type\attrs\Attributes;
use n2n\util\type\attrs\DataMap;
use n2n\util\type\attrs\DataSet;
use n2n\web\http\controller\impl\HttpData;

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
	
	
	/**
	 * @param int $status
	 * @return string
	 * @deprecated
	 */
	public function toNumericOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->toNumeric($status);
	}
	
	public function toNumeric($status = Response::STATUS_404_NOT_FOUND) {
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
	
	/**
	 * @param int $status
	 * @return string
	 * @deprecated
	 */
	public function toNotEmptyStringOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->toNotEmptyString($status);
	}
	
	/**
	 * @param int $status
	 * @return string
	 */
	public function toNotEmptyString(int $status = Response::STATUS_404_NOT_FOUND) {
		$this->rejectIfNotNotEmptyString($status);
		
		return $this->value;
	}
	
	public function toNotEmptyStringOrNull(int $rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		// value could be an array
		return $this->toNotEmptyString($rejectStatus);
	}
	
	
	/**
	 * @param int $rejectStatus
	 * @throws StatusException
	 * @return string
	 */
	public function toArray(int $rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if (is_array($this->value)) {
			return $this->value;
		}
		
		throw new StatusException($rejectStatus, 'Param not array');
	}
	
	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	
	/**
	 * @param int $status
	 * @return array
	 * @deprecated
	 */
	public function toStringArrayOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->toStringArray($status);
	}
	
	/**
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 */
	public function toStringArray(int $status = Response::STATUS_404_NOT_FOUND) {
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
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 * @deprecated
	 */
	public function toIntArrayOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->toIntArray($status);
	}
	
	/**
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 */
	public function toIntArray(int $status = Response::STATUS_404_NOT_FOUND) {
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
	
		return $values;
	}

	/**
	 * @param string $separator
	 * @param bool $sorted
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 * @deprecated
	 */
	public function splitToStringArrayOrReject(string $separator = ',', bool $sorted = true,
			int $status = Response::STATUS_404_NOT_FOUND) {
		$this->splitToStringArray($separator, $sorted, $status);
	}
	
	/**
	 * @param string $separator
	 * @param bool $sorted
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 */
	public function splitToStringArray(string $separator = ',', bool $sorted = true, 
			int $status = Response::STATUS_404_NOT_FOUND) {
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
	
	
	/**
	 * @param string $separator
	 * @param bool $sorted
	 * @param int $status
	 * @deprecated
	 */
	public function splitToIntArrayOrReject(string $separator = '-', bool $sorted = true, 
			int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->splitToIntArray($separator, $sorted, $status);
	}
	
	public function splitToIntArray(string $separator = '-', bool $sorted = true, 
			int $status = Response::STATUS_404_NOT_FOUND) {
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
	
	
	/**
	 * @param int $errStatus
	 * @return string
	 * @deprecated
	 */
	public function toNotEmptyStringArrayOrReject(int $errStatus = Response::STATUS_404_NOT_FOUND) {
		return $this->toNotEmptyStringArray($errStatus);
	}
	
	/**
	 * @param int $errStatus
	 * @throws StatusException
	 * @return string
	 */
	public function toNotEmptyStringArray(int $errStatus = Response::STATUS_404_NOT_FOUND) {
		$values = $this->toArrayOrReject($errStatus);
		foreach ($values as $value) {
			if (!self::valNotEmptyString($value)) {
				throw new StatusException($errStatus);
			}
		}
		return $this->value;
	}
	
	/**
	 * @param int $errStatus
	 * @param bool $assoc
	 * @throws StatusException
	 * @return array|object
	 * @deprecated {@see self::parseJson()}
	 */
	public function parseJsonOrReject(int $errStatus = Response::STATUS_400_BAD_REQUEST, bool $assoc = true) {
		return $this->parseJson($errStatus, $assoc);
	}
	
	/**
	 * @param int $errStatus
	 * @param bool $assoc
	 * @throws StatusException
	 * @return array|object
	 */
	public function parseJson(int $errStatus = Response::STATUS_400_BAD_REQUEST, bool $assoc = true) {
		try {
			return StringUtils::jsonDecode($this->value, $assoc);
		} catch (\n2n\util\JsonDecodeFailedException $e) {
			throw new StatusException($errStatus, null, null, $e);
		}
	}
	
	/**
	 * @deprecated use {@see self::parseJsonToDataSet()}
	 * @param int $errStatus
	 * @throws StatusException
	 * @return \n2n\util\type\attrs\Attributes
	 */
	public function parseJsonToAttrsOrReject(int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		return new Attributes($this->parseJson($errStatus, true));
	}
	
	/**
	 * @param int $errStatus
	 * @throws StatusException
	 * @return \n2n\util\type\attrs\Attributes
	 */
	public function parseJsonToDataSet(int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		return new DataSet($this->parseJson($errStatus, true));
	}
	
	/**
	 * @param int $errStatus
	 * @throws StatusException
	 * @return \n2n\util\type\attrs\DataMap
	 */
	public function parseJsonToDataMap(int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		return new DataMap($this->parseJson($errStatus, true));
	}
	
	/**
	 * @param int $errStatus
	 * @return HttpData
	 */
	function parseJsonToHttpData(int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		return new HttpData($this->parseJsonToDataMap($errStatus, true), $errStatus);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->value;
	}
}
