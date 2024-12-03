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

use n2n\web\http\Response;

class ResponseCacheItem {
	private int $expireTimestamp;

	/**
	 * @param CachedPayload $cachedPayload
	 * @param string|null $verifierLookupId
	 */
	public function __construct(private CachedPayload $cachedPayload, private array $characteristics, private ?string $verifierLookupId = null) {
	}

	public function isExpired(\DateTimeInterface $now): bool {
		return $this->cachedPayload->isExpired($now)
				// invalidate cache of old serialized objects
				|| !isset($this->cachedPayload);
	}

	function getCharacteristics(): array {
		return $this->characteristics;
	}

	function hasVerifier(): bool {
		return $this->verifierLookupId !== null;
	}

	function getVerifierLookupId(): ?string {
		return $this->verifierLookupId;
	}

	function getCachedPayload(): CachedPayload {
		return $this->cachedPayload;
	}

	static function createFromSentPayload(Response $response, \DateTimeInterface $expireDateTime,
			array $characteristics, ?string $verifierLookupId = null): ResponseCacheItem {
		return new ResponseCacheItem(CachedPayload::createFromSentPayload($response, $expireDateTime),
				$characteristics, $verifierLookupId);
	}

}
