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
use n2n\util\type\attrs\AttributePath;
use n2n\util\type\attrs\DataMap;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\type\attrs\AttributesException;
use n2n\util\type\attrs\AttributeWriter;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;

class HttpData implements AttributeReader, AttributeWriter {

	private $dataMap;
	private $errStatus;
	
	/**
	 *
	 * @param array $attrs
	 */
	public function __construct(DataMap $dataMap, int $errStatus = Response::STATUS_400_BAD_REQUEST) {
		$this->dataMap = $dataMap;
		$this->errStatus = $errStatus;
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
	 * @see \n2n\util\type\attrs\AttributeReader::containsAttribute()
	 */
	function containsAttribute(AttributePath $path): bool {
		return $this->has($path);
	}
		
	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link StatusException} to implement
	 * {@link AttributeReader} correclty.</strong> For safe usage where only {@link StatusException} are desired
	 * use {@link self::req()} instead.
	 * 
	 * {@inheritDoc}
	 * @see \n2n\util\type\attrs\AttributeReader::readAttribute()
	 */
	function readAttribute(AttributePath $path, TypeConstraint $typeConstraint = null, bool $mandatory = true, 
			$defaultValue = null) {
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
	function has($path) {
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
		} catch (\n2n\util\type\attrs\AttributesException $e) {
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
		} catch (\n2n\util\type\attrs\AttributesException $e) {
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
		} catch (\n2n\util\type\attrs\AttributesException $e) {
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
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
		return $this;
	}


	/**
	 * @param string $name
	 * @param bool $mandatory
	 * @param mixed $defaultValue
	 * @param TypeConstraint $typeConstraint
	 * @throws StatusException
	 * @return mixed
	 */
	public function req($path, $type = null) {
		try {
			return $this->dataMap->req($path, $type);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}
	
	public function opt($path, $type = null, $defaultValue = null) {
		try {
			return $this->dataMap->opt($path, $type, $defaultValue);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}
	
	public function reqScalar($path, bool $nullAllowed = false) {
		return $this->req($path, TypeConstraint::createSimple('scalar', $nullAllowed));
	}
	
	public function optScalar($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->opt($path, TypeConstraint::createSimple('scalar', $nullAllowed));
	}
	
	public function getString($path, bool $mandatory = true, $defaultValue = null, bool $nullAllowed = false) {
		if ($mandatory) {
			return $this->reqString($path, $nullAllowed);
		}
		
		return $this->optString($path, $defaultValue, $nullAllowed);
	}
	
	public function reqString($name, bool $nullAllowed = false, bool $lenient = true) {
		if (!$lenient) {
			return $this->req($name, TypeConstraint::createSimple('string', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqScalar($name, $nullAllowed))) {
			return (string) $value;
		}
		
		return null;
	}
	
	public function optString($path, $defaultValue = null, $nullAllowed = true, bool $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('string', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optScalar($path, $defaultValue, $nullAllowed))) {
			return (string) $value;
		}
		
		return null;
	}
	
	public function reqBool($path, bool $nullAllowed = false, $lenient = true) {
		if (!$lenient) {
			return $this->req($path, TypeConstraint::createSimple('bool', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqScalar($path, $nullAllowed))) {
			return (bool) $value;
		}
		
		return null;
	}
	
	public function optBool($path, $defaultValue = null, bool $nullAllowed = true, $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('bool', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optScalar($path, $defaultValue, $nullAllowed))) {
			return (bool) $value;
		}
		
		return $defaultValue;
	}
	
	public function reqNumeric($path, bool $nullAllowed = false) {
		return $this->req($path, TypeConstraint::createSimple('numeric', $nullAllowed));
	}
	
	public function optNumeric($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->opt($path, TypeConstraint::createSimple('numeric', $nullAllowed), $defaultValue);
	}
	
	public function reqInt($path, bool $nullAllowed = false, $lenient = true) {
		if (!$lenient) {
			return $this->req($path, TypeConstraint::createSimple('int', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqNumeric($path, $nullAllowed))) {
			return (int) $value;
		}
		
		return null;
	}
	
	public function optInt($path, $defaultValue = null, bool $nullAllowed = true, $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('int', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optNumeric($path, $defaultValue))) {
			return (int) $value;
		}
		
		return null;
	}

	/**
	 * @throws StatusException
	 */
	public function reqEnum($path, array $allowedValues, bool $nullAllowed = false) {
		try {
			return $this->dataMap->reqEnum($path, $allowedValues, $nullAllowed);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws StatusException
	 */
	public function optEnum($path, array $allowedValues, $defaultValue = null, bool $nullAllowed = true) {
		try {
			return $this->dataMap->optEnum($path, $allowedValues, $defaultValue, $nullAllowed);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws StatusException
	 */
	public function reqArray($name, $fieldType = null, bool $nullAllowed = false) {
		return $this->req($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType));
	}

	/**
	 * @throws StatusException
	 */
	public function optArray($name, $fieldType = null, $defaultValue = [], bool $nullAllowed = false) {
		return $this->opt($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType), $defaultValue);
	}

	/**
	 * @throws StatusException
	 */
	public function reqScalarArray($name, bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->reqArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $nullAllowed);
	}

	/**
	 * @throws StatusException
	 */
	public function optScalarArray($name, $defaultValue = [], bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->optArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $defaultValue, $nullAllowed);
	}

	/**
	 * @param string|AttributePath|string[] $path
	 * @param bool $nullAllowed
	 * @param int|null $errStatus
	 * @return HttpData|null
	 * @throws StatusException
	 */
	public function reqHttpData($path, bool $nullAllowed = false, int $errStatus = null): ?HttpData {
		if (null !== ($array = $this->reqArray($path, null, $nullAllowed))) {
			return new HttpData(new DataMap($array), $errStatus ?? $this->errStatus);
		}
		
		return null;
	}

	/**
	 * @param string|AttributePath|string[] $path
	 * @param mixed|null $defaultValue
	 * @param bool $nullAllowed
	 * @param int|null $errStatus
	 * @return HttpData|null
	 */
	public function optHttpData($path, mixed $defaultValue = null, bool $nullAllowed = true, int $errStatus = null): ?HttpData {
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
	
	public function optHttpDatas($path, $defaultValue = [], bool $nullAllowed = false) {
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
