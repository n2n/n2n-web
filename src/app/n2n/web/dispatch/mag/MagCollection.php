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
namespace n2n\web\dispatch\mag;

use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\map\bind\BindingDefinition;

class MagCollection {
	private $magWrappers = array();
	
	public function addMag(Mag $mag) {
		return $this->magWrappers[$mag->getPropertyName()] = new MagWrapper($mag);
	}
	
	public function getMagByPropertyName(string $propertyName) {
		return $this->getMagWrapperByPropertyName($propertyName)->getMag();
	}
	
	/**
	 * @param string $propertyName
	 * @throws UnknownMagException
	 * @return MagWrapper
	 */
	public function getMagWrapperByPropertyName(string $propertyName) {
		if ($this->containsPropertyName($propertyName)) {
			return $this->magWrappers[$propertyName];
		}
		throw new UnknownMagException('Mag not found: ' . $propertyName);
	}
	
	public function containsPropertyName($propertyName) {
		return isset($this->magWrappers[$propertyName]);
	}
	
	public function removeMagByPropertyName($propertyName) {
		unset($this->magWrappers[$propertyName]);
	}
	
	public function isEmpty() {
		return empty($this->magWrappers);
	}
	
	/**
	 * @return MagWrapper[]
	 */
	public function getMagWrappers() {
		return $this->magWrappers;
	}
	
	public function getPropertyNames() {
		return array_keys($this->magWrappers);
	}
	
	public function readFormValues() {
		$formValues = array();
		foreach ($this->magWrappers as $propertyName => $magWrapper) {
			$formValues[$propertyName] = $magWrapper->getMag()->getFormValue();
		}
		return $formValues;
	}
	
	public function readValues(array $propertyNames = null, bool $ignoreUnknown = false): array {
		$values = array();
		
		if ($propertyNames !== null) {
			foreach ($propertyNames as $propertyName) {
				if ($ignoreUnknown && !$this->containsPropertyName($propertyName)) {
					continue;
				}
				
				$values[$propertyName] = $this->getMagWrapperByPropertyName($propertyName)->getMag()->getValue();
			}
			return $values;
		}
		
		foreach ($this->magWrappers as $propertyName => $magWrapper) {
			$values[$propertyName] = $magWrapper->getMag()->getValue();
		}
		return $values;
	}
	
	public function writeValues(array $values) {
		foreach ($this->magWrappers as $propertyName => $magWrapper) {
			if (!array_key_exists($propertyName, $values)) continue;
			$magWrapper->getMag()->setValue($values[$propertyName]);
		}
	}
	

	public function setupMappingDefinition(MappingDefinition $md) {
		foreach ($this->magWrappers as $propertyName => $magWrapper) {
			$magWrapper->setupMappingDefinition($md);
		}
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		foreach ($this->magWrappers as $propertyName => $magWrapper) {
			$magWrapper->setupBindingDefinition($bd);
		}
	}
}
