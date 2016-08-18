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
namespace n2n\dispatch\mag\impl\model;

use n2n\dispatch\map\bind\MappingDefinition;
use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\mag\Mag;
use n2n\l10n\N2nLocale;
use n2n\l10n\Lstr;

abstract class MagAdapter implements Mag {
	protected $propertyName;
	protected $labelLstr;
	protected $containerAttrs = array();
	protected $value;
	
	public function __construct(string $propertyName, $labelLstr, $value = null, array $containerAttrs = null) {
		$this->propertyName = $propertyName;
		$this->labelLstr = Lstr::create($labelLstr);
		$this->containerAttrs = (array) $containerAttrs;
		$this->value = $value;
	}
	
	public function getPropertyName(): string {
		return $this->propertyName;
	}
	
	public function getLabel(N2nLocale $n2nLocale): string {
		return $this->labelLstr->t($n2nLocale);
	}
	
	public function setLabelLstr(Lstr $labelL10nStr) {
		$this->labelLstr = $labelL10nStr;
	}
	
	public function getContainerAttrs(HtmlView $view): array {
		return $this->containerAttrs;
	}
	
	public function setContainerAttrs(array $containerAttrs) {
		$this->containerAttrs = $containerAttrs;
	}
	/* (non-PHPdoc)
	 * @see \n2n\dispatch\mag\Mag::setupMappingDefinition()
	 */
	public function setupMappingDefinition(MappingDefinition $md) {
		$md->getMappingResult()->setLabel($this->propertyName, (string) $this->labelLstr);
	}
	/**
	 * @return mixed
	 */
	public function getFormValue() {
		return $this->value;
	}
	/* (non-PHPdoc)
	 * @see \n2n\dispatch\mag\Mag::setValue()
	 */
	public function setFormValue($formValue) {
		$this->value = $formValue;
	}
	/* (non-PHPdoc)
	 * @see \n2n\dispatch\mag\Mag::getValue()
	 */
	public function getValue() {
		return $this->value;
	}
	/* (non-PHPdoc)
	 * @see \n2n\dispatch\mag\Mag::setValue()
	 */
	public function setValue($value) {
		$this->value = $value;
	}
}
