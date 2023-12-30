<?php

namespace n2n\web\http\cache;

interface ResponseCacheVerifier {

	
	function verifyValidity(ResponseCacheId $responseCacheId, ResponseCacheItem $item, \DateTimeInterface $now): bool;
}