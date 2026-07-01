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

use n2n\util\type\TypeConstraint;
use n2n\web\http\StatusException;
use n2n\web\http\Response;
use n2n\util\attr\AttributePath;
use n2n\util\attr\DataMap;
use n2n\util\attr\AttributeReader;
use n2n\util\attr\AttributesException;
use n2n\util\attr\AttributeWriter;

class HttpData implements AttributeReader, AttributeWriter {

	private DataMap $dataMap;

	public function __construct(DataMap|array $dataMap, private int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		if (is_array($dataMap)) {
			$this->dataMap = new DataMap($dataMap);
		} else {
			$this->dataMap = $dataMap;
		}
	}
	
// 	public function setInterceptor(?Interceptor $interceptor) {
// 		$this->interceptor = $interceptor;
// 		return $this;
// 	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->dataMap->isEmpty();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\util\attr\AttributeReader::containsAttribute()
	 */
	function containsAttribute(AttributePath $path): bool {
		return $this->has($path);
	}
		
	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link StatusException} to implement
	 * {@link AttributeReader} correctly.</strong> For safe usage where only {@link StatusException} are desired
	 * use {@link self::req()} instead.
	 * 
	 * {@inheritDoc}
	 * @see \n2n\util\attr\AttributeReader::readAttribute()
	 */
	function readAttribute(AttributePath $path, ?TypeConstraint $typeConstraint = null, bool $mandatory = true,
			mixed $defaultValue = null): mixed {
		return $this->dataMap->readAttribute($path, $typeConstraint, $mandatory, $defaultValue);
	}

	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link StatusException} to implement
	 * {@link AttributeReader} correclty.</strong>
	 *
	 * {@inheritDoc}
	 */
	function writeAttribute(AttributePath $path, mixed $value): void {
		$this->dataMap->writeAttribute($path, $value);
	}

	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link StatusException} to implement
	 * {@link AttributeReader} correclty.</strong>
	 *
	 * {@inheritDoc}
	 */
	function removeAttribute(AttributePath $path): bool {
		return $this->dataMap->removeAttribute($path);
	}

	/**
	 * @param string|AttributePath $path
	 * @return boolean
	 */
	function has($path): bool {
		return $this->dataMap->has($path);
	}

	/**
	 * @param array $paths
	 * @param \Closure $closure
	 * @return HttpData
	 */
	function mapStrings(array $paths, \Closure $closure) {
		try {
			$this->dataMap->mapStrings($paths, $closure);
		} catch (\n2n\util\attr\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	/**
	 * @param $path
	 * @param \Closure $closure
	 * @return HttpData
	 */
	function mapString($path, \Closure $closure) {
		try {
			$this->dataMap->mapString($path, $closure);
		} catch (\n2n\util\attr\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	/**
	 * @param $path
	 * @param bool $simpleWhitespacesOnly
	 * @return HttpData
	 */
	function cleanString($path, bool $simpleWhitespacesOnly = true) {
		try {
			$this->dataMap->cleanString($path, $simpleWhitespacesOnly);
		} catch (\n2n\util\attr\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
		return $this;
	}

	/**
	 * @param array $paths
	 * @param bool $simpleWhitespacesOnly
	 * @return HttpData
	 */
	function cleanStrings(array $paths, bool $simpleWhitespacesOnly = true) {
		try {
			$this->dataMap->cleanStrings($paths, $simpleWhitespacesOnly);
		} catch (\n2n\util\attr\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
		return $this;
	}

	/**
	 * @throws StatusException
	 */
	private function try(\Closure $closure): mixed {
		try {
			return $closure();
		} catch (\n2n\util\attr\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * @throws StatusException
	 */
	public function req(mixed $path, mixed $type = null): mixed {
		return $this->try(fn () => $this->dataMap->req($path, $type));
	}

	/**
	 * @throws StatusException
	 */
	public function opt(mixed $path, mixed $type = null, mixed $defaultValue = null) {
		return $this->try(fn () => $this->dataMap->opt($path, $type, $defaultValue));
	}

	/**
	 * @throws StatusException
	 */
	public function reqScalar($path, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqScalar($path, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optScalar($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optScalar($path, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqString($path, bool $nullAllowed = false, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->reqString($path, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function optString($path, $defaultValue = null, $nullAllowed = true, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->optString($path, $defaultValue, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function reqBool($path, bool $nullAllowed = false, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->reqBool($path, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function optBool($path, $defaultValue = null, bool $nullAllowed = true, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->optBool($path, $defaultValue, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function reqNumeric($path, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqNumeric($path, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optNumeric($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optNumeric($path, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqInt($path, bool $nullAllowed = false, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->reqInt($path, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function optInt($path, $defaultValue = null, bool $nullAllowed = true, bool $lenient = true) {
		return $this->try(fn () => $this->dataMap->optInt($path, $defaultValue, $nullAllowed, $lenient));
	}

	/**
	 * @throws StatusException
	 */
	public function reqEnum($path, array $allowedValues, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqEnum($path, $allowedValues, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optEnum($path, array $allowedValues, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optEnum($path, $allowedValues, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqArray($path, $fieldType = null, bool $nullAllowed = false, $keyType = null) {
		return $this->try(fn () => $this->dataMap->reqArray($path, $fieldType, $nullAllowed, $keyType));
	}

	/**
	 * @throws StatusException
	 */
	public function optArray($path, $fieldType = null, $defaultValue = [], bool $nullAllowed = false, $keyType = null) {
		return $this->try(fn () => $this->dataMap->optArray($path, $fieldType, $defaultValue, $nullAllowed, $keyType));
	}

	/**
	 * @throws StatusException
	 */
	public function reqScalarArray($path, bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqScalarArray($path, $nullAllowed, $fieldNullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optScalarArray($path, $defaultValue = [], bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->try(fn () => $this->dataMap->optScalarArray($path, $defaultValue, $nullAllowed, $fieldNullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqStringValueObject($path, string $typeName, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqStringValueObject($path, $typeName, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optStringValueObject($path, string $typeName, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optStringValueObject($path, $typeName, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqIntValueObject($path, string $typeName, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqIntValueObject($path, $typeName, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optIntValueObject($path, string $typeName, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optIntValueObject($path, $typeName, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqFloatValueObject($path, string $typeName, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqFloatValueObject($path, $typeName, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optFloatValueObject($path, string $typeName, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optFloatValueObject($path, $typeName, $defaultValue, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function reqBoolValueObject($path, string $typeName, bool $nullAllowed = false) {
		return $this->try(fn () => $this->dataMap->reqBoolValueObject($path, $typeName, $nullAllowed));
	}

	/**
	 * @throws StatusException
	 */
	public function optBoolValueObject($path, string $typeName, $defaultValue = null, bool $nullAllowed = true) {
		return $this->try(fn () => $this->dataMap->optBoolValueObject($path, $typeName, $defaultValue, $nullAllowed));
	}


	/**
	 * @throws StatusException
	 */
	public function reqHttpData($path, bool $nullAllowed = false, ?int $errStatus = null): ?HttpData {
		if (null !== ($array = $this->reqArray($path, null, $nullAllowed))) {
			return new HttpData(new DataMap($array), $errStatus ?? $this->errStatus);
		}
		
		return null;
	}

	/**
	 * @throws StatusException
	 */
	public function optHttpData($path, mixed $defaultValue = null, bool $nullAllowed = true, ?int $errStatus = null): ?HttpData {
		if (null !== ($array = $this->optArray($path, null, $defaultValue, $nullAllowed))) {
			return new HttpData(new DataMap($array), $errStatus ?? $this->errStatus);
		}
		
		return null;
	}

	/**
	 * @return HttpData[]
	 * @throws StatusException
	 */
	public function reqHttpDatas($path, bool $nullAllowed = false): array {
		return array_map(fn ($data) => new HttpData(new DataMap($data)),
				$this->reqArray($path, 'array', $nullAllowed));
	}

	/**
	 * @return HttpData[]
	 * @throws StatusException
	 */
	public function optHttpDatas($path, array $defaultValue = [], bool $nullAllowed = false): array {
		$httpDatas = $this->optArray($path, 'array', null, $nullAllowed);
		if ($httpDatas === null) {
			return $defaultValue;
		}
		
		return array_map(fn ($data) => new HttpData(new DataMap($data)), $httpDatas);
	}

	/**
	 * @param string $propName
	 * @param \Closure $valueCallback
	 * @param array $convertableExcpetionNames
	 * @return HttpData
	 */
	function trySet(string $propName, \Closure $valueCallback, array $convertableExcpetionNames = [\InvalidArgumentException::class]) {
		try {
			$this->dataMap->set($propName, $valueCallback());
		} catch (\Throwable $e) {
			foreach ($convertableExcpetionNames as $convertableExcpetionName) {
				if (is_a($e, $convertableExcpetionName)) {
					throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
				}
			}
			
			throw $e;
		}
		
		return $this;
	}
	
	
	/**
	 * 
	 * @return DataMap
	 */
	function toDataMap() {
		return $this->dataMap;
	}
}
