<?php

namespace n2n\web;

use n2n\core\ext\N2nHttpEngine;
use n2n\web\http\Method;
use n2n\web\http\MethodNotAllowedException;
use n2n\web\http\controller\ControllerRegistry;
use n2n\web\http\HttpContext;
use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\web\http\Session;
use n2n\context\config\LookupSession;

class ControllerInvoker implements N2nHttpEngine {

	function __construct(private readonly HttpContext $httpContext,
			private readonly ControllerRegistry $controllerRegistry) {

	}

	function canUnwrap(string $className): bool {
		return match ($className) {
			Request::class, Response::class, Session::class, HttpContext::class => true,
			default => false,
		};
	}

	function unwrap(string $className): ?object {
		return match ($className) {
			Request::class => $this->httpContext->getRequest(),
			Response::class => $this->httpContext->getResponse(),
			Session::class => $this->httpContext->getSession(),
			HttpContext::class => $this->httpContext,
			default => null,
		};
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
}