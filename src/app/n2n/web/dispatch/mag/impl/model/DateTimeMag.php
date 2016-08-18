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
namespace n2n\web\dispatch\mag\impl\model;

use n2n\web\dispatch\map\val\impl\ValIsset;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\view\impl\html\HtmlView;
use util\jquery\datepicker\DatePickerHtmlBuilder;
use n2n\web\dispatch\property\impl\DateTimeProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;

class DateTimeMag extends MagAdapter {
	private $mandatory;
	private $dateStyle;
	private $timeStyle;
	private $icuPattern;
	private $inputAttrs;
	
	public function __construct($propertyName, $label, $dateStyle = null, $timeStyle = null, 
			$icuPattern = null, \DateTime $value = null, $mandatory = false, array $inputAttrs = null) {
		parent::__construct($propertyName, $label, $value);
		$this->mandatory = $mandatory;
		$this->dateStyle = $dateStyle;
		$this->timeStyle = $timeStyle;
		$this->icuPattern = $icuPattern;
		$this->inputAttrs = $inputAttrs;
	}	
	
	public function getDateStyle() {
		return $this->dateStyle;
	}

	public function setDateStyle($dateStyle) {
		$this->dateStyle = $dateStyle;
	}

	public function getTimeStyle() {
		return $this->timeStyle;
	}

	public function setTimeStyle($timeStyle) {
		$this->timeStyle = $timeStyle;
	}

	public function getIcuPattern() {
		return $this->icuPattern;
	}

	public function setIcuPattern($icuPattern) {
		$this->icuPattern = $icuPattern;
	}

	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		$dateTimeProperty = new DateTimeProperty($accessProxy, false);
		
		if (isset($this->dateStyle)) {
			$dateTimeProperty->setDateStyle($this->dateStyle);
		}
		if (isset($this->timeStyle)) {
			$dateTimeProperty->setTimeStyle($this->timeStyle);
		}
		if (isset($this->icuPattern)) {
			$dateTimeProperty->setIcuPattern($this->icuPattern);
		}
		
		return $dateTimeProperty;
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValIsset());
		}
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $htmlView): UiComponent {
		$datePickerHtml = new DatePickerHtmlBuilder($htmlView);
		return $datePickerHtml->getFormDatePicker($propertyPath, $this->inputAttrs);
	}
}
