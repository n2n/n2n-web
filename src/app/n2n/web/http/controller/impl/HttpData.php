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
use n2n\util\type\attrs\DataSet;

class HttpData {

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
		
	public function readAttribute(AttributePath $path, TypeConstraint $typeConstraint = null, bool $mandatory = true, 
			$defaultValue = null) {
		try {
			return $this->dataMap->readAttribute($path, $typeConstraint, $mandatory, $defaultValue);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
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
		
		if (null !== ($value = $this->reqNumeric($path))) {
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
	
	public function reqEnum($path, array $allowedValues, bool $nullAllowed = false) {
		try {
			return $this->dataMap->getEnum($path, $allowedValues);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}
	
	public function optEnum($path, array $allowedValues, $defaultValue = null, bool $nullAllowed = true) {
		try {
			return $this->dataMap->getEnum($path, $allowedValues, false, $defaultValue, $nullAllowed);
		} catch (\n2n\util\type\attrs\AttributesException $e) {
			throw new StatusException($this->errStatus, $e->getMessage(), $e->getCode(), $e);
		}
	}
	
	public function reqArray($name, $fieldType = null, bool $nullAllowed = false) {
		return $this->req($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType));
	}
	
	public function optArray($name, $fieldType = null, $defaultValue = [], bool $nullAllowed = false) {
		return $this->opt($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType), $defaultValue);
	}
	
	public function reqScalarArray($name, bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->reqArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $nullAllowed);
	}
	
	public function optScalarArray($name, $defaultValue = [], bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->optArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $defaultValue, $nullAllowed);
	}
	
	/**
	 * @param string|AttributePath|string[] $path
	 * @param mixed $defaultValue
	 * @param bool $nullAllowed
	 * @return HttpData|null
	 */
	public function reqHttpData($path, bool $nullAllowed = false, int $errStatus = null) {
		if (null !== ($array = $this->reqArray($path, null, $nullAllowed))) {
			return new HttpData(new DataMap($array), $errStatus ?? $this->errStatus);
		}
		
		return null;
	}
	
	/**
	 * @param string|AttributePath|string[] $path
	 * @param mixed $defaultValue
	 * @param bool $nullAllowed
	 * @return \n2n\util\type\attrs\Attributes|null
	 */
	public function optDataSet($path, $defaultValue = null, bool $nullAllowed = true, int $errStatus = null) {
		if (null !== ($array = $this->optArray($path, null, $nullAllowed))) {
			return new HttpData(new DataSet($array), $errStatus ?? $this->errStatus);
		}
		
		return null;
	}
	
	/**
	 * 
	 * @return \n2n\util\type\attrs\DataMap
	 */
	function toDataMap() {
		return $this->dataMap;
	}
}
