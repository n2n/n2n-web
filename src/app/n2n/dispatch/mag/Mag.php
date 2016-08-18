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

use n2n\ui\view\impl\html\HtmlView;
use n2n\dispatch\map\PropertyPath;
use n2n\dispatch\map\BindingConstraints;
use n2n\dispatch\map\bind\MappingDefinition;
use n2n\reflection\property\AccessProxy;
use n2n\dispatch\map\bind\BindingDefinition;
use n2n\dispatch\property\ManagedProperty;
use n2n\ui\UiComponent;
use n2n\l10n\Label;
use n2n\l10n\N2nLocale;

interface Mag {
	/**
	 * @return string 
	 */
	public function getPropertyName(): string;
	/**
	 * @return string
	 */
	public function getLabel(N2nLocale $n2nLocale): string;
	/**
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty;
	/**
	 * @param MappingDefinition $mappingDefinition
	 */
	public function setupMappingDefinition(MappingDefinition $mappingDefinition);
	/**
	 * @param BindingConstraints $bindingConstraints
	 */
	public function setupBindingDefinition(BindingDefinition $bindingDefinition);
	/**
	 * @return mixed
	 */
	public function getFormValue();
	/**
	 * @param mixed $value
	 */
	public function setFormValue($value);
	/**
	 * @return array 
	 */
	public function getContainerAttrs(HtmlView $view): array;
	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view): UiComponent;
	/**
	 * @return mixed 
	 */
	public function getValue();
	/**
	 * @param mixed $value
	 * @throws \InvalidArgumentException if passed value is invalid.
	 */
	public function setValue($value);
}
