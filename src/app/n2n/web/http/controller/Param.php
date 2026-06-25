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

use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\web\http\StatusException;
use n2n\web\http\Response;
use n2n\util\type\ArgUtils;
use n2n\util\StringUtils;
use n2n\util\type\attrs\Attributes;
use n2n\util\attr\DataMap;
use n2n\util\attr\DataSet;
use n2n\web\http\controller\impl\HttpData;
use n2n\util\type\TypeConstraint;
use n2n\util\type\TypeConstraints;
use n2n\util\EnumUtils;
use DateTimeImmutable;
use n2n\util\DateUtils;
use n2n\util\DateParseException;
use ReflectionClass;
use n2n\util\ex\IllegalStateException;
use n2n\spec\valobj\scalar\StringValueObject;
use n2n\spec\valobj\scalar\IntValueObject;
use n2n\spec\valobj\scalar\FloatValueObject;
use n2n\spec\valobj\scalar\BoolValueObject;
use n2n\spec\valobj\err\IllegalValueException;

abstract class Param {
	private string|array $value;

	/**
	 * 
	 * @param string|array $rawValue
	 */
	public function __construct(private string|array $rawValue) {
		$this->value = StringUtils::convertNonPrintables($rawValue);
	}

	/**
	 * @throws StatusException
	 */
	private function val(TypeConstraint $typeConstraint, int $rejectStatus = Response::STATUS_404_NOT_FOUND) {
		try {
			return $typeConstraint->validate($this->rawValue);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new StatusException($rejectStatus, $e->getMessage(), null, $e);
		}
	}

	/**
	 * @throws StatusException
	 */
	function toString(int $rejectStatus = Response::STATUS_404_NOT_FOUND): string {
		return $this->val(TypeConstraints::string(), $rejectStatus);
	}

	/**
	 * @throws StatusException
	 */
	function toInt(int $rejectStatus = Response::STATUS_404_NOT_FOUND): int {
		return $this->val(TypeConstraints::int(false, true), $rejectStatus);
	}

	/**
	 * @throws StatusException
	 */
	function toFloat(int $rejectStatus = Response::STATUS_404_NOT_FOUND): float {
		return $this->val(TypeConstraints::float(false, true), $rejectStatus);
	}

	/**
	 * @return bool
	 * @throws StatusException
	 */
	public function toBool(string $trueStr = '1', string $falseStr = '0',
			int $status = Response::STATUS_404_NOT_FOUND): bool {
		try {
			ArgUtils::valEnum($this->rawValue, [$trueStr, $falseStr]);
			return $this->rawValue === $trueStr;
		} catch (\InvalidArgumentException $e) {
			throw new StatusException($status, $e->getMessage(), null, $e);
		}
	}

	/**
	 * @throws StatusException
	 * @deprecated use {@link self::toBool()}
	 */
	public function toBoolOrReject(string $trueStr = '1', string $falseStr = '0',
			int $status = Response::STATUS_404_NOT_FOUND): bool {
		return $this->toBool($trueStr, $falseStr, $status);
	}

	/**
	 * @throws StatusException
	 * @deprecated use {@link self::toInt()}
	 */
	public function toIntOrReject($status = Response::STATUS_404_NOT_FOUND) {
		if (false !== ($value = filter_var($this->value, FILTER_VALIDATE_INT))) {
			return $value;
		}
		
		throw new StatusException($status);
	}

	/**
	 * @throws StatusException
	 */
	public function toIntOrNull($rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		return $this->toIntOrReject($rejectStatus);
	}

	/**
	 * @throws StatusException
	 * @deprecated use {@link self::toFloat()}
	 */
	public function toFloatOrReject($status = Response::STATUS_404_NOT_FOUND) {
		if (false !== ($value = filter_var($this->value, FILTER_VALIDATE_FLOAT))) {
			return $value;
		}
		
		throw new StatusException($status);
	}

	/**
	 * @throws StatusException
	 */
	public function toFloatOrNull($rejectStatus = Response::STATUS_404_NOT_FOUND) {
		if ($this->isEmptyString()) {
			return null;
		}
		
		return $this->toFloatOrReject($rejectStatus);
	}

	public function isNumeric(): bool {
		return is_numeric($this->value);
	}

	/**
	 * @throws StatusException
	 */
	public function rejectIfNotNumeric(): void {
		if ($this->isNumeric()) return;
		
		throw new StatusException('Param not numeric');
	}

	/**
	 * @param int $status
	 * @return string
	 * @throws StatusException
	 * @deprecated use {@link self::toNumeric()}
	 */
	public function toNumericOrReject(int $status = Response::STATUS_404_NOT_FOUND) {
		return $this->toNumeric($status);
	}

	/**
	 * @throws StatusException
	 */
	public function toNumeric($status = Response::STATUS_404_NOT_FOUND): string {
		$this->rejectIfNotNumeric();
		
		return $this->value;
	}

	/**
	 * @throws StatusException
	 */
	public function rejectIfNot($value, $status = Response::STATUS_404_NOT_FOUND): void {
		if ($this->value === $value) return;
		
		throw new StatusException($status, 'Param invalid');
	}

	/**
	 * @throws StatusException
	 */
	public function rejectIfNotNotEmptyString($status = Response::STATUS_404_NOT_FOUND): void {
		if ($this->isNotEmptyString()) return;
		
		throw new StatusException($status, 'Param not numeric');
	}
	
	public function isEmptyString(): bool {
		return $this->value === '';
	}
	
	public function isNotEmptyString(): bool {
		return is_scalar($this->value) && mb_strlen($this->value) > 0;
	}
	
	private function valNotEmptyString($value): bool {
		return is_scalar($value) && mb_strlen($value) > 0;
	}

	/**
	 * @param int $status
	 * @return string
	 * @throws StatusException
	 * @deprecated
	 */
	public function toNotEmptyStringOrReject(int $status = Response::STATUS_404_NOT_FOUND): string {
		return $this->toNotEmptyString($status);
	}

	/**
	 * @param string[]|\UnitEnum[] $options
	 * @param int $rejectStatus
	 * @return string|\UnitEnum
	 * @throws StatusException
	 */
	function toEnum(array $options, int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {
		try {
			return EnumUtils::valueToPseudoUnit($this->rawValue, $options);
		} catch (\InvalidArgumentException $e) {
			throw new StatusException($rejectStatus, $e->getMessage(), null, $e);
		}
	}

	/**
	 * @param int $status
	 * @return string
	 * @throws StatusException
	 */
	public function toNotEmptyString(int $status = Response::STATUS_404_NOT_FOUND): string {
		$this->rejectIfNotNotEmptyString($status);
		
		return $this->value;
	}

	/**
	 * @throws StatusException
	 */
	public function toNotEmptyStringOrNull(int $rejectStatus = Response::STATUS_404_NOT_FOUND): ?string {
		if ($this->isEmptyString()) {
			return null;
		}
		
		// value could be an array
		return $this->toNotEmptyString($rejectStatus);
	}


	/**
	 * @param int $rejectStatus
	 * @return array
	 * @throws StatusException
	 */
	public function toArray(int $rejectStatus = Response::STATUS_404_NOT_FOUND): array {
		if (is_array($this->value)) {
			return $this->value;
		}
		
		throw new StatusException($rejectStatus, 'Param not array');
	}

	public function getValue(): array|string|null {
		return $this->value;
	}


	/**
	 * @param int $status
	 * @return array
	 * @throws StatusException
	 * @deprecated use {@see self::toStringArray()}
	 */
	public function toStringArrayOrReject(int $status = Response::STATUS_404_NOT_FOUND): array {
		return $this->toStringArray($status);
	}
	
	/**
	 * @param int $rejectStatus
	 * @return array
	 * @throws StatusException
	 */
	public function toStringArray(int $rejectStatus = Response::STATUS_404_NOT_FOUND): array {
		if (!is_array($this->value)) {
			throw new StatusException($rejectStatus);
		}

		foreach ($this->value as $fieldValue) {
			if (!is_string($fieldValue)) {
				throw new StatusException($rejectStatus);
			}
		}
		
		return $this->value;
	}
	
	/**
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 * @deprecated use {@see self::toIntArray()}
	 */
	public function toIntArrayOrReject(int $status = Response::STATUS_404_NOT_FOUND): array {
		return $this->toIntArray($status);
	}
	
	/**
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 */
	public function toIntArray(int $status = Response::STATUS_404_NOT_FOUND): array {
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
	 * @deprecated use {@see self::splitToStringArray()}
	 */
	public function splitToStringArrayOrReject(string $separator = ',', bool $sorted = true,
			int $status = Response::STATUS_404_NOT_FOUND): array {
		return $this->splitToStringArray($separator, $sorted, $status);
	}
	
	/**
	 * @param string $separator
	 * @param bool $sorted
	 * @param int $status
	 * @throws StatusException
	 * @return array
	 */
	public function splitToStringArray(string $separator = ',', bool $sorted = true, 
			int $status = Response::STATUS_404_NOT_FOUND): array {
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
	 * @throws StatusException
	 * @deprecated use {@see self::splitToIntArray()}
	 */
	public function splitToIntArrayOrReject(string $separator = '-', bool $sorted = true, 
			int $status = Response::STATUS_404_NOT_FOUND): array {
		return $this->splitToIntArray($separator, $sorted, $status);
	}

	/**
	 * @throws StatusException
	 */
	public function splitToIntArray(string $separator = '-', bool $sorted = true,
			int $status = Response::STATUS_404_NOT_FOUND): array {
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
	 * @return array
	 * @throws StatusException
	 * @deprecated use {@see self::toNotEmptyStringArray()}
	 */
	public function toNotEmptyStringArrayOrReject(int $errStatus = Response::STATUS_404_NOT_FOUND): array {
		return $this->toNotEmptyStringArray($errStatus);
	}

	/**
	 * @param int $errStatus
	 * @return array
	 * @throws StatusException
	 */
	public function toNotEmptyStringArray(int $errStatus = Response::STATUS_404_NOT_FOUND): array {
		$values = $this->toArray($errStatus);
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
	public function parseJsonOrReject(int $errStatus = Response::STATUS_400_BAD_REQUEST, bool $assoc = true): object|array {
		return $this->parseJson($errStatus, $assoc);
	}
	
	/**
	 * @param int $errStatus
	 * @param bool $assoc
	 * @throws StatusException
	 * @return array|object
	 */
	public function parseJson(int $errStatus = Response::STATUS_400_BAD_REQUEST, bool $assoc = true): object|array {
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
	 * @return Attributes
	 */
	public function parseJsonToAttrsOrReject(int $errStatus = Response::STATUS_400_BAD_REQUEST): Attributes {
		return new Attributes($this->parseJson($errStatus, true));
	}
	
	/**
	 * @param int $errStatus
	 * @throws StatusException
	 * @return DataSet
	 */
	public function parseJsonToDataSet(int $errStatus = Response::STATUS_400_BAD_REQUEST): DataSet {
		return new DataSet($this->parseJson($errStatus, true));
	}
	
	/**
	 * @param int $errStatus
	 * @throws StatusException
	 * @return DataMap
	 */
	public function parseJsonToDataMap(int $errStatus = Response::STATUS_400_BAD_REQUEST): DataMap {
		return new DataMap($this->parseJson($errStatus, true));
	}

	/**
	 * @param int $errStatus
	 * @return HttpData
	 * @throws StatusException
	 */
	function parseJsonToHttpData(int $errStatus = Response::STATUS_400_BAD_REQUEST): HttpData {
		return new HttpData($this->parseJsonToDataMap($errStatus), $errStatus);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->value;
	}

	function getRawValue(): array|string {
		return $this->rawValue;
	}

	/**
	 * @throws StatusException
	 */
	function parseDateTimeImmutable(int $errStatus = Response::STATUS_400_BAD_REQUEST): DateTimeImmutable {
		try {
			return DateUtils::createDateTimeImmutableFromFormat(DateUtils::SQL_DATE_TIME_FORMAT,
					$this->toString($errStatus));
		} catch (DateParseException $e) {
			throw new StatusException($errStatus, previous: $e);
		}
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param int $rejectStatus
	 * @throws StatusException
	 * @return T
	 */
	function toStringValueObject(string $className, int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {
		return $this->parseValueObject($this->toString($rejectStatus), $className, StringValueObject::class,
				$rejectStatus);
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param int $rejectStatus
	 * @throws StatusException
	 * @return T
	 */
	function toIntValueObject(string $className, int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {
		return $this->parseValueObject($this->toInt($rejectStatus), $className, IntValueObject::class,
				$rejectStatus);
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param int $rejectStatus
	 * @throws StatusException
	 * @return T
	 */
	function toFloatValueObject(string $className, int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {
		return $this->parseValueObject($this->toFloat($rejectStatus), $className, FloatValueObject::class,
				$rejectStatus);
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param int $rejectStatus
	 * @throws StatusException
	 * @return T
	 */
	function toBoolValueObject(string $className, int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {
		return $this->parseValueObject($this->toBool(status: $rejectStatus), $className, BoolValueObject::class,
				$rejectStatus);
	}

	/**
	 * @throws StatusException
	 */
	private function parseValueObject(mixed $value, string $className, string $interfaceName,
			int $rejectStatus = Response::STATUS_404_NOT_FOUND): mixed {

		try {
			$class = new ReflectionClass($className);
		} catch (\ReflectionException $e) {
			throw new \InvalidArgumentException('Invalid value object class name passed: ' . $className,
					previous: $e);
		}

		if (!$class->implementsInterface($interfaceName)) {
			throw new \InvalidArgumentException($className. ' Class must implement ' . $interfaceName);
		}

		try {
			return $class->newInstance($value);
		} catch (\ReflectionException $e) {
			throw new IllegalStateException(previous: $e);
		} catch (IllegalValueException $e) {
			throw new StatusException($rejectStatus, $e->getMessage(), previous: $e);
		}
	}
}
