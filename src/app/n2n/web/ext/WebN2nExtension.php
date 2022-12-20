<?php

namespace n2n\web;

use n2n\core\ext\N2nExtension;
use n2n\core\container\impl\AppN2nContext;
use n2n\core\HttpContextFactory;
use n2n\web\http\VarsRequest;
use n2n\web\http\VarsSession;
use n2n\web\http\controller\ControllerRegistry;
use n2n\util\magic\impl\SimpleMagicContext;
use n2n\web\http\HttpContext;
use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\web\http\Session;

class WebN2nExtension implements N2nExtension {

	public function __construct() {
	}

	function setUp(AppN2nContext $appN2nContext): void {
		$phpVars = $appN2nContext->getPhpVars();
		$request = new VarsRequest($phpVars->server, $phpVars->get, $phpVars->post, $phpVars->files);
		$request->legacyN2nContext = $appN2nContext;
		$appConfig = $appN2nContext->getAppConfig();
		$lookupSession = $session = new VarsSession($appConfig->general()->getApplicationName());

		$httpContext = HttpContextFactory::createFromAppConfig($appConfig, $request, $session, $appN2nContext,
				self::$exceptionHandler);

		$controllerRegistry = new ControllerRegistry($appConfig->web(), $httpContext);

		$appN2nContext->setN2nHttpEngine(new ControllerInvoker($httpContext, $controllerRegistry));
		$appN2nContext->addMagicContext(new SimpleMagicContext([
			HttpContext::class => $httpContext,
			Request::class => $request,
			Response::class => $httpContext->getResponse(),
			Session::class => $httpContext->getSession()
		]));

	}

	function copyTo(AppN2nContext $appN2nContext): void {
		// TODO: Implement copyTo() method.
	}
}