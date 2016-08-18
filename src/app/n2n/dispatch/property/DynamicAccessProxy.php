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
namespace n2n\dispatch\property;

use n2n\dispatch\DynamicDispatchable;
use n2n\reflection\property\AccessProxy;
use n2n\reflection\property\TypeConstraint;
use n2n\reflection\ArgUtils;
use n2n\reflection\property\ValueIncompatibleWithConstraintsException;
use n2n\reflection\property\PropertyValueTypeMissmatchException;
use n2n\reflection\ReflectionUtils;

class DynamicAccessProxy implements AccessProxy {
	private $propertyName;
	private $constraints;
	private $nullReturnAllowed;
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		$this->constraints = TypeConstraint::createSimple(null);
	}
	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::getPropertyName()
	 */
	public function getPropertyName(): string {
		return $this->propertyName;
	}
	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::getConstraint()
	 */
	public function getConstraint() {
		return $this->constraints;
	}

	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::setConstraint()
	 */
	public function setConstraint(TypeConstraint $constraints) {
		$this->constraints = $constraints;
	}
	
	public function isNullReturnAllowed() {
		return $this->nullReturnAllowed;
	}
	
	public function setNullReturnAllowed($nullReturnAllowed) {
		$this->nullReturnAllowed = $nullReturnAllowed;
	}
	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::setValue()
	 */
	public function setValue($object, $value, $validate = true) {
		ArgUtils::assertTrue($object instanceof DynamicDispatchable);
		if ($validate) {
			try {
				$this->constraints->validate($value);
			} catch (ValueIncompatibleWithConstraintsException $e) {
				throw new PropertyValueTypeMissmatchException('Could not pass invalid value for property \''
						. $this->propertyName . '\' to '
						. ReflectionUtils::prettyMethName(get_class($object), 'setPropertyValue'), 0, $e);
						
			}
		}
		
		$object->setPropertyValue($this->propertyName, $value);
	}

	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::getValue()
	 */
	public function getValue($object) {
		ArgUtils::assertTrue($object instanceof DynamicDispatchable);
		$value = $object->getPropertyValue($this->propertyName);
		if ($this->nullReturnAllowed && $value === null) return $value;
		
		try {
			$this->constraints->validate($value);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new PropertyValueTypeMissmatchException(
					ReflectionUtils::prettyMethName(get_class($object), 'getPropertyValue') 
							. ' returns invalid value for property \'' . $this->propertyName . '\'', 0, $e);
		}
		return $value;
	}

	public function __toString(): string {	
		return 'AccessProxy [' . ReflectionUtils::prettyMethName(DynamicDispatchable::class, 'getPropertyValue') 
				. ', ' . ReflectionUtils::prettyMethName(DynamicDispatchable::class, 'getPropertyValue') . ']';
	}
}
