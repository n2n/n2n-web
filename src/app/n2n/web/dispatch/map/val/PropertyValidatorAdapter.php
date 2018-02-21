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
namespace n2n\web\dispatch\map\val;

use n2n\web\dispatch\property\ManagedProperty;
use n2n\util\ex\IllegalStateException;

abstract class PropertyValidatorAdapter implements PropertyValidator {	
	private $allowedTypeNames = null;
	private $array = null;
	private $managedProperties = null;
	
	protected function restrictType(array $allowedTypeNames = null, $array = null) {
		$this->allowedTypeNames = $allowedTypeNames;
		$this->array = $array;
	}
	
	private function isTypeAllowed(ManagedProperty $managedProperty) {
		if ($this->array !== null && $managedProperty->isArray() !== $this->array) {
			return false;
		}
		
		if ($this->allowedTypeNames === null) return true;
		
		foreach ($this->allowedTypeNames as $allowedTypeName) {
			if (is_a($managedProperty, $allowedTypeName)) {
				return true;
			}
		}
		
		return false;
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\context\dispatch\val.PropertyValidator::initialize()
	 */
	public function initialize(array $managedProperties) {
		foreach ($managedProperties as $managedProperty) {
			if ($this->isTypeAllowed($managedProperty)) continue;
			
			throw new ValidatorInitializationException('Validator ' . get_class($this) 
					. ' incompatible with type ' . get_class($managedProperty) . ' of property ' 
					. $managedProperty->getName() . '. Allowed types: ' 
					. implode(', ', (array) $this->allowedTypeNames));
		}
		
		$this->managedProperties = $managedProperties;
	}
	/**
	 * @throws IllegalStateException
	 */
	public function getManagedProperties() {
		if ($this->managedProperties === null) {
			throw new IllegalStateException('properties not initialized');
		}
		
		return $this->managedProperties;
	}
}
