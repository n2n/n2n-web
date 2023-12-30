<?php

namespace n2n\web\http;

use n2n\util\uri\Path;

interface ResponseCacheVerifier {

	
	function verifyValidity(ResponseCacheId $responseCacheId,
			array $characteristics, ResponseCacheItem $item, \DateTimeInterface $now): bool;
}