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

namespace n2n\web\dispatch\mag;

use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\map\bind\BindingDefinition;

class MagWrapper {
	private $mag;
	private $markAttrs = array();
	private $ignored = false;
	
	private $lastMappingDefinition;
	
	public function __construct(Mag $mag) {
		$this->mag = $mag;
	}
	
	public function getMag() {
		return $this->mag;
	}
	
	public function addMarkAttrs(array $markAttrs) {
		$this->markAttrs = HtmlUtils::mergeAttrs($this->markAttrs, $markAttrs, true);
	}
	
	public function getMarkAttrs() {
		return $this->markAttrs;
	}
	
	public function setMarkAttrs(array $markAttrs) {
		$this->markAttrs = $markAttrs;
	}
	
	public function getContainerAttrs(HtmlView $view) {
		return HtmlUtils::mergeAttrs($this->markAttrs, $this->mag->getContainerAttrs($view), true);
	}
	
	public function setIgnored(bool $ignored) {
		$this->ignored = $ignored;
		
		if ($this->lastMappingDefinition === null) return;
		
		if ($ignored) {
			$this->lastMappingDefinition->ignore($this->mag->getPropertyName());
		} else {
			$this->lastMappingDefinition->removeIgnore($this->mag->getPropertyName());
		}
	}
	
	public function isIgnored() {
		return $this->ignored;
	}
	
	public function setupMappingDefinition(MappingDefinition $md) {
		$this->lastMappingDefinition = $md;
		
		if ($this->ignored) {
			$md->ignore($this->mag->getPropertyName());
		}
		$this->mag->setupMappingDefinition($md);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		$this->mag->setupBindingDefinition($bd);
	}
}
