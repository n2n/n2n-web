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
namespace n2n\web\ext;

use n2n\core\ext\N2nHttp;
use n2n\web\http\Method;
use n2n\web\http\MethodNotAllowedException;
use n2n\web\http\controller\ControllerRegistry;
use n2n\web\http\HttpContext;
use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\web\http\Session;
use n2n\context\config\LookupSession;
use n2n\core\container\impl\AddOnContext;
use n2n\util\magic\impl\SimpleMagicContext;
use n2n\web\http\cache\ResponseCacheStore;
use n2n\core\N2N;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\cache\PayloadCacheStore;

class HttpAddonContext implements N2nHttp, AddOnContext {
	private ?SimpleMagicContext $simpleMagicContext;

	/**
	 * @param HttpContext|null $httpContext
	 * @param ControllerRegistry|null $controllerRegistry
	 * @param ResponseCacheStore $responseCacheStore
	 * @param bool $statusExceptionLoggingEnabled
	 * @param int[] $loggingExcludedStatusCodes
	 */
	function __construct(private readonly ?HttpContext $httpContext,
			private readonly ?ControllerRegistry $controllerRegistry,
			private readonly ResponseCacheStore $responseCacheStore,
			private readonly PayloadCacheStore $payloadCacheStore,
			private readonly bool $statusExceptionLoggingEnabled = false,
			private readonly array $loggingExcludedStatusCodes = []) {

		$this->simpleMagicContext = new SimpleMagicContext(array_filter([
			HttpContext::class => $httpContext,
			Request::class => $httpContext?->getRequest(),
			Response::class => $httpContext?->getResponse(),
			Session::class => $httpContext?->getSession(),
			ResponseCacheStore::class => $this->responseCacheStore,
			\n2n\web\http\ResponseCacheStore::class => new \n2n\web\http\ResponseCacheStore($this->responseCacheStore),
			PayloadCacheStore::class => $this->payloadCacheStore,
			ControllerRegistry::class => $controllerRegistry
		]));
	}

	function hasMagicObject(string $id): bool {
		$this->ensureNotFinalized();

		return $this->simpleMagicContext->has($id);
	}

	function lookupMagicObject(\ReflectionClass|string $id, bool $required = true, string $contextNamespace = null): mixed {
		$this->ensureNotFinalized();

		$result = $this->simpleMagicContext->lookup($id, false, $contextNamespace);
		if ($result !== null || $this->httpContext !== null || !$required) {
			return $result;
		}

		switch ($id) {
			case HttpContext::class:
			case Request::class:
			case Response::class:
			case Session::class:
				throw new MagicObjectUnavailableException('HttpContext not available.');
			default:
				return null;
		}
	}

	public function invokerControllers(bool $flush): void {
		$this->ensureNotFinalized();

		$request = $this->httpContext->getRequest();
		$response = $this->httpContext->getResponse();

		if ($request->getOrigMethodName() !== Method::toString(Method::HEAD)
				&& ($request->getOrigMethodName() != Method::toString($request->getMethod()))) {
			throw new MethodNotAllowedException(Method::HEAD|Method::GET|Method::POST|Method::PUT|Method::OPTIONS|Method::PATCH|Method::DELETE|Method::TRACE);
		}

		$controllingPlan = $this->controllerRegistry->createControllingPlan($this->httpContext, $request->getCmdPath(),
				$this->httpContext->getActiveSubsystemRule());
		$result = $controllingPlan->execute();
		if ($result->isSuccessful()) {
			if ($flush) {
				$response->flush();
			}
			return;
		}

		$statusException = $result->getStatusException();
		$controllingPlan->sendStatusView($statusException);
		if ($this->statusExceptionLoggingEnabled
				&& !in_array($statusException->getStatus(), $this->loggingExcludedStatusCodes)) {
			N2N::getExceptionHandler()->log($statusException);
		}

		if ($flush) {
			$response->flush();
		}
	}



//	public static function invokerControllers(string $subsystemName = null, Path $cmdPath = null) {
//		$n2nContext = self::_i()->n2nContext;
//		$httpContext = $n2nContext->getHttpContext();
//		$request = $httpContext->getRequest();
//
//		$subsystem = null;
//		if ($subsystemName !== null) {
//			$subsystem = $this->httpContext->getAvailableSubsystemByName($subsystemName);
//		}
//		$request->setSubsystem($subsystem);
//
//
//		$controllerRegistry = $n2nContext->lookup(ControllerRegistry::class);
//
//		if ($cmdPath === null) {
//			$cmdPath = $request->getCmdPath();
//		}
//		$controllerRegistry->createControllingPlan($request->getCmdPath(), $request->getSubsystemName())->execute();
//	}
	function getLookupSession(): LookupSession {
		$this->ensureNotFinalized();

		return $this->httpContext->getSession();
	}

//	function copyTo(AppN2nContext $appN2nContext): void {
//		if ($this->httpContext !== null) {
//			$appN2nContext->setHttp($this);
//		}
//		$appN2nContext->addAddonContext($this);
//	}


	function isFinalized(): bool {
		return $this->simpleMagicContext === null;
	}

	private function ensureNotFinalized(): void {
		if (!$this->isFinalized()) {
			return;
		}

		throw new IllegalStateException(self::class . ' already finalized.');
	}

	function finalize(): void {
		$this->ensureNotFinalized();

		$this->simpleMagicContext = null;
		$this->responseCacheStore->close();
	}
}