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
namespace n2n\web\dispatch\property;

use n2n\web\dispatch\DynamicDispatchable;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\TypeConstraint;
use n2n\util\type\ArgUtils;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\reflection\property\PropertyValueTypeMismatchException;
use n2n\util\type\TypeUtils;
use n2n\util\ex\UnsupportedOperationException;

class DynamicAccessProxy implements AccessProxy {
	private $propertyName;
	private $constraint;
	private $nullReturnAllowed;
	
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		$this->constraint = TypeConstraint::createSimple(null);
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
	public function getConstraint(): TypeConstraint {
		return $this->constraint;
	}

	function isConstant(): bool {
		return false;
	}

	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::setConstraint()
	 */
	public function setConstraint(TypeConstraint $constraint) {
		$this->constraint = $constraint;
	}
	
	public function isNullReturnAllowed() {
		return $this->nullReturnAllowed;
	}
	
	public function setNullReturnAllowed(bool $nullReturnAllowed): void {
		$this->nullReturnAllowed = $nullReturnAllowed;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\reflection\property\AccessProxy::isWritable()
	 */
	public function isWritable(): bool {
		return true;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::setValue()
	 */
	public function setValue(object $object, mixed $value, bool $validate = true): void {
		ArgUtils::assertTrue($object instanceof DynamicDispatchable);
		if ($validate) {
			try {
				$value = $this->constraint->validate($value);
			} catch (ValueIncompatibleWithConstraintsException $e) {
				throw new PropertyValueTypeMismatchException('Could not pass invalid value for property \''
						. $this->propertyName . '\' to '
						. TypeUtils::prettyMethName(get_class($object), 'setPropertyValue'), 0, $e);
						
			}
		}
		
		$object->setPropertyValue($this->propertyName, $value);
	}

	/* (non-PHPdoc)
	 * @see \n2n\reflection\property\AccessProxy::getValue()
	 */
	public function getValue($object): mixed {
		ArgUtils::assertTrue($object instanceof DynamicDispatchable);
		$value = $object->getPropertyValue($this->propertyName);
		if ($this->nullReturnAllowed && $value === null) return $value;
		
		try {
			$value = $this->constraint->validate($value);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new PropertyValueTypeMismatchException(
					TypeUtils::prettyMethName(get_class($object), 'getPropertyValue') 
							. ' returns invalid value for property \'' . $this->propertyName . '\'', 0, $e);
		}
		return $value;
	}

	public function __toString(): string {	
		return 'AccessProxy [' . TypeUtils::prettyMethName(DynamicDispatchable::class, 'getPropertyValue') 
				. ', ' . TypeUtils::prettyMethName(DynamicDispatchable::class, 'getPropertyValue') . ']';
	}

	function getGetterConstraint(): TypeConstraint {
		return $this->constraint;
	}

	function isReadable(): bool {
		return true;
	}

	function getSetterConstraint(): TypeConstraint {
		return $this->constraint;
	}

	function createRestricted(?TypeConstraint $getterConstraint = null, ?TypeConstraint $setterConstraint = null): AccessProxy {
		throw new UnsupportedOperationException();
	}
}
