<?php

namespace n2n\web;

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

class HttpAddonContext extends SimpleMagicContext implements N2nHttp, AddOnContext {

	function __construct(private readonly HttpContext $httpContext,
			private readonly ControllerRegistry $controllerRegistry) {
		parent::__construct([
			HttpContext::class => $httpContext,
			Request::class => $httpContext->getRequest(),
			Response::class => $httpContext->getResponse(),
			Session::class => $httpContext->getSession()
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
		$appN2NContext->setN2nHttpEngine($this);
		$appN2NContext->addAddonContext($this);
	}
}