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

/**
 * Assembles the http response and gives you different tools to modify it according to your wishes.
 * n2n creates an object of this class in its initialization phase lets you access it over the {@see HttpContext}.
 */
abstract class Response {
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


	abstract function getRequest(): Request;

	abstract function isBuffering(): bool;

	abstract function createOutputBuffer(): OutputBuffer;

	abstract function addContent(string $content): void;

	abstract function cleanBufferedOutput(): void;

	abstract function fetchBufferedOutput(bool $closeBaseBuffer = false): string;

	abstract function reset(): void;

	abstract function sendCachedPayload(): bool;

	abstract function isFlushed(): bool;

	abstract function flush(): void;

	abstract function closeBuffer(): void;

	abstract function setStatus(int $code): void;

	abstract function getStatus(): int;

	abstract function setHeader(string $header, bool $replace = true): void;

	abstract function removeHeader(string $name): void;

	abstract function addHeaderJob(HeaderJob $headerJob): void;

	/**
	 * @return HeaderJob[]
	 */
	abstract function getHeaderJobs(): array;

	/**
	 * @param string $name
	 * @return string[];
	 */
	abstract function getHeaderValues(string $name): array;

	abstract function setHttpCacheControl(?HttpCacheControl $httpCacheControl): void;

	abstract function getHttpCacheControl(): ?HttpCacheControl;

	abstract function setResponseCacheControl(?ResponseCacheControl $responseCacheControl): void;

	abstract function getResponseCacheControl(): ?ResponseCacheControl;


	abstract function setContentSecurityPolicy(?ContentSecurityPolicy $contentSecurityPolicy): void;

	abstract function getContentSecurityPolicy(): ?ContentSecurityPolicy;

	/**
	 * Server push will be ignored if HTTP version of request is lower than 2 or if sever push is disabled in app.ini
	 *
	 * @param ServerPushDirective $directive
	 */
	abstract function serverPush(ServerPushDirective $directive): void;

	/**
	 *
	 * @param Payload|ResponseInterface $payload
	 * @param bool $includeBuffer
	 * @throws \n2n\web\http\err\PayloadAlreadySentException
	 */
	abstract function send(Payload|ResponseInterface $payload, bool $includeBuffer = true): void;

	abstract function hasSentPayload(): bool;

	abstract function getSentPayload(): ?Payload;

	abstract function registerListener(ResponseListener $listener): void;

	abstract function unregisterListener(ResponseListener $listener): void;

	/**
	 *
	 * @param int $code
	 * @param bool $required
	 * @return string|null
	 */
	public static function textOfStatusCode(int $code, bool $required = false): string|null {
		switch ($code) {
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
