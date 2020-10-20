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
use n2n\io\ob\OutputBuffer;
use n2n\core\N2N;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\payload\Payload;

/**
 * Assembles the http response and gives you diffrent tools to modify it according to your wishes. 
 * n2n creates an object of this class in its initialization phase lets you access it over the {@see HttpContext}.
 */
class Response {
	const STATUS_100_CONTINUE = 100;
	const STATUS_101_SWITCHING_PROTOCOLS = 101;
	const STATUS_102_PROCESSING = 102;
	const STATUS_200_OK = 200;
	const STATUS_201_CREATED = 201;
	const STATUS_202_ACCEPTED = 202;
	const STATUS_203_NON_AUTHORITATIVE_INFORMATION = 203;
	const STATUS_204_NO_CONTENT = 204;
	const STATUS_205_RESET_CONTENT = 205;
	const STATUS_206_PARTIAL_CONTENT = 206;
	const STATUS_207_MULTI_STATUS = 207; 
	const STATUS_208_ALREADY_REPORTED = 208; 
	const STATUS_226_IM_USED = 226; 
	const STATUS_300_MULTIPLE_CHOICES = 300;
	const STATUS_301_MOVED_PERMANENTLY = 301;
	const STATUS_302_FOUND = 302;
	const STATUS_303_SEE_OTHER = 303;
	const STATUS_304_NOT_MODIFIED = 304;
	const STATUS_305_USE_PROXY = 305;
	const STATUS_307_TEMPORARY_REDIRECT = 307;
	const STATUS_308_PERMANENT_REDIRECT = 308;
	const STATUS_400_BAD_REQUEST = 400;
	const STATUS_401_UNAUTHORIZED = 401;
	const STATUS_402_PAYMENT_REQUIRED = 402;
	const STATUS_403_FORBIDDEN = 403;
	const STATUS_404_NOT_FOUND = 404;
	const STATUS_405_METHOD_NOT_ALLOWED = 405;
	const STATUS_406_NOT_ACCEPTABLE = 406;
	const STATUS_407_PROXY_AUTHENTICATION_REQUIRED = 407;
	const STATUS_408_REQUEST_TIMEOUT = 408;
	const STATUS_409_CONFLICT = 409;
	const STATUS_410_GONE = 410;
	const STATUS_411_LENGTH_REQUIRED = 411;
	const STATUS_412_PRECONDITION_FAILED = 412;
	const STATUS_413_REQUEST_ENTITY_TOO_LARGE = 413;
	const STATUS_414_REQUEST_URI_TOO_LONG = 414;
	const STATUS_415_UNSUPPORTED_MEDIA_TYPE = 415;
	const STATUS_416_REQUEST_RANGE_NOT_SATISFIABLE = 416;
	const STATUS_417_EXPECTATION_FAILED = 417;
	const STATUS_418_IM_A_TEAPOT = 418;
	const STATUS_420_POLICY_NOT_FULFILLED = 420;
	const STATUS_421_MISDIRECTED_REQUEST = 421;
	const STATUS_422_UNPROCESSABLE_ENTITY = 422;
	const STATUS_423_LOCKED = 423;
	const STATUS_424_FAILED_DEPENDENCY = 424;
	const STATUS_425_UNORDERED_COLLECTION = 425;
	const STATUS_426_UPGRADE_REQUIRED = 426;
	const STATUS_428_PRECONDITION_REQUIRED = 428;
	const STATUS_429_TOO_MANY_REQUESTS = 429;
	const STATUS_431_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const STATUS_451_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
	const STATUS_500_INTERNAL_SERVER_ERROR = 500;
	const STATUS_501_NOT_IMPLEMENTED = 501;
	const STATUS_502_BAD_GATEWAY = 502;
	const STATUS_503_SERVICE_UNAVAILABLE = 503;
	const STATUS_504_GATEWAY_TIME_OUT = 504;
	const STATUS_505_HTTP_VERSION_NOT_SUPPORTED = 505;
	const STATUS_506_VARIANT_ALSO_NEGOTIATES = 506;
	const STATUS_507_INSUFFICIENT_STORAGE = 507;
	const STATUS_508_LOOP_DETECTED = 508;
	const STATUS_509_BANDWIDTH_LIMIT_EXCEEDED = 509;
	const STATUS_510_NOT_EXTENDED = 510;
	const STATUS_511_NETWORK_AUTHENTICATION_REQUIRED = 511;
	
	private $listeners = array();
	private $request;
	private $responseCachingEnabled = true;
	private $httpCachingEnabled = true;
	private $sendEtagAllowed = true;
	private $sendLastModifiedAllowed = true;
	private $serverPushAllowed = true;
	
	private $outputBuffers;
	private $bufferedHeaders;
	private $bufferedStatusCode;
	private $bufferedHttpCacheControl;
	private $bufferedResponseCacheControl;
	private $responseCacheStore;
	private $sentPayload;
	
	/**
	 * @param Request $request
	 */
	public function __construct(Request $request) {		
		$this->request = $request;
		$this->listeners = array();

		$prevContent = ob_get_contents();
		if ($prevContent !== false) {
			@ob_clean();
		}
		
		$outputBuffer = $this->createOutputBuffer();
		$outputBuffer->start();
		
		$this->reset();
		$outputBuffer->append($prevContent);
	}

	/**
	 * @return Request
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * If true the response will use {@link https://en.wikipedia.org/wiki/HTTP_ETag etags} to determine if 
	 * the response was modified since last request and send 304 Not Modified http status to reduce traffic if not.
	 * @param bool $sendEtagAllowed
	 */
	public function setSendEtagAllowed(bool $sendEtagAllowed) {
		$this->sendEtagAllowed = $sendEtagAllowed;
	}
	
	/**
	 * @see self::setSendEtagAllowed()
	 * @return bool
	 */
	public function isSendEtagAllowed() {
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
	public function setSendLastModifiedAllowed(bool $sendLastModifiedAllowed) {
		$this->sendLastModifiedAllowed = $sendLastModifiedAllowed;
	}
	
	/**
	 * @see self::setSendLastMoidifiedAllowed()
	 * @return bool
	 */
	public function isSendLastModifiedAllowed() {
		return $this->sendLastModifiedAllowed;
	}
	
	/**
	 * If false all server push directives mandated be {@see self::serverPush()} will be ignored. This option is 
	 * usually modified through changes in the app.ini.
	 *
	 * @param bool $sendLastModifiedAllowed
	 */
	public function setServerPushAllowed(bool $serverPushAllowed) {
		$this->serverPushAllowed = $serverPushAllowed;
	}
	
	/**
	 * @see self::setSendLastMoidifiedAllowed()
	 * @return bool
	 */
	public function isServerPushAllowed() {
		return $this->serverPushAllowed;
	}
	
	/**
	 * @see self::setResponseCachingEnabled()
	 * @return bool
	 */
	public function isResponseCachingEnabled() {
		return $this->responseCachingEnabled;
	}
	
	/**
	 * If false response cache configurations assigned over {@see self::setHttpCacheControl()} will be ignored.
	 * @param bool $httpCachingEnabled
	 */
	public function setResponseCachingEnabled(bool $responseCachingEnabled) {
		$this->responseCachingEnabled = $responseCachingEnabled;
	}
	
	/**
	 * @see self::setResponseCachingEnabled()
	 * @return bool
	 */
	public function isHttpCachingEnabled() {
		return $this->httpCachingEnabled;
	}
	
	/**
	 * If false http cache configurations assigned over {@see self::setHttpCacheControl()} will be ignored.
	 * @param bool $httpCachingEnabled
	 */
	public function setHttpCachingEnabled(bool $httpCachingEnabled) {
		$this->httpCachingEnabled = $httpCachingEnabled;
	}
	
	/**
	 * @return bool
	 */
	public function isBuffering() {
		return (bool) sizeof($this->outputBuffers);
	}
	
	/**
	 * @throws ResponseBufferIsClosed
	 */
	private function ensureBuffering() {
		if ($this->isBuffering()) return;
		
		throw new IllegalStateException('Response buffer is closed');
	}
	
	/**
	 * @return OutputBuffer
	 */
	public function createOutputBuffer() {
		if ($this->outputBuffers === null) {
			$this->outputBuffers = array();
			$outputBuffer = new OutputBuffer();
		} else {
			$this->ensureBuffering();
			$outputBuffer = new OutputBuffer();
		}
		
		$this->outputBuffers[] = $outputBuffer; 
		return $outputBuffer;
	}
	
	/**
	 * @return string
	 */
	public function getBufferedOutput() {
		$contents = '';
		
		foreach ($this->outputBuffers as $outputBuffer) {
			if (!$outputBuffer->isBuffering()) continue;
			$contents .= $outputBuffer->getBufferedContents();
		}
		
		return $contents;
	}
	/**
	 * 
	 * @param bool $closeBaseBuffer
	 * @return string
	 */
	public function fetchBufferedOutput($closeBaseBuffer = false) {
		$this->ensureBuffering();
		
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
				
		$outputBuffer->clean();

		return $contents;
	}
	
	/**
	 * 
	 */
	public function reset() {
		foreach ($this->listeners as $listener) {
			$listener->onReset($this);
		}
		
		$this->ensureBuffering();
		
		$this->bufferedStatusCode = self::STATUS_200_OK;
		$this->bufferedHeaders = array();
		$this->fetchBufferedOutput(false);
		$this->bufferedHttpCacheControl = null;
		$this->bufferedResponseCacheControl = null;
		$this->sentPayload = null;
	}
	
	/**
	 * 
	 * @param string $etag
	 * @param \DateTime $lastModified
	 */
	private function notModified($etag, \DateTime $lastModified = null) {
		if ($this->bufferedStatusCode !== self::STATUS_200_OK) return false;
		
		$etagNotModified = null;
		if ($this->sendEtagAllowed && $etag !== null) {
			$this->setHeader('Etag: "' . $etag . '"');
		
			if (null !== ($ifNoneMatch = $this->request->getHeader('If-None-Match'))) {
				$etagNotModified = '"' . $etag . '"' ==  $ifNoneMatch;
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
							\DateTime::RFC1123, $ifModifiedSinceStr)) {
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
	public function sendCachedPayload() {
		if ($this->responseCacheStore === null || !$this->responseCachingEnabled) {
			return false;
		}
		
		$responseCacheItem = $this->responseCacheStore->get($this->request->getMethod(), 
					$this->request->getSubsystemName(), $this->request->getPath());
		
		if ($responseCacheItem === null) {
			$responseCacheItem = $this->responseCacheStore->get($this->request->getMethod(), 
					$this->request->getSubsystemName(), $this->request->getPath(),
					$this->request->getQuery()->toArray());
		}
		
		if ($responseCacheItem === null) return false;
	
		$this->send($responseCacheItem);
		return true;
	}
	
	/**
	 * @return array|null
	 */
	public function buildQueryParamsCharacteristic() {
		$paramNames = $this->bufferedResponseCacheControl->getIncludedQueryParamNames();
		if (null === $paramNames) return null;
		
		$queryParams = $this->request->getQuery()->toArray();
		$characteristic = array();
		foreach ($paramNames as $paramName) {
			if (!array_key_exists($paramName, $queryParams)) continue;
			$characteristic[$paramName] = $queryParams[$paramName];
		}
		return $characteristic;
	}
	
	/**
	 * 
	 */
	public function flush() {
		foreach ($this->listeners as $listener) {
			$listener->onFlush($this);
		}
		
		if (!$this->isBuffering()) return;
		
		$contents = $this->fetchBufferedOutput(false);
		
		if ($this->bufferedResponseCacheControl !== null && $this->responseCacheStore !== null) {
			$expireDate = new \DateTime();
			$expireDate->add($this->bufferedResponseCacheControl->getCacheInterval());
			$this->responseCacheStore->store($this->request->getMethod(), 
					$this->request->getSubsystemName(), $this->request->getPath(),
					$this->buildQueryParamsCharacteristic(),
					$this->bufferedResponseCacheControl->getCharacteristics(),
					new ResponseCacheItem($contents, $this->bufferedStatusCode, 
							$this->bufferedHeaders, $this->bufferedHttpCacheControl, $expireDate));
		}
		
		if ($this->notModified(HashUtils::base36md5Hash($contents, 26))) {
			$this->flushHeaders();
			$this->closeBuffer();
			return;
		}
		
		$this->flushHeaders();
		$this->closeBuffer();
		echo $contents;
	}
	/**
	 * 
	 */
	public function closeBuffer() {
		while (null != ($outputBuffer = array_pop($this->outputBuffers))) {
			$outputBuffer->seal();
		}
	}
	/**
	 * 
	 * @param int $code
	 */
	public function setStatus($code) {
		if ($this->bufferedStatusCode != $code) {
			foreach ($this->listeners as $listener) {
				$listener->onReset($this);
			}
		}
		
		$this->ensureBuffering();
		
		$this->bufferedStatusCode = $code;
	}
	
	public function getStatus() {
		return $this->bufferedStatusCode;
	}
	/**
	 * 
	 * @param string $header
	 * @param string $replace
	 */
	public function setHeader($header, $replace = true) {
		$this->ensureBuffering();
	
		if ($header instanceof Header) {
			$this->bufferedHeaders[] = $header;
			return;
		}
		
		$this->bufferedHeaders[] = new Header($header, $replace);
	}
	
	public function setHttpCacheControl(HttpCacheControl $httpCacheControl = null) {
		$this->bufferedHttpCacheControl = $httpCacheControl;
	}
	
	public function setResponseCacheControl(ResponseCacheControl $responseCacheControl = null) {
		$this->bufferedResponseCacheControl = $responseCacheControl;
	}
	
	/**
	 * Server push will be ignored if HTTP version of request is lower than 2 or if sever push is disabled in app.ini
	 * 
	 * @param ServerPushDirective $directive
	 */
	public function serverPush(ServerPushDirective $directive) {
		if ($this->request->getProtocolVersion()->getMajorNum() < 2) {
			return;
		}
		
		$this->setHeader($directive->toHeader());
	}
	
	public function getResponseCacheStore() {
		return $this->responseCacheStore;
	}
	
	public function setResponseCacheStore(ResponseCacheStore $responseCacheStore = null) {
		$this->responseCacheStore = $responseCacheStore;
	}
	/**
	 * 
	 * @throws HttpHeadersAlreadySentException
	 */	
	private function flushHeaders() {
		$file = null; 
		$line = null;
		if (headers_sent($file, $line)) {
			throw new \ErrorException('Response sent outside of n2n context', 
					0, E_USER_ERROR, $file, $line);
		}
		
		header('X-Powered-By: N2N/' . N2N::VERSION, false, $this->bufferedStatusCode);
		
		if ($this->bufferedHttpCacheControl !== null && $this->httpCachingEnabled) {
			$this->bufferedHttpCacheControl->applyHeaders($this);
		} else {
			$httpCacheControl = new HttpCacheControl();
			$httpCacheControl->applyHeaders($this);
		}
		
		while (!is_null($header = array_pop($this->bufferedHeaders))) {
			header($header->getHeaderStr(), $header->isReplace());
		}
	}
	/**
	 * 
	 * @param Payload $thing
	 * @param HttpCacheControl $httpCacheControl
	 * @param bool $includeBuffer
	 * @throws PayloadAlreadySentException
	 */
	public function send(Payload $thing, bool $includeBuffer = true) {
		foreach ($this->listeners as $listener) {
			$listener->onSend($thing, $this);
		}
		
		$this->ensureBuffering();
		if (null !== $this->sentPayload) {
			throw new MalformedResponseException('Payload already sent: ' 
					. $this->sentPayload->toKownPayloadString(), 0, null, 1);
		}
		$this->sentPayload = $thing;

		$thing->prepareForResponse($this);
		$bufferdContents = '';
		if ($includeBuffer) { 
			$bufferdContents = $this->fetchBufferedOutput(false);
		}
		
		if ($thing->isBufferable()) {
			echo $bufferdContents;
			echo $thing->getBufferedContents();
		} else {
			if ($this->bufferedResponseCacheControl !== null) {
				throw new MalformedResponseException('ResponseCacheControl only works with bufferable response objects.');
			}
			
			if (!strlen($bufferdContents) && $this->notModified($thing->getEtag(), $thing->getLastModified())) {
				$this->flushHeaders();
				$this->closeBuffer();
				return;
			} 
			$this->flushHeaders();
			$this->closeBuffer();
			echo $bufferdContents;
			$thing->responseOut();
		}
	}
	
	public function hasSentPayload() {
		return $this->sentPayload !== null;
	}
	
	public function getSentPayload() {
		return $this->sentPayload;
	}
	
	public function registerListener(ResponseListener $listener) {
		$this->listeners[spl_object_hash($listener)] = $listener;
	}
	
	public function unregisterListener(ResponseListener $listener) {
		unset($this->listeners[spl_object_hash($listener)]);
	}
	
	/**
	 * 
	 * @param int $code
	 * @throws \InvalidArgumentException
	 * @return int
	 */
	public static function textOfStatusCode($code, bool $required = false) {
		switch ((int) $code) {
			case self::STATUS_100_CONTINUE: return 'Continue'; 
			case self::STATUS_101_SWITCHING_PROTOCOLS: return 'Switching Protocols'; 
			case self::STATUS_102_PROCESSING: return 'Processing';
			case self::STATUS_200_OK: return 'OK'; 
			case self::STATUS_201_CREATED: return 'Created'; 
			case self::STATUS_202_ACCEPTED: return 'Accepted'; 
			case self::STATUS_203_NON_AUTHORITATIVE_INFORMATION: return 'Non-Authoritative Information'; 
			case self::STATUS_204_NO_CONTENT: return 'No Content'; 
			case self::STATUS_205_RESET_CONTENT: return 'Reset Content'; 
			case self::STATUS_206_PARTIAL_CONTENT: return 'Partial Content'; 
			case self::STATUS_207_MULTI_STATUS: return 'Multi-Status'; 
			case self::STATUS_208_ALREADY_REPORTED: return 'Already Reported'; 
			case self::STATUS_226_IM_USED: return 'IM Used'; 
			case self::STATUS_300_MULTIPLE_CHOICES: return 'Multiple Choices'; 
			case self::STATUS_301_MOVED_PERMANENTLY: return 'Moved Permanently'; 
			case self::STATUS_302_FOUND: return 'Found'; 
			case self::STATUS_303_SEE_OTHER: return 'See Other'; 
			case self::STATUS_304_NOT_MODIFIED: return 'Not Modified'; 
			case self::STATUS_305_USE_PROXY: return 'Use Proxy';  
			case self::STATUS_307_TEMPORARY_REDIRECT: return 'Temporary Redirect'; 
			case self::STATUS_308_PERMANENT_REDIRECT: return 'Permanent Redirect'; 
			case self::STATUS_400_BAD_REQUEST: return 'Bad Request'; 
			case self::STATUS_401_UNAUTHORIZED: return 'Unauthorized'; 
			case self::STATUS_402_PAYMENT_REQUIRED: return 'Payment Required'; 
			case self::STATUS_403_FORBIDDEN: return 'Forbidden'; 
			case self::STATUS_404_NOT_FOUND: return 'Not Found'; 
			case self::STATUS_405_METHOD_NOT_ALLOWED: return 'Method Not Allowed'; 
			case self::STATUS_406_NOT_ACCEPTABLE: return 'Not Acceptable'; 
			case self::STATUS_407_PROXY_AUTHENTICATION_REQUIRED: return 'Proxy Authentication Required'; 
			case self::STATUS_408_REQUEST_TIMEOUT: return 'Request Timeout'; 
			case self::STATUS_409_CONFLICT: return 'Conflict'; 
			case self::STATUS_410_GONE: return 'Gone'; 
			case self::STATUS_411_LENGTH_REQUIRED: return 'Length Required'; 
			case self::STATUS_412_PRECONDITION_FAILED: return 'Precondition Failed'; 
			case self::STATUS_413_REQUEST_ENTITY_TOO_LARGE: return 'Request Entity Too Large'; 
			case self::STATUS_414_REQUEST_URI_TOO_LONG: return 'Request-URI Too Large'; 
			case self::STATUS_415_UNSUPPORTED_MEDIA_TYPE: return 'Unsupported Media Type'; 
			case self::STATUS_416_REQUEST_RANGE_NOT_SATISFIABLE: return 'Requested Range Not Satisfiable';
			case self::STATUS_417_EXPECTATION_FAILED: return 'Expectation Failed';
			case self::STATUS_418_IM_A_TEAPOT: return 'I’m a teapot';
			case self::STATUS_420_POLICY_NOT_FULFILLED: return 'Policy Not Fulfilled';
			case self::STATUS_421_MISDIRECTED_REQUEST: return 'Misdirected Request';
			case self::STATUS_422_UNPROCESSABLE_ENTITY: return 'Unprocessable Entity';
			case self::STATUS_423_LOCKED: return 'Locked';
			case self::STATUS_424_FAILED_DEPENDENCY: return 'Failed Dependency';
			case self::STATUS_425_UNORDERED_COLLECTION: return 'Unordered Collection';
			case self::STATUS_426_UPGRADE_REQUIRED: return 'Upgrade Required';
			case self::STATUS_428_PRECONDITION_REQUIRED: return 'Precondition Required';
			case self::STATUS_429_TOO_MANY_REQUESTS: return 'Too Many Requests';
			case self::STATUS_431_REQUEST_HEADER_FIELDS_TOO_LARGE: return 'Request Header Fields Too Large';
			case self::STATUS_451_UNAVAILABLE_FOR_LEGAL_REASONS: return 'Unavailable For Legal Reasons';
			case self::STATUS_500_INTERNAL_SERVER_ERROR: return 'Internal Server Error'; 
			case self::STATUS_501_NOT_IMPLEMENTED: return 'Not Implemented'; 
			case self::STATUS_502_BAD_GATEWAY: return 'Bad Gateway'; 
			case self::STATUS_503_SERVICE_UNAVAILABLE: return 'Service Unavailable'; 
			case self::STATUS_504_GATEWAY_TIME_OUT: return 'Gateway Timeout'; 
			case self::STATUS_505_HTTP_VERSION_NOT_SUPPORTED: return 'HTTP Version not supported'; 
			case self::STATUS_506_VARIANT_ALSO_NEGOTIATES: return 'Variant Also Negotiates';  
			case self::STATUS_507_INSUFFICIENT_STORAGE: return 'Insufficient Storage';  
			case self::STATUS_508_LOOP_DETECTED: return 'Loop Detected';  
			case self::STATUS_509_BANDWIDTH_LIMIT_EXCEEDED: return 'Bandwidth Limit Exceeded';  
			case self::STATUS_510_NOT_EXTENDED: return 'Not Extended';  
			case self::STATUS_511_NETWORK_AUTHENTICATION_REQUIRED: return 'Network Authentication Required'; 
			default:
				if (!$required) return null;
				throw new \InvalidArgumentException('Unknown http status code: ' . $code);
		}
	}
}

class Header {
	private $headerStr;
	private $replace; 
	/**
	 * 
	 * @param string $headerStr
	 * @param bool $replace
	 */
	public function __construct(string $headerStr, bool $replace = true) {
		if (is_numeric(strpos($headerStr, "\r")) || is_numeric(strpos($headerStr, "\n"))) {
			throw new \InvalidArgumentException('Illegal chars in http header str: ' . $headerStr);
		}
		
		// @todo maybe throw an illegalargument exception headerStr contains illegal characters.
		$this->headerStr = str_replace(array("\r", "\n"), '', (string) $headerStr);
		$this->replace = (boolean) $replace;
	}
	/**
	 * 
	 * @return string
	 */
	public function getHeaderStr() {
		return $this->headerStr;
	}
	/**
	 * 
	 * @return bool
	 */
	public function isReplace() {
		return $this->replace;
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return $this->headerStr;
	}
}

class HttpHeadersAlreadySentException extends N2nErrorException {
	
}

class ResponseBufferIsClosed extends N2nRuntimeException {
	
}

class PayloadAlreadySentException extends N2nRuntimeException {
	
}
