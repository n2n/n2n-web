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
namespace n2n\web\http;

use n2n\l10n\Message;
use n2n\io\IoUtils;

class UploadDefinition {
	private $errorNo;
	private $name;
	private $tmpName;
	private $type;
	private $size;
	
	public function __construct($errorNo, $name, $tmpName, $type, $size) {
		$this->errorNo = $errorNo;
		$this->name = $name;
		$this->tmpName = $tmpName;
		$this->type = $type;
		$this->size = $size;
	}
	
	public function getErrorNo() {
		return $this->errorNo;
	}

	public function getName() {
		return $this->name;
	}
	
	function setName(string $name) {
		$this->name = $name;
	}

	public function getTmpName() {
		return $this->tmpName;
	}

	public function getType() {
		return $this->type;
	}

	public function getSize() {
		return $this->size;
	}
	
	public function isOk() {
		return $this->errorNo == UPLOAD_ERR_OK;
	}
	
	/**
	 * @return boolean
	 */
	public function hasClientError() {
		switch ($this->errorNo) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_PARTIAL:
				return true;
			default:
				return false;
		}	
	}
	
	/**
	 * @return Message|null 
	 */
	public function buildClientErrorMessage() {
		$maxSize = null;
		switch ($this->errorNo) {
			case UPLOAD_ERR_INI_SIZE:
				$maxSize = IoUtils::prettySize(IoUtils::determineFileUploadMaxSize());
			case UPLOAD_ERR_FORM_SIZE:
				return Message::createCodeArg(self::MAX_SIZE_ERROR_CODE, 
						array('maxSize' => $maxSize,
								'file_name' => $this->getName(),
								'size' => $this->getSize()),
						'n2n\web\http');
						
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_PARTIAL:
				return Message::createCodeArg(self::INCOMPLETE_ERROR_CODE, 
						array('file_name' => $uploadDefinition->getName()),
						'n2n\web\http');
						
			default: 
				return null;
		}	
		
	}
}
