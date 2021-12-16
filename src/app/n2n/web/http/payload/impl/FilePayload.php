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
use n2n\web\http\payload\ResourcePayload;
use n2n\util\type\ArgUtils;
use n2n\util\io\IoUtils;
use n2n\util\ex\NotYetImplementedException;
use n2n\util\io\Downloadable;
use n2n\core\N2nVars;

class FilePayload extends ResourcePayload {
	private $downloadable;
	private $attachment;
	private $attachmentName;
	
	public function __construct(Downloadable $downlodable, bool $attachment = false, string $attachmentName = null) {
		ArgUtils::valScalar($attachment, true, 'attachment');
		$this->downloadable = $downlodable;
		$this->attachment = $attachment;
		$this->attachmentName = $attachmentName;
	}
	
	public function prepareForResponse(Response $response) {
		$mimeType = N2nVars::getMimeTypeDetector()->getMimeTypeByExtension($this->downloadable->getName())
				?? $this->downloadable->getMimeType() ?? 'application/octet-stream';

// 		if (isset($mimeType)) {
			$response->setHeader('Content-Type: ' . $mimeType);
// 		} else {
// 			$response->setHeader('Content-Type: application/octet-stream');
// 		}
		
		$response->setHeader('Content-Length: ' . $this->downloadable->getSize());
		
		if ($this->attachment) {
			$attachmentName = $this->attachmentName ?? $this->downloadable->getName();
			if (IoUtils::hasSpecialChars($attachmentName)) {
				throw new NotYetImplementedException('RFC-2231 encoding not yet implemented');
			}
			
			$response->setHeader('Content-Disposition: attachment;filename="' . $attachmentName . '"');
		}
	}
	
	public function toKownPayloadString(): string {
		return $this->downloadable->getName() . ' (' . $this->downloadable->__toString() . ')';
	}
	
	public function responseOut() {
		echo $this->downloadable->out();
	}
	
	public function getEtag() {
		return $this->downloadable->buildHash();
	}
	
	public function getLastModified() {
		return $this->downloadable->getLastModified();
	}
}
