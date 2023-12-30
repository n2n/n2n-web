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

use n2n\core\N2nErrorException;
use n2n\util\HashUtils;
use n2n\core\N2nRuntimeException;
use n2n\util\io\ob\OutputBuffer;
use n2n\core\N2N;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\payload\Payload;
use n2n\util\col\ArrayUtils;
use n2n\reflection\ReflectionUtils;
use n2n\util\type\ArgUtils;
use Psr\Http\Message\ResponseInterface;
use n2n\web\http\payload\impl\PsrResponsePayload;
use DateTimeInterface;
use n2n\web\http\csp\ContentSecurityPolicy;
use n2n\util\ex\UnsupportedOperationException;
use n2n\util\io\ob\OutputBufferDisturbedException;
use n2n\util\ex\err\FancyError;
use n2n\web\http\err\HttpHeadersAlreadySentException;
use n2n\web\http\err\ResponseBufferIsClosed;
use n2n\web\http\cache\CachedPayload;

/**
 * Assembles the http response and gives you different tools to modify it according to your wishes.
 * n2n creates an object of this class in its initialization phase lets you access it over the {@see HttpContext}.
 */
class ManagedResponse extends Response {

	/**
	 * @var array<ResponseListener>
	 */
	private array $listeners = [];

	private bool $responseCachingEnabled = true;
	private bool $httpCachingEnabled = true;
	private bool $contentSecurityPolicyEnabled = true;
	private bool $sendEtagAllowed = true;
	private bool $sendLastModifiedAllowed = true;
	private bool $serverPushAllowed = true;
	/**
	 * @var OutputBuffer[]
	 */
	private array $outputBuffers = [];
	/**
	 * @var HeaderJob[]
	 */
	private array $headerJobs;
	private $statusCode;
	private ?HttpCacheControl $httpCacheControl = null;
	private ?ResponseCacheControl $responseCacheControl = null;
	private ?ContentSecurityPolicy $contentSecurityPolicy = null;
	private ?ResponseCacheStore $responseCacheStore = null;
	private $sentPayload;
	private string $bodyContents = '';

	private bool $flushed = false;

	/**
	 * @param Request $request
	 */
	public function __construct(private Request $request) {
		$this->reset();
	}

	/**
	 * @return Request
	 */
	public function getRequest(): Request {
		return $this->request;
	}

	/**
	 * If true the response will use {@link https://en.wikipedia.org/wiki/HTTP_ETag etags} to determine if
	 * the response was modified since last request and send 304 Not Modified http status to reduce traffic if not.
	 * @param bool $sendEtagAllowed
	 */
	public function setSendEtagAllowed(bool $sendEtagAllowed): void {
		$this->sendEtagAllowed = $sendEtagAllowed;
	}

	/**
	 * @see self::setSendEtagAllowed()
	 * @return bool
	 */
	public function isSendEtagAllowed(): bool {
		return $this->sendEtagAllowed;
	}

	/**
	 * If last modified DateTime is provided by the sent {@see Payload} it will be used to determine if
	 * the response was modified since last request and send 304 Not Modified http status to reduce traffic if not.
	 *
	 * @see self::send()
	 * @see Payload::getLastModified()
	 *
	 * @param bool $sendLastModifiedAllowed
	 */
	public function setSendLastModifiedAllowed(bool $sendLastModifiedAllowed): void {
		$this->sendLastModifiedAllowed = $sendLastModifiedAllowed;
	}

	/**
	 * @see self::setSendLastMoidifiedAllowed()
	 * @return bool
	 */
	public function isSendLastModifiedAllowed(): bool {
		return $this->sendLastModifiedAllowed;
	}

	/**
	 * If false all server push directives mandated be {@see self::serverPush()} will be ignored. This option is
	 * usually modified through changes in the app.ini.
	 *
	 * @param bool $serverPushAllowed
	 */
	public function setServerPushAllowed(bool $serverPushAllowed): void {
		$this->serverPushAllowed = $serverPushAllowed;
	}

	/**
	 * @see self::setSendLastMoidifiedAllowed()
	 * @return bool
	 */
	public function isServerPushAllowed(): bool {
		return $this->serverPushAllowed;
	}

	/**
	 * @see self::setResponseCachingEnabled()
	 * @return bool
	 */
	public function isResponseCachingEnabled(): bool {
		return $this->responseCachingEnabled;
	}

	/**
	 * If false response cache configurations assigned over {@see self::setResponseCacheControl()} will be ignored.
	 * @param bool $responseCachingEnabled
	 */
	public function setResponseCachingEnabled(bool $responseCachingEnabled): void {
		$this->ensureNotFlushed();

		$this->responseCachingEnabled = $responseCachingEnabled;
	}

	/**
	 * @see self::setResponseCachingEnabled()
	 * @return bool
	 */
	public function isHttpCachingEnabled(): bool {
		return $this->httpCachingEnabled;
	}

	/**
	 * If false http cache configurations assigned over {@see self::setHttpCacheControl()} will be ignored.
	 * @param bool $httpCachingEnabled
	 */
	public function setHttpCachingEnabled(bool $httpCachingEnabled): void {
		$this->ensureNotFlushed();

		$this->httpCachingEnabled = $httpCachingEnabled;
	}

	public function isContentSecurityPolicyEnabled(): bool {
		return $this->contentSecurityPolicyEnabled;
	}

	/**
	 * If false response cache configurations assigned over {@see self::setResponseCacheControl()} will be ignored.
	 * @param bool $contentSecurityPolicyEnabled
	 */
	public function setContentSecurityPolicyEnabled(bool $contentSecurityPolicyEnabled): void {
		$this->ensureNotFlushed();

		$this->contentSecurityPolicyEnabled = $contentSecurityPolicyEnabled;
	}

	/**
	 * @return bool
	 */
	public function isBuffering(): bool {
		return !empty($this->outputBuffers) && $this->outputBuffers[0]->isBuffering();
	}

	/**
	 * @throws ResponseBufferIsClosed
	 */
	private function ensureBuffering(): void {
		if ($this->isBuffering()) return;

		throw new IllegalStateException('Response buffer is closed.');
	}

	function capturePrevBuffer(): void {
		$this->ensureNotFlushed();

		if (!empty($this->outputBuffers)) {
			throw new IllegalStateException('OutputBuffer already exists.');
		}

		$prevContent = ob_get_contents();
		if ($prevContent !== false) {
			@ob_clean();
		}

		$this->bodyContents .= $prevContent;
	}

	/**
	 * @return OutputBuffer
	 */
	public function createOutputBuffer(): OutputBuffer {
		$this->ensureNotFlushed();

		$this->removeEndedBuffers();
		return $this->pushNewOutputBuffer();
	}

	function removeEndedBuffers(): void {
		while (false !== ($outputBuffer = end($this->outputBuffers))) {
			if ($outputBuffer->isBuffering()) {
				return;
			}
			$outputBuffer->seal();
			array_pop($this->outputBuffers);
		}
	}

	private function pushNewOutputBuffer(): OutputBuffer {
		$outputBuffer = new OutputBuffer();
		$this->outputBuffers[] = $outputBuffer;
		return $outputBuffer;
	}

	/**
	 * @return string
	 */
	public function getBufferedOutput(): string {
		$contents = '';

		foreach ($this->outputBuffers as $outputBuffer) {
			if (!$outputBuffer->isBuffering()) continue;
			$contents .= $outputBuffer->getBufferedContents();
		}

		return $contents;
	}

	function addContent(string $content): void {
		$this->ensureNotFlushed();

		if ($this->isBuffering()) {
			echo $content;
		} else {
			$this->bodyContents .= $content;
		}
	}

	function getBufferableOutput(): string {
		return $this->bodyContents;
	}

	function cleanBufferedOutput(): void {
		foreach ($this->outputBuffers as $outputBuffer) {
			if (!$outputBuffer->isBuffering()) continue;

			$outputBuffer->clean();
		}
	}

	/**
	 *
	 * @param bool $closeBaseBuffer
	 * @return string
	 * @throws OutputBufferDisturbedException
	 */
	public function fetchBufferedOutput(bool $closeBaseBuffer = false): string {
		$contents = '';

		$outputBuffer = null;
		$num = sizeof($this->outputBuffers);
		for ($i = 1; $i <= $num; $i++) {
			if ($i < $num || $closeBaseBuffer) {
				$outputBuffer = array_pop($this->outputBuffers);
			} else {
				$outputBuffer = current($this->outputBuffers);
			}

			if ($outputBuffer->isBuffering()) {
				$contents = $outputBuffer->getBufferedContents() . $contents;
			}

			if ($i < $num || $closeBaseBuffer) {
				$outputBuffer->seal();
			}
		}

		$outputBuffer?->clean();

		return $contents;
	}

	/**
	 *
	 */
	public function reset(): void {
		$this->ensureNotFlushed();

		foreach ($this->listeners as $listener) {
			$listener->onReset($this);
		}

// 		$this->ensureBuffering();

		$this->statusCode = self::STATUS_200_OK;
		$this->headerJobs = array();
		if ($this->isBuffering()) {
			$this->fetchBufferedOutput(false);
		}
		$this->httpCacheControl = null;
		$this->responseCacheControl = null;
		$this->contentSecurityPolicy = null;
		$this->sentPayload = null;
		$this->bodyContents = '';
	}

	/**
	 *
	 * @param string|null $etag
	 * @param \DateTime|null $lastModified
	 * @return bool
	 */
	private function notModified(?string $etag, \DateTime $lastModified = null): bool {
		if ($this->statusCode !== self::STATUS_200_OK) return false;

		$etagNotModified = null;
		if ($this->sendEtagAllowed && $etag !== null) {
			$this->setHeader('Etag: "' . $etag . '"');

			if (null !== ($ifNoneMatch = $this->request->getHeader('If-None-Match'))) {
				$ifNoneMatchParts = preg_split('/\s*,\s*/', $ifNoneMatch);
				$etagNotModified = in_array('"' . $etag . '"', $ifNoneMatchParts)
						// also test weak etags
						|| in_array('W/"' . $etag . '"', $ifNoneMatchParts);
			}
		}

		$lastModifiedNotModified = null;
		if ($this->sendLastModifiedAllowed && $lastModified !== null) {
			$lastModified->setTimezone(new \DateTimeZone('GMT'));
			// RFC1123 with GMT
			$this->setHeader('Last-Modified: ' . $lastModified->format('D, d M Y H:i:s') . ' GMT');

			$ifModifiedSinceStr = $this->request->getHeader('If-Modified-Since');
			if (null !== $ifModifiedSinceStr
					&& $ifModifiedSince = \DateTime::createFromFormat(
							DateTimeInterface::RFC1123, $ifModifiedSinceStr)) {
				$lastModifiedNotModified = $ifModifiedSince >= $lastModified;
			}
		}

		if (($etagNotModified !== null || $lastModifiedNotModified !== null)
				&& $etagNotModified !== false && $lastModifiedNotModified !== false) {
			$this->setStatus(self::STATUS_304_NOT_MODIFIED);
			return true;
		}

		return false;
	}

	/**
	 *
	 * @return boolean
	 */
	public function sendCachedPayload(): bool {
		$this->ensureNotFlushed();

		if ($this->responseCacheStore === null || !$this->responseCachingEnabled) {
			return false;
		}

		$responseCacheItem = $this->responseCacheStore->get(
				ResponseCacheId::createFromRequest($this->request, false), false);

		if ($responseCacheItem === null) {
			$responseCacheItem = $this->responseCacheStore->get(
					ResponseCacheId::createFromRequest($this->request, true), false);
		}

		if ($responseCacheItem === null) {
			$responseCacheItem = $this->responseCacheStore->get(
					ResponseCacheId::createFromRequest($this->request, false), true);
		}

		if ($responseCacheItem === null) {
			$responseCacheItem = $this->responseCacheStore->get(
					ResponseCacheId::createFromRequest($this->request, true), true);
		}

		if ($responseCacheItem === null) {
			return false;
		}

		$this->send($responseCacheItem->getCachedPayload());
		return true;
	}

	private function cachePayload(): void {
		if (!$this->responseCachingEnabled || $this->responseCacheControl === null || $this->responseCacheStore === null) {
			return;
		}

		$expireDate = new \DateTime();
		$expireDate->add($this->responseCacheControl->getCacheInterval());
		$this->responseCacheStore->store(
				new ResponseCacheId($this->request->getMethod(),
						$this->request->getHostName(), $this->request->getPath(),
						$this->buildQueryParamsCharacteristic()),
				new ResponseCacheItem(
						new CachedPayload($this->bodyContents, $this->statusCode,
								$this->headerJobs, $this->httpCacheControl, $expireDate),
						$this->responseCacheControl->getCharacteristics(),
						$this->responseCacheControl->getVerifierCheckLookupId()),
				$this->responseCacheControl->isShared());
	}

	/**
	 * @return array|null
	 */
	public function buildQueryParamsCharacteristic(): ?array {
		$paramNames = $this->responseCacheControl->getIncludedQueryParamNames();
		if (null === $paramNames) return null;

		$queryParams = $this->request->getQuery()->toArray();
		$characteristic = array();
		foreach ($paramNames as $paramName) {
			if (!array_key_exists($paramName, $queryParams)) continue;
			$characteristic[$paramName] = $queryParams[$paramName];
		}
		return $characteristic;
	}

	function isFlushed(): bool {
		return $this->flushed;
	}

	private function ensureNotFlushed(): void {
		if (!$this->isFlushed()) return;

		throw new IllegalStateException('Response is already flushed.');
	}

	private function applyHeaders(): void {
		if ($this->contentSecurityPolicy !== null && $this->contentSecurityPolicyEnabled) {
			$this->contentSecurityPolicy->applyHeaders($this);
		}
	}

	/**
	 *
	 * @throws HttpHeadersAlreadySentException
	 */
	public function flush(FlushMode $flushMode = FlushMode::OUT): void {
		$this->ensureNotFlushed();

		foreach ($this->listeners as $listener) {
			$listener->onFlush($this);
		}

		$this->applyHeaders();
		$contents = $this->fetchBufferedOutput(true);

		if ($this->sentPayload !== null && !$this->sentPayload->isBufferable()) {
			if ($this->responseCacheControl !== null) {
				throw new MalformedResponseException('ResponseCacheControl only works with bufferable response objects.');
			}

			$this->flushed = true;

			if (!strlen($contents) && $this->notModified($this->sentPayload->getEtag(), $this->sentPayload->getLastModified())) {
				if ($flushMode->isEchoEnabled()) {
					$this->flushHeaders();
				}
				$this->closeBuffer();
				return;
			}

			if ($flushMode->isEchoEnabled()) {
				$this->flushHeaders();
				echo $this->bodyContents;
				$this->sentPayload->responseOut();
				echo $contents;
			}
			$this->bodyContents .= $contents;
			return;
		}

		$this->bodyContents .= $contents;

		$this->cachePayload();

		$this->flushed = true;

		if ($this->notModified(HashUtils::base36md5Hash($this->bodyContents, 26))) {
			if ($flushMode->isEchoEnabled()) {
				$this->flushHeaders();
			}
			return;
		}


		if ($flushMode->isEchoEnabled()) {
			$this->flushHeaders();
			echo $this->bodyContents;
		}
	}
	/**
	 *
	 */
	public function closeBuffer(): void {
		while (null != ($outputBuffer = array_pop($this->outputBuffers))) {
			$outputBuffer->seal();
		}
	}
	/**
	 *
	 * @param int $code
	 */
	public function setStatus(int $code): void {
		if ($this->statusCode != $code) {
			foreach ($this->listeners as $listener) {
				$listener->onReset($this);
			}
		}

		$this->statusCode = $code;
	}

	public function getStatus(): int {
		return $this->statusCode;
	}
	/**
	 *
	 * @param string $header
	 * @param bool $replace
	 */
	public function setHeader(string $header, bool $replace = true): void {
//		if ($header instanceof Header) {
//			$this->headers[] = $header;
//			return;
//		}

		$this->headerJobs[] = new ApplyHeaderJob($header, $replace);
	}

	function removeHeader(string $name): void {
		$this->headerJobs[] = new RemoveHeaderJob($name);
	}

	function addHeaderJob(HeaderJob $headerJob): void {
		$this->headerJobs[] = $headerJob;
	}

	function getHeaderJobs(): array {
		return $this->headerJobs;
	}

	/**
	 * @param string $name
	 * @return string[];
	 */
	function getHeaderValues(string $name): array {
		$name = mb_strtolower(trim($name));
		$values = [];

		foreach ($this->headerJobs as $headerJob) {
			if (mb_strtolower($headerJob->getName()) !== $name) {
				continue;
			}

			if ($headerJob->isRemove()) {
				$values = [];
				continue;
			}

			$values[] = $headerJob->getValue();
		}

		return $values;
	}

	public function setHttpCacheControl(?HttpCacheControl $httpCacheControl): void {
		$this->ensureNotFlushed();
		$this->httpCacheControl = $httpCacheControl;
	}

	public function getHttpCacheControl(): ?HttpCacheControl {
		return $this->httpCacheControl;
	}

	public function setResponseCacheControl(?ResponseCacheControl $responseCacheControl): void {
		$this->ensureNotFlushed();
		$this->responseCacheControl = $responseCacheControl;
	}

	public function getResponseCacheControl(): ?ResponseCacheControl {
		return $this->responseCacheControl;
	}


	function setContentSecurityPolicy(?ContentSecurityPolicy $contentSecurityPolicy): void {
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}

	function getContentSecurityPolicy(): ?ContentSecurityPolicy {
		return $this->contentSecurityPolicy;
	}

	/**
	 * Server push will be ignored if HTTP version of request is lower than 2 or if sever push is disabled in app.ini
	 *
	 * @param ServerPushDirective $directive
	 */
	public function serverPush(ServerPushDirective $directive): void {
		if ($this->request->getProtocolVersion()->getMajorNum() < 2) {
			return;
		}

		$this->setHeader($directive->toHeader());
	}

	public function getResponseCacheStore() {
		return $this->responseCacheStore;
	}

	public function setResponseCacheStore(ResponseCacheStore $responseCacheStore = null): void {
		$this->responseCacheStore = $responseCacheStore;
	}
	/**
	 *
	 * @throws HttpHeadersAlreadySentException
	 */
	private function flushHeaders(): void {
		$file = null;
		$line = null;
		if (headers_sent($file, $line)) {
			throw new FancyError('Response sent outside of n2n context', $file, $line);
		}

//		header('X-Powered-By: N2N/' . N2N::VERSION, false, $this->statusCode);
		http_response_code($this->statusCode);

		if ($this->httpCacheControl !== null && $this->httpCachingEnabled) {
			$this->httpCacheControl->applyHeaders($this);
		}

		while (!is_null($header = array_shift($this->headerJobs))) {
			$header->flush();
		}
	}

	/**
	 *
	 * @param Payload|ResponseInterface $payload
	 * @param bool $includeBuffer
	 */
	public function send(Payload|ResponseInterface $payload, bool $includeBuffer = true): void {
		if ($payload instanceof ResponseInterface) {
			$payload = new PsrResponsePayload($payload);
		}

		foreach ($this->listeners as $listener) {
			$listener->onSend($payload, $this);
		}

		if (null !== $this->sentPayload) {
			throw new MalformedResponseException('Payload already sent: '
					. $this->sentPayload->toKownPayloadString(), 0, null, 1);
		}
		$this->sentPayload = $payload;

		$payload->prepareForResponse($this);

		if ($payload->isBufferable()) {
			if (!$this->isBuffering()) {
				$this->bodyContents .= $payload->getBufferedContents();
			} else {
				if (!$includeBuffer) {
					$this->cleanBufferedOutput();
				}

				echo $payload->getBufferedContents();
			}
		}
	}

	public function hasSentPayload(): bool {
		return $this->sentPayload !== null;
	}

	public function getSentPayload(): ?Payload {
		return $this->sentPayload;
	}

	public function registerListener(ResponseListener $listener): void {
		$this->listeners[spl_object_hash($listener)] = $listener;
	}

	public function unregisterListener(ResponseListener $listener): void {
		unset($this->listeners[spl_object_hash($listener)]);
	}
}
