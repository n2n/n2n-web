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

use n2n\io\managed\File;
use n2n\io\managed\FileManager;

class ManagedFileMag extends FileMag {
	
	/**
	 * @var \n2n\io\fs\PersistableFileManager
	 */
	private $fileManager;
	private $value;
	
	
	public function __construct($label, FileManager $fileManager, array $allowedExtensions = null, 
			$checkImageResourceMemory = false, File $default = null, $required = false, array $inputAttrs = null) {
		parent::__construct($label, $allowedExtensions, $checkImageResourceMemory, $default, $required, $inputAttrs);
		$this->fileManager = $fileManager;
	}
	
	public function attributeValueToOptionValue($value) {
		if (null !== ($file = $this->fileManager->getByQualifiedName($value))) {
			$this->value = $value;
			return $file;
		}
		return null;
	}
	
	public function optionValueToAttributeValue($value) {
		if ((string) $value == $this->value) return (string) $value;
		if ($value === $this->value) return  $this->fileManager->getByQualifiedName($value);
		if (!($value instanceof File)) return $value;
		return $this->fileManager->persist($value);
	}
}
