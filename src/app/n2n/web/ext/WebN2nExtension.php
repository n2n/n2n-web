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

use n2n\core\container\impl\AppN2nContext;
use n2n\web\http\VarsRequest;
use n2n\web\http\VarsSession;
use n2n\web\http\controller\ControllerRegistry;
use n2n\core\N2N;
use n2n\web\http\cache\ResponseCacheStore;
use n2n\core\VarStore;
use n2n\web\http\cache\PayloadCacheStore;
use n2n\core\N2nApplication;
use n2n\core\ext\ConfigN2nExtension;
use n2n\web\http\cache\ResponseCacheVerifying;
use n2n\web\http\Session;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;

class WebN2nExtension implements ConfigN2nExtension {

	private ?\Closure $sessionFactory = null;

	public function __construct(private N2nApplication $n2nApplication) {
	}

	function setSessionFactory(\Closure $sessionFactory): static {
		$this->sessionFactory = $sessionFactory;
		return $this;
	}

	function getSessionFactory(): ?\Closure {
		return $this->sessionFactory;
	}

	private function createSession(AppN2nContext $appN2nContext): Session {
		if ($this->sessionFactory !== null) {
			$mmi = new MagicMethodInvoker($appN2nContext);
			$mmi->setClosure($this->sessionFactory);
			$mmi->setReturnTypeConstraint(TypeConstraints::type(Session::class));
			return $mmi->invoke();
		}

		$appConfig = $appN2nContext->getAppConfig();
		$session = new VarsSession($appConfig->general()->getApplicationName());

		if ($appConfig->general()->isApplicationReplicatable()) {
			$session->setDirFsPath($appN2nContext->getVarStore()->requestDirFsPath(VarStore::CATEGORY_TMP,
					N2N::NS, 'sessions', shared: true));
		}

		return $session;
	}

	function applyToN2nContext(AppN2nContext $appN2nContext): void {
		$responseCacheStore = new ResponseCacheStore($appN2nContext->getAppCache(), $appN2nContext->getTransactionManager(),
				new ResponseCacheVerifying($appN2nContext));
		$payloadCacheStore = new PayloadCacheStore($appN2nContext->getAppCache(), $appN2nContext->getTransactionManager());

		if (!isset($appN2nContext->getPhpVars()->server['REQUEST_URI'])) {
			$appN2nContext->addAddonContext(new HttpAddonContext(null, null,
					$responseCacheStore, $payloadCacheStore));
			return;
		}

		$phpVars = $appN2nContext->getPhpVars();
		$request = new VarsRequest($phpVars->server, $phpVars->get, $phpVars->post, $phpVars->files);
		$request->legacyN2nContext = $appN2nContext;
		$appConfig = $appN2nContext->getAppConfig();

		$session = $this->createSession($appN2nContext);

		$httpContext = HttpContextFactory::createFromAppConfig($appConfig, $request, $session, $appN2nContext,
				$responseCacheStore, N2N::getExceptionHandler());

		$appN2nContext->setN2nLocale($httpContext->determineBestN2nLocale());

		$controllerRegistry = new ControllerRegistry($appConfig->web(), $appConfig->routing(),  $httpContext);

		$errorConfig = $appConfig->error();

		$controllerInvoker = new HttpAddonContext($httpContext, $controllerRegistry, $responseCacheStore,
				$payloadCacheStore, $errorConfig->isLogHandleStatusExceptionsEnabled(),
				$errorConfig->getLogExcludedHttpStatus());
		$appN2nContext->setHttp($controllerInvoker);
		$appN2nContext->addAddonContext($controllerInvoker);
	}
}