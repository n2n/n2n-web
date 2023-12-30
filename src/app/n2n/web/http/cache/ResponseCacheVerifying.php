<?php

namespace n2n\web\http\cache;

use n2n\util\magic\MagicContext;
use n2n\context\LookupFailedException;
use n2n\web\http\InvalidResponseCacheVerifierException;

class ResponseCacheVerifying {

	function __construct(private MagicContext $magicContext) {

	}


	/**
	 * @throws InvalidResponseCacheVerifierException
	 */
	function assertVerifier(string $lookupId): void {
		try {
			$verifier = $this->magicContext->lookup($lookupId);
		} catch (LookupFailedException $e) {
			throw new InvalidResponseCacheVerifierException('Provided lookup id for ResponseCacheVerifier is invalid: '
					. $lookupId, previous: $e);
		}

		if (!($verifier instanceof ResponseCacheVerifier)) {
			throw new InvalidResponseCacheVerifierException('ResponseCacheVerifier must implement '
					. ResponseCacheVerifier::class . ': ' . get_class($verifier));
		}
	}

	function verifyValidity(ResponseCacheId $responseCacheId, ResponseCacheItem $item, \DateTimeInterface $now): bool {
		$verifierLookupId = $item->getVerifierLookupId();
		if ($verifierLookupId === null) {
			return true;
		}

		try {
			$verifier = $this->magicContext->lookup($verifierLookupId);
		} catch (LookupFailedException $e) {
			return false;
		}

		if (!($verifier instanceof ResponseCacheVerifier)) {
			return false;
		}

		return $verifier->verifyValidity($responseCacheId, $item, $now);

	}

}