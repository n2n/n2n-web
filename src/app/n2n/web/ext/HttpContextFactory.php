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


use n2n\core\config\AppConfig;
use n2n\core\err\ExceptionHandler;
use n2n\web\http\Request;
use n2n\core\container\N2nContext;
use n2n\web\http\Session;
use n2n\web\http\HttpContext;
use n2n\web\http\cache\ResponseCacheStore;
use n2n\web\http\BadRequestException;
use n2n\core\N2N;
use n2n\core\config\RoutingConfig;
use n2n\web\http\Supersystem;
use n2n\web\http\ManagedResponse;

class HttpContextFactory {

	static function createFromAppConfig(AppConfig $appConfig, Request $request, Session $session, N2nContext $n2nContext,
            ResponseCacheStore $responseCacheStore, ?ExceptionHandler $exceptionHandler): HttpContext {
		$generalConfig = $appConfig->general();
		$webConfig = $appConfig->web();
		$filesConfig = $appConfig->files();
		$errorConfig = $appConfig->error();
		$routingConfig = $appConfig->routing();
		
		$response = new ManagedResponse($request);
		$response->setResponseCachingEnabled($webConfig->isResponseCachingEnabled());
		$response->setContentSecurityPolicyEnabled($webConfig->isResponseContentSecurityPolicyEnabled());
		$response->setResponseCacheStore($responseCacheStore);
		$response->setHttpCachingEnabled($webConfig->isResponseBrowserCachingEnabled());
		$response->setSendEtagAllowed($webConfig->isResponseSendEtagAllowed());
		$response->setSendLastModifiedAllowed($webConfig->isResponseSendLastModifiedAllowed());
		$response->setServerPushAllowed($webConfig->isResponseServerPushAllowed());


		$assetsUrl = $filesConfig->getAssetsUrl();
		if ($assetsUrl->isRelative() && !$assetsUrl->getPath()->hasLeadingDelimiter()) {
			$assetsUrl = $request->getContextPath()->toUrl()->ext($assetsUrl);
		}
		
		$httpContext = new HttpContext($request, $response, $session, $assetsUrl,
				self::createSupersystem($routingConfig), self::createSubsystems($routingConfig), $n2nContext);

		$httpContext->setErrorStatusViewNames($errorConfig->getErrorViewNames());
		$httpContext->setErrorStatusDefaultViewName($errorConfig->getDefaultErrorViewName());

		if ($exceptionHandler !== null) {
			$prevError = $exceptionHandler->getPrevError();
			if ($prevError !== null && $appConfig->error()->isStartupDetectBadRequestsEnabled() && $prevError->isBadRequest()) {
				$httpContext->setPrevStatusException(new BadRequestException($prevError->getMessage(), null, $prevError));
			}
		}

        return $httpContext;
	}

	private static function createSupersystem(RoutingConfig $routingConfig): Supersystem {
		return new Supersystem($routingConfig->getN2nLocales(), $routingConfig->getResponseHeaders());
	}

	private static function createSubsystems(RoutingConfig $routingConfig): array {
		$subsystemBuilder = new SubsystemBuilder();
		foreach ($routingConfig->getRoutingRules() as $routingRule) {
			$subsystemBuilder->addSchema($routingRule->getMatcherName(), $routingRule->getSubsystemName(),
					$routingRule->getHostName(), $routingRule->getContextPath(), $routingRule->getN2nLocales(),
					$routingRule->getResponseHeaders());
		}
		return $subsystemBuilder->getSubsystems();
	}
}