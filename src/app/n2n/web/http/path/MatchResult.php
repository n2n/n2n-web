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
namespace n2n\web\http\path;

use n2n\util\uri\Path;

class MatchResult {
	private $paramValues;
	private $placeholderValues;
	private $usedPath;
	private $surplusPath;
	private $extension;
	
	public function __construct(array $paramValues, array $placeholderValues, 
			Path $usedPath, Path $surplusPath, $extension = null) {
		$this->paramValues = $paramValues;
		$this->placeholderValues = $placeholderValues;
		$this->usedPath = $usedPath;
		$this->surplusPath = $surplusPath;
		$this->extension = $extension;
	}
	
	public function getParamValues() {
		return $this->paramValues;
	}

	public function setParamValues($paramValues) {
		$this->paramValues = $paramValues;
	}

	public function hasPlaceholderValues() {
		return !empty($this->placeholderValues);
	}
	
	public function getPlaceholderValues() {
		return $this->placeholderValues;
	}

	public function setPlaceholderValues($placeholderValues) {
		$this->placeholderValues = $placeholderValues;
	}

	public function getUsedPath() {
		return $this->usedPath;
	}

	public function setUsedPath(Path $usedPath) {
		$this->usedPath = $usedPath;
	}

	public function getSurplusPath() {
		return $this->surplusPath;
	}

	public function setSurplusPath(Path $surplusPath) {
		$this->surplusPath = $surplusPath;
	}
	
	public function getExtension() {
		return $this->extension;
	}
	
	public function setExtension($extension) {
		$this->extension = $extension;
	}
}
