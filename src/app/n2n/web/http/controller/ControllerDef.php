<?php
///*
// * Copyright (c) 2012-2016, Hofmänner New Media.
// * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
// *
// * This file is part of the N2N FRAMEWORK.
// *
// * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
// * the GNU Lesser General Public License as published by the Free Software Foundation, either
// * version 2.1 of the License, or (at your option) any later version.
// *
// * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
// * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
// *
// * The following people participated in this project:
// *
// * Andreas von Burg.....: Architect, Lead Developer
// * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
// * Thomas Günther.......: Developer, Hangar
// */
//namespace n2n\web\http\controller;
//
//use n2n\web\http\SubsystemRule;
//
//class ControllerDef {
//
//	public function __construct(private string $controllerClassName, private ?string $subsystemName,
//			private ?string $subsystemRuleName, private string $contextPath) {
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getControllerClassName() {
//		return $this->controllerClassName;
//	}
//
//	/**
//	 * @return string|null
//	 */
//	public function getSubsystemName() {
//		return $this->subsystemName;
//	}
//
//	/**
//	 * @return string|null
//	 */
//	function getSubsystemRuleName() {
//		return $this->subsystemRuleName ;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getContextPath() {
//		return $this->contextPath;
//	}
//
//	/**
//	 * @param string|null $subsystemName
//	 * @param string|null $subsystemRuleName
//	 * @return bool
//	 */
//	function acceptableBy(?string $subsystemName, ?string $subsystemRuleName) {
//		return ($this->subsystemName === null || $this->subsystemName === $subsystemName)
//				&& ($this->subsystemRuleName === null || $this->subsystemRuleName === $subsystemRuleName);
//	}
//}
