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
namespace n2n\web\http\payload\impl;

use n2n\web\http\Response;
use n2n\io\managed\File;
use n2n\core\N2nVars;
use n2n\web\http\payload\ResourcePayload;
use n2n\util\type\ArgUtils;
use n2n\io\IoUtils;
use n2n\util\ex\NotYetImplementedException;

class FilePayload extends ResourcePayload {
	private $file;
	private $attachment;
	private $attachmentName;
	
	public function __construct(File $file, bool $attachment = false, string $attachmentName = null) {
		ArgUtils::valScalar($attachment, true, 'attachment');
		$this->file = $file;
		$this->attachment = $attachment;
		$this->attachmentName = $attachmentName;
	}
	
	public function prepareForResponse(Response $response) {
		$mimeType = N2nVars::getMimeTypeDetector()->getMimeTypeByExtension($this->file->getOriginalExtension());
		
		if (isset($mimeType)) {
			$response->setHeader('Content-Type: ' . $mimeType);
		} else {
			$response->setHeader('Content-Type: application/octet-stream');
		}
		
		$response->setHeader('Content-Length: ' . $this->file->getFileSource()->getSize());
		
		if ($this->attachment) {
			$attachmentName = $this->attachmentName ?? $this->file->getOriginalName();
			if (IoUtils::hasSpecialChars($attachmentName)) {
				throw new NotYetImplementedException('RFC-2231 encoding not yet implemented');
			}
			
			$response->setHeader('Content-Disposition: attachment;filename="' . $attachmentName . '"');
		}
	}
	
	public function toKownPayloadString(): string {
		return $this->file->getOriginalName() . ' (' . $this->file->getFileSource()->__toString() . ')';
	}
	
	public function responseOut() {
		echo $this->file->getFileSource()->out();
	}
	
	/* (non-PHPdoc)
	 * @see n2n\web\http.ResourcePayload::getEtag()
	 */
	public function getEtag() {
		return $this->file->getFileSource()->buildHash();
	}
	
	public function getLastModified() {
		return $this->file->getFileSource()->getLastModified();
	}
}
