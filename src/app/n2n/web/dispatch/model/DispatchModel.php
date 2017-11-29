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
namespace n2n\web\dispatch\model;

use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\map\DispatchItemsFactory;

class DispatchModel {
	private $class;
	private $properties = array();
	private $dispatchItemFactory;
	/**
	 * @param \ReflectionClass $class
	 */
	public function __construct(\ReflectionClass $class) {
		$this->class = $class;
	}
	/**
	 * @return \ReflectionClass
	 */
	public function getClass() {
		return $this->class;
	}
	/**
	 * @param ManagedProperty $property
	 */
	public function addProperty(ManagedProperty $property) {
		$this->properties[$property->getName()] = $property;
	}
	/**
	 * @return ManagedProperty[]
	 */
	public function getProperties() {
		return $this->properties;
	}
	/**
	 * @param string $name
	 */
	public function removePropertyByName($name) {
		unset($this->properties[$name]);
	}
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function containsPropertyName($name) {
		return isset($this->properties[$name]);
	}
	/**
	 * @param string $name
	 * @throws UnknownManagedPropertyException
	 * @return ManagedProperty
	 */
	public function getPropertyByName($name) {
		if (!$this->containsPropertyName($name)) {
			throw new UnknownManagedPropertyException('No ManagedProperty \'' . $name 
					. '\' defined for class \'' . $this->class->getName() . '\'.');
		}
		
		return $this->properties[$name];
	}
	/**
	 * @param string $methodName
	 * @return boolean
	 */
	public function containsMethodName($methodName) {
		if (!$this->class->hasMethod($methodName)) return false;
		
		$method = $this->class->getMethod($methodName);
		return $method->isPublic() && !$method->isStatic();
	}
	
	public function getMethodByName($methodName) {
		if (!$this->class->hasMethod($methodName)) {
			throw new UnknownManagedMethodException('Unknown managed method: ' 
					. $this->class->getName() . '::' . $methodName . '()');
		}
		
		$method = $this->class->getMethod($methodName);
		if (!$method->isPublic()) {
			throw new UnknownManagedMethodException('Method is not public: '
					. $this->class->getName() . '::' . $methodName . '()');
		}		
		if ($method->isStatic()) {
			throw new UnknownManagedMethodException('Method is static: '
					. $this->class->getName() . '::' . $methodName . '()');
		}
		
		return $method;
	}
	
	public function getDispatchItemFactory() {
		if ($this->dispatchItemFactory === null) {
			$this->dispatchItemFactory = new DispatchItemsFactory($this);
		}
		return $this->dispatchItemFactory;
	}
}
