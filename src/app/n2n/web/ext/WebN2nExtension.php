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

use n2n\core\ext\N2nExtension;
use n2n\core\container\impl\AppN2nContext;
use n2n\web\http\VarsRequest;
use n2n\web\http\VarsSession;
use n2n\web\http\controller\ControllerRegistry;
use n2n\core\N2N;
use n2n\web\http\ResponseCacheStore;
use n2n\core\config\AppConfig;
use n2n\core\cache\AppCache;

class WebN2nExtension implements N2nExtension {

	public function __construct(private AppConfig $appConfig, private AppCache $appCache) {
	}

	function setUp(AppN2nContext $appN2nContext): void {
		$responseCacheStore = new ResponseCacheStore($appN2nContext->getAppCache(), $appN2nContext->getTransactionManager());

		if (!isset($appN2nContext->getPhpVars()->server['REQUEST_URI'])) {
			$appN2nContext->addAddonContext(new HttpAddonContext(null, null, $responseCacheStore));
			return;
		}

		$phpVars = $appN2nContext->getPhpVars();
		$request = new VarsRequest($phpVars->server, $phpVars->get, $phpVars->post, $phpVars->files);
		$request->legacyN2nContext = $appN2nContext;
		$appConfig = $appN2nContext->getAppConfig();
		$lookupSession = $session = new VarsSession($appConfig->general()->getApplicationName());

		$httpContext = HttpContextFactory::createFromAppConfig($appConfig, $request, $session, $appN2nContext,
				$responseCacheStore, N2N::getExceptionHandler());

		$appN2nContext->setN2nLocale($httpContext->determineBestN2nLocale());

		$controllerRegistry = new ControllerRegistry($appConfig->web(), $appConfig->routing(),  $httpContext);

		$errorConfig = $appConfig->error();

		$controllerInvoker = new HttpAddonContext($httpContext, $controllerRegistry, $responseCacheStore,
				$errorConfig->isLogHandleStatusExceptionsEnabled(), $errorConfig->getLogExcludedHttpStatus());
		$appN2nContext->setHttp($controllerInvoker);
		$appN2nContext->addAddonContext($controllerInvoker);
	}
}