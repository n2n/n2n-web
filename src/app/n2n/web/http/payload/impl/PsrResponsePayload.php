<?php

namespace n2n\web\http\payload\impl;

use n2n\web\http\payload\BufferedPayload;
use n2n\web\http\payload\ResourcePayload;
use n2n\web\http\payload\Payload;
use n2n\web\http\Response;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use n2n\util\type\ArgUtils;
use n2n\util\type\TypeConstraints;

class PsrResponsePayload implements Payload {
	function __construct(private ResponseInterface $responseInterface) {

	}

	public function prepareForResponse(Response $response): void {
		$response->setStatus($this->responseInterface->getStatusCode());

		$headersMap = $this->responseInterface->getHeaders();
		ArgUtils::valArrayReturn($headersMap, $this->responseInterface, 'getHeaders',
				TypeConstraints::array(false, 'string'));

		foreach ($headersMap as $headerName => $headerValues) {
			foreach ($headerValues as $headerValue) {
				$response->setHeader($headerName . ': ' .  $headerValue, false);
			}
		}
	}

	public function toKownPayloadString(): string {
		return 'PsrResponsePayload';
	}

	private const BUFFERABLE_BYTES_LIMIT = 102400;

	public function isBufferable(): bool {
		return $this->responseInterface->getBody()->getSize() <= self::BUFFERABLE_BYTES_LIMIT;
	}

	public function getBufferedContents(): string {
		return (string) $this->responseInterface->getBody();
	}

	public function responseOut(): void {
		echo (string) $this->responseInterface->getBody();
	}

	public function getEtag(): ?string {
		return null;
	}

	public function getLastModified(): ?\DateTime {
		return null;
	}
}
