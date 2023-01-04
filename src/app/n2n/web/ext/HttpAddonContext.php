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
use n2n\core\container\impl\AppN2nContext;
use n2n\web\http\ResponseCacheStore;

class HttpAddonContext extends SimpleMagicContext implements N2nHttp, AddOnContext {

	function __construct(private readonly ?HttpContext $httpContext,
			private readonly ?ControllerRegistry $controllerRegistry,
			private readonly ResponseCacheStore $responseCacheStore) {
		parent::__construct([
			HttpContext::class => $httpContext,
			Request::class => $httpContext->getRequest(),
			Response::class => $httpContext->getResponse(),
			Session::class => $httpContext->getSession(),
			ResponseCacheStore::class => $this->responseCacheStore,
			ControllerRegistry::class => $controllerRegistry
		]);
	}

	public function invokerControllers(): void {
		$request = $this->httpContext->getRequest();
		$response = $this->httpContext->getResponse();

		if ($request->getOrigMethodName() !== Method::toString(Method::HEAD)
				&& ($request->getOrigMethodName() != Method::toString($request->getMethod()))) {
			throw new MethodNotAllowedException(Method::HEAD|Method::GET|Method::POST|Method::PUT|Method::OPTIONS|Method::PATCH|Method::DELETE|Method::TRACE);
		}

		$controllingPlan = $this->controllerRegistry->createControllingPlan($this->httpContext, $request->getCmdPath(),
				$this->httpContext->getActiveSubsystemRule());
		$result = $controllingPlan->execute();
		if (!$result->isSuccessful()) {
			$controllingPlan->sendStatusView($result->getStatusException());
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
		return $this->httpContext->getSession();
	}

	function copyTo(AppN2nContext $appN2NContext): void {
		if ($this->httpContext !== null) {
			$appN2NContext->setHttp($this);
		}
		$appN2NContext->addAddonContext($this);
	}

	function finalize(): void {
	}
}