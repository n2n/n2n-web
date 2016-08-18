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

use n2n\N2N;
use n2n\dispatch\map\val\ValidationUtils;
use n2n\dispatch\map\val\SimplePropertyValidator;

class ValEmail extends SimplePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValEmail';
	
	private $errorMessage;
	
	public function __construct($errorMessage = null) {
		$this->errorMessage = ValidationUtils::createMessage($errorMessage);
	}
	
	protected function validateValue($value) {
		if ($value === null || self::isEMail($value)) return;
	
		$this->failed($this->errorMessage, self::DEFAULT_ERROR_TEXT_CODE, array(), N2N::NS);
		$this->restrictType(array('n2n\dispatch\property\SimpleProperty'));
	}
	/**
	 * checks a string, if it is a valid e-mail address
	 *
	 * @param string $email
	 * @return bool
	 */
	public static function isEMail($email) {
		return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
		// email check, accepts "umlaute" in domain names
// 		$regex = '/^[\p{L}\\.\\-_0-9]+@[\p{L}\\-0-9\\.]+\\.[\p{L}0-9]+$/u';
// 		return preg_match($regex, (string) $email);
	}
// 	/**
// 	 * checks a string, if it is a valid url address
// 	 *
// 	 * @param string $url
// 	 * @return bool
// 	 */
// 	public static function isUrl($url) {
// 		$regex = '/^((mailto\:|(news|(ht|f)tp(s?))\://){1}\S+)$/i'';
// 		return preg_match($regex, $url);
// 	}
}
