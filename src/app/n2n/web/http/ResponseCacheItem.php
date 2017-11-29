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

use n2n\web\http\payload\BufferedPayload;

class ResponseCacheItem extends BufferedPayload {
	private $contents;
	private $statusCode;
	private $headers;
	private $httpCacheControl;
	private $expireTimestamp;
	/**
	 * @param string $contents
	 * @param int $statusCode
	 * @param array $headers
	 * @param HttpCacheControl $httpCacheControl
	 * @param \DateTime $expireDate
	 */
	public function __construct($contents, $statusCode, array $headers, HttpCacheControl $httpCacheControl = null, \DateTime $expireDate) {
		$this->contents = $contents;
		$this->statusCode = $statusCode;
		$this->headers = $headers;
		$this->httpCacheControl = $httpCacheControl;
		$this->expireTimestamp = $expireDate->getTimestamp();
	}
	/**
	 * @return HttpCacheControl
	 */
	public function getHttpCacheControl() {
		return $this->httpCacheControl;
	}
	/**
	 * @param \DateTime $now
	 * @return boolean
	 */
	public function isExpired(\DateTime $now) {
		return $this->expireTimestamp < $now->getTimestamp();
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\BufferedPayload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		return $this->contents;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::prepareForResponse()
	 */
	public function prepareForResponse(\n2n\web\http\Response $response) {
		$response->setStatus($this->statusCode);
		foreach ($this->headers as $header) {
			$response->setHeader($header);
		}

		$response->setHttpCacheControl($this->httpCacheControl);

	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return 'Cached response';
	}
}
