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
namespace n2n\web\http\cache;

use n2n\web\http\payload\BufferedPayload;
use n2n\util\type\ArgUtils;
use DateTime;
use n2n\web\http\HeaderJob;

class PayloadCacheItem extends BufferedPayload {
	private int $expireTimestamp;

	/**
	 * @param string $contents
	 * @param int $statusCode
	 * @param HeaderJob[] $headerJobs
	 * @param DateTime $expireDate
	 */
	public function __construct(private string $contents, private int $statusCode, private array $headerJobs,
			DateTime $expireDate) {
		ArgUtils::valArray($headerJobs, HeaderJob::class);
		$this->expireTimestamp = $expireDate->getTimestamp();
	}

	/**
	 * @param DateTime $now
	 * @return boolean
	 */
	public function isExpired(DateTime $now): bool {
		return $this->expireTimestamp < $now->getTimestamp();
	}

	function getBufferedContents(): string {
		return $this->contents;
	}

	function prepareForResponse(\n2n\web\http\Response $response): void {
		$response->setStatus($this->statusCode);
		foreach ($this->headerJobs as $headerJob) {
			$response->addHeaderJob($headerJob);
		}
	}

	 function toKownPayloadString(): string {
		return 'Cached payload';
	}
}
