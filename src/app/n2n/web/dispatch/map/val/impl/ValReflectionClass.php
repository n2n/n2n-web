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
namespace n2n\dispatch\map\val\impl;

use n2n\reflection\ReflectionUtils;
use n2n\core\TypeNotFoundException;
use n2n\dispatch\map\val\SimplePropertyValidator;
use n2n\l10n\MessageCode;

class ValReflectionClass extends SimplePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValReflectionClass';
	
	private $isAClass;
	private $required;
	
	public function __construct(\ReflectionClass $isAClass = null, $required = false) {
		$this->isAClass = $isAClass;
		$this->required = $required;
	}

	protected function validateValue($value) {
		if (null == $value && !$this->required) return;
		$errMsg = null;
		try {
			if (null !== $this->isAClass) {
				$isAClass = $this->isAClass;
				$class = ReflectionUtils::createReflectionClass($value);
				if (ReflectionUtils::isClassA(new \ReflectionClass($class), $isAClass) 
						|| $isAClass->isInterface() && $class->implementsInterface($isAClass)) return;
				
				$errMsg = new MessageCode('n2n_error_dispatch_reflection_class_incorrect_type',
						array('expected_type' => $isAClass->getName(), 'given_type' => $class->getName()));
			}
		} catch (TypeNotFoundException $e) {}
		
		if (null === $errMsg) {
			$errMsg = new MessageCode('n2n_error_dispatch_reflection_class_invalid',
					array('type' => $value));
		}
		
		$this->failed($errMsg);
	}
}
