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
namespace n2n\dispatch\mag;

class MagCollection {
	private $mags = array();
	
	public function addMag(Mag $mag) {
		$this->mags[$mag->getPropertyName()] = $mag;
	}
	/**
	 * @param string $propertyName
	 * @throws UnknownMagException
	 * @return Option
	 */
	public function getMagByPropertyName($propertyName) {
		if ($this->containsPropertyName($propertyName)) {
			return $this->mags[$propertyName];
		}
		throw new UnknownMagException('Mag not found: ' . $propertyName);
	}
	
	public function containsPropertyName($propertyName) {
		return isset($this->mags[$propertyName]);
	}
	
	public function removeOptionByPropertyName($propertyName) {
		unset($this->mags[$propertyName]);
	}
	
	public function isEmpty() {
		return empty($this->mags);
	}
	
	/**
	 * @return Mag[]
	 */
	public function getMags() {
		return $this->mags;
	}
	
	public function getPropertyNames() {
		return array_keys($this->mags);
	}
	
	public function readFormValues() {
		$formValues = array();
		foreach ($this->mags as $propertyName => $mag) {
			$formValues[$propertyName] = $mag->getFormValue();
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
				
				$values[$propertyName] = $this->getMagByPropertyName($propertyName)->getValue();
			}
			return $values;
		}
		
		foreach ($this->mags as $propertyName => $mag) {
			$values[$propertyName] = $mag->getValue();
		}
		return $values;
	}
	
	public function writeValues(array $values) {
		foreach ($this->mags as $propertyName => $mag) {
			if (!array_key_exists($propertyName, $values)) continue;
			$mag->setValue($values[$propertyName]);
		}
	}
}
