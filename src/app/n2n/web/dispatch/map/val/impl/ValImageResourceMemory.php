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
namespace n2n\web\dispatch\map\val\impl;

use n2n\core\N2N;
use n2n\io\managed\File;
use n2n\io\managed\img\ImageFile;
use n2n\web\dispatch\map\val\SimplePropertyValidator;

class ValImageResourceMemory extends SimplePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValImageResourceMemory';
	const DEFAULT_MEMORY_LIMIT = 33554432;
	const RESERVATED_MEMORY_SIZE = 1048576;
	
	private $errorMessage;
	
	public function __construct($errorMessage = null) {
		$this->errorMessage = $errorMessage;
	}
	
	private function getMemoryLimit() {
		$memoryLimitDef = trim(ini_get('memory_limit'));
		$memoryLimit = null;
		
		switch (mb_substr($memoryLimitDef, -1)) {
			case 'k':
			case 'K':
				$memoryLimit = (int) $memoryLimitDef * 1024;
				break;
			case 'm':
			case 'M':
				$memoryLimit = (int) $memoryLimitDef * 1048576;
				break;
			case 'g':
			case 'G':
				$memoryLimit = (int) $memoryLimitDef * 1073741824;
				break;
			default:
				$memoryLimit = (int) $memoryLimitDef;
				break;
		}
		
		if (empty($memoryLimit)) {
			$memoryLimit = self::DEFAULT_MEMORY_LIMIT;
		}
		
		return $memoryLimit;
	}

	protected function validateValue($mapValue) {
		if (!($mapValue instanceof File) || ($mapValue->isValid() 
				&& !$mapValue->getFileSource()->isImage())) return;

		if (!$mapValue->isValid()) {
			$this->failed('No file uploaded.', self::DEFAULT_ERROR_TEXT_CODE, array(), N2N::NS);
			return;
		}
				
		$imageFile = new ImageFile($mapValue);
		$requiredMemorySize = $imageFile->getImageSource()->calcResourceMemorySize();
		$memoryLimit = $this->getMemoryLimit();
		
		if (self::RESERVATED_MEMORY_SIZE + $requiredMemorySize > $memoryLimit) {
			$this->failed($this->errorMessage, self::DEFAULT_ERROR_TEXT_CODE, array(), N2N::NS);
		}
	}
}
