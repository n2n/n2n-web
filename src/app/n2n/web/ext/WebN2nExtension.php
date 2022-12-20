<?php

namespace n2n\web;

use n2n\core\ext\N2nExtension;
use n2n\core\container\impl\AppN2nContext;
use n2n\core\HttpContextFactory;
use n2n\web\http\VarsRequest;
use n2n\web\http\VarsSession;
use n2n\web\http\controller\ControllerRegistry;

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

		new ControllerInvoker($controllerRegistry)

	}

	function copyTo(AppN2nContext $appN2nContext): void {
		// TODO: Implement copyTo() method.
	}
}