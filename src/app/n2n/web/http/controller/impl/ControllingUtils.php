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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\http\controller\impl;

use n2n\web\http\controller\ControllerContext;
use n2n\web\http\controller\ControllingPlanException;
use n2n\reflection\TypeExpressionResolver;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\IllegalStateException;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\http\controller\ControllingPlan;
use n2n\web\http\ResponseThing;
use n2n\web\http\controller\NoHttpRefererGivenException;
use n2n\web\http\nav\Murlable;
use n2n\web\http\Redirect;
use n2n\web\dispatch\DispatchContext;
use n2n\web\dispatch\Dispatchable;
use n2n\web\http\ResponseCacheControl;
use n2n\web\http\HttpCacheControl;
use n2n\web\ui\ViewFactory;
use n2n\reflection\CastUtils;
use n2n\web\http\controller\Controller;
use n2n\core\container\N2nContext;
use n2n\web\http\Response;
use n2n\web\http\Request;
use n2n\web\http\HttpContext;
use n2n\web\http\controller\InvokerInfo;
use n2n\web\ui\view\View;
use n2n\web\http\nav\Murl;
use n2n\web\http\BadRequestException;
use n2n\core\container\Transaction;

class ControllingUtils {
	private $relatedTypeName;
	private $controllerContext;
	private $typeExpressionResolver;
	private $invokerInfo;
	private $viewCacheControl;
	private $httpCacheControl;
	private $responseCacheControl;
	private $transactions = array();
	
	public function __construct(string $relatedTypeName, ControllerContext $controllerContext) {
		$this->relatedTypeName = $relatedTypeName;
		$this->controllerContext = $controllerContext;
		$this->typeExpressionResolver = new TypeExpressionResolver(ReflectionUtils::getNamespace($relatedTypeName),
				$this->getN2nContext()->getModuleManager());
	}

	/**
	 *
	 * @return string
	 */
	public function getModuleNamespace(): string {
		$moduleNamespace = $this->controllerContext->getModuleNamespace();
		if ($moduleNamespace !== null) return $moduleNamespace;
	
		return $this->getN2nContext()->getModuleManager()
				->getModuleOfTypeName($this->relatedTypeName, false)->getNamespace();
	}
	
	public function getHttpContext(): HttpContext {
		return $this->getN2nContext()->getHttpContext();
	}
	
	/**
	 * @return \n2n\web\http\Request
	 */
	public function getRequest(): Request {
		return $this->getHttpContext()->getRequest();
	}
	
	/**
	 * @return \n2n\web\http\Response
	 */
	public function getResponse(): Response {
		return $this->getHttpContext()->getResponse();
	}
	
	/**
	 * @return \n2n\core\container\N2nContext
	 */
	public function getN2nContext(): N2nContext {
		return $this->controllerContext->getControllingPlan()->getN2nContext();
	}
	
	/**
	 * @return ControllerContext
	 */
	public function getControllerContext(): ControllerContext {
		if ($this->controllerContext === null) {
			throw new ControllingPlanException('Controller not active.');
		}
	
		return $this->controllerContext;
	}
	
	/**
	 * @return ControllingPlan
	 */
	public function getControllingPlan(): ControllingPlan {
		return $this->controllerContext->getControllingPlan();
	}
	
	/**
	 * 
	 */
	public function reset() {
		while (null !== ($transaction = array_pop($this->transactions))) {
			$transaction->commit();
		}
	
		$this->controllerContext = null;
		$this->invokerInfo = null;
		$this->resetCacheControl();
	}
	
	/**
	 * 
	 */
	public function resetCacheControl() {
		$this->viewCacheControl = null;
		$this->httpCacheControl = null;
		$this->responseCacheControl = null;
	}
	
	/**
	 * @param string $readOnly
	 */
	public function beginTransaction($readOnly = false) {
		$this->transactions[] = $this->getN2nContext()->getTransactionManager()->createTransaction($readOnly);
	}
	
	/**
	 * @throws IllegalStateException
	 * @return Transaction
	 */
	private function peakTransaction() {
		if (null !== ($transaction = array_pop($this->transactions))) {
			return $transaction;
		}
	
		throw new IllegalStateException('No active transaction started in this controller.');
	}
	
	/**
	 * 
	 */
	public function commit() {
		$this->peakTransaction()->commit();
	}
	
	/**
	 * 
	 */
	public function rollBack() {
		$this->peakTransaction()->rollBack();
	}
	/**
	 *
	 * @param ViewCacheControl $viewCacheControl
	 */
	public function assignViewCacheControl(\DateInterval $cacheInterval = null, array $characteristics = array()) {
		$this->viewCacheControl = new ViewCacheControl($cacheInterval, $characteristics);
	}
	/**
	 *
	 * @param HttpCacheControl $httpCacheControl
	 */
	public function assignHttpCacheControl(\DateInterval $maxAge = null, array $directives = null) {
		$this->httpCacheControl = new HttpCacheControl($maxAge, $directives);
	}
	
	public function resetHttpCacheControl() {
		$this->httpCacheControl = null;
	}
	/**
	 * @param ResponseCacheControl $responseCacheControl
	 */
	public function assignResponseCacheControl(\DateInterval $cacheInterval = null,
			bool $includeQuery = false, array $characteristics = array()) {
		$queryParamNames = null;
		if ($includeQuery) {
			$queryParamNames = array_keys($this->getInvokerInfo()->getQueryParams());
		}

		$this->responseCacheControl = new ResponseCacheControl($cacheInterval, $queryParamNames, $characteristics);
	}
	
	public function resetResponseCacheControl() {
		$this->responseCacheControl = null;
	}
	
	/**
	 *
	 * @param string $viewName
	 * @param ViewCacheControl $viewCacheControl
	 */
	public function createViewFromCache(string $viewNameExpression, string $moduleNamespace = null) {
		if ($this->viewCacheControl === null) return null;
	
		$viewFactory = $this->getN2nContext()->lookup(ViewFactory::class);
		CastUtils::assertTrue($viewFactory instanceof ViewFactory);
	
		$viewName = $this->typeExpressionResolver->resolve($viewNameExpression);
		return $viewFactory->createFromCache($viewName, $this->viewCacheControl, $moduleNamespace);
	}
	
	/**
	 *
	 * @param string $viewName
	 * @param mixed $params
	 * @return View
	 */
	public function createView(string $viewNameExpression, array $params = null, string $moduleNamespace = null) {
		$viewName = $this->typeExpressionResolver->resolve($viewNameExpression);
	
		$viewFactory = $this->getN2nContext()->lookup(ViewFactory::class);
		CastUtils::assertTrue($viewFactory instanceof ViewFactory);
	
		$view = $viewFactory->create($viewName, $params, $moduleNamespace);
		$view->setControllerContext($this->controllerContext);
	
		if (null !== $this->viewCacheControl) {
			$viewFactory->cache($view, $this->viewCacheControl);
		}
	
		return $view;
	}
	/**
	 *
	 * @param string $viewNameExpression
	 * @param ViewCacheControl $viewCacheControl
	 * @param HttpCacheControl $httpCacheControl
	 * @param StaticCacheControl $staticCacheControl
	 */
	public function forwardCache(string $viewNameExpression, ViewCacheControl $viewCacheControl = null) {
		$cachedView = $this->createViewFromCache($viewNameExpression, $viewCacheControl);
		if (null === $cachedView) return false;
	
		$this->forwardView($cachedView);
		return true;
	}
	/**
	 *
	 * @param string $viewNameExpression
	 * @param mixed $params
	 */
	public function forward(string $viewNameExpression, array $params = null,
			ViewCacheControl $viewCacheControl = null) {
		$this->forwardView($this->createView($viewNameExpression, $params, $viewCacheControl));
	}
	
	public function forwardView(View $view) {
		$this->assignCacheControls();
		$this->getResponse()->send($view);
	}
	
	public function dispatch(Dispatchable $dispatchable, $methodName = null) {
		try {
			return $this->getN2nContext()->lookup(DispatchContext::class)->dispatch($dispatchable, $methodName,
					$this->getN2nContext());
		} catch (\n2n\web\dispatch\map\CorruptedDispatchException $e) {
			throw new BadRequestException(null, 0, $e);
		}
	}
	
	public function refresh(int $httpStatus = null) {
		$this->redirect($this->getRequest()->getUrl(), $httpStatus);
	}
	
	private function assignCacheControls() {
		if ($this->httpCacheControl !== null) {
			$this->getResponse()->setHttpCacheControl($this->httpCacheControl);
		}
	
		if ($this->responseCacheControl !== null) {
			$this->getResponse()->setResponseCacheControl($this->responseCacheControl);
		}
	}
	
	public function redirect($murl, int $httpStatus = null) {
		$this->assignCacheControls();
	
		if ($murl instanceof Murlable) {
			$murl = $murl->toUrl($this->getN2nContext(), $this->controllerContext);
		}
	
		$this->getResponse()->send(new Redirect($murl, $httpStatus));
	}
	
	public function getUrlToContext($pathExt = null, array $queries = null, int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		return $this->getHttpContext()->buildContextUrl($ssl, $subsystem)->extR($pathExt, $queries, $fragment);
	}
	
	public function redirectToContext($pathExt = null, array $queries = null, int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect(
				$this->getUrlToContext($pathExt, $queries, $httpStatus,	$fragment, $ssl, $subsystem),
				$httpStatus));
	}
	
	public function getUrlToController($pathExt = null, array $queries = null, $controllerContext = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		if (isset($controllerContext)) {
			if (!($controllerContext instanceof ControllerContext)) {
				$controllerContext = $this->getRequest()->getControllerContextByName((string) $controllerContext);
			}
		} else {
			$controllerContext = $this->controllerContext;
		}

		return Murl::controller($controllerContext)->pathExt($pathExt)
				->queryExt($queries)->fragment($fragment)->ssl($ssl)->subsystem($subsystem)
				->toUrl($this->getN2nContext());
	}
	
	public function redirectToController($pathExt = null, array $queries = null, int $httpStatus = null,
			$controllerContext = null, $fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect(
				$this->getUrlToController($pathExt, $queries, $controllerContext, $fragment, $ssl, $subsystem),
				$httpStatus));
	}
	

	public function getUrlToPath($pathExt = null, array $queries = null, string $fragment = null, bool $ssl = null, 
			$subsystem = null) {
		return $this->getHttpContext()->buildContextUrl($ssl, $subsystem)->extR($this->getRequest()->getCmdPath())
				->extR($pathExt, $queries, $fragment);
	}
	
	public function redirectToPath($pathExt = null, array $queries = null, int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect($this->getUrlToPath($pathExt, $queries, $fragment, $ssl, $subsystem), 
				$httpStatus));
	}
	/**
	 * @param string $httpStatus
	 * @throws NoHttpRefererGivenException
	 */
	public function redirectToReferer(int $httpStatus = null) {
		if (null !== ($referer = $this->getRequest()->getHeader('Referer'))) {
			$this->assignCacheControls();
			$this->redirect($referer, $httpStatus);
			return;
		}
	
		throw new NoHttpRefererGivenException('Request contains no referer.');
	}
	
	private function getInvokerInfo() {
		if ($this->invokerInfo === null) {
			throw new ControllingPlanException('No controller method executing.');
		}
	
		return $this->invokerInfo;
	}
	
	public function setInvokerInfo(InvokerInfo $invokerInfo = null) {
		$this->invokerInfo = $invokerInfo;
	}
	
	/**
	 * @param Controller $controller
	 * @param unknown $pathPartsToShift
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function createDelegateContext(Controller $controller = null, $pathPartsToShift = null) {
		$controllerContext = $this->controllerContext;
	
		if ($pathPartsToShift === null) {
			$pathPartsToShift = $this->getInvokerInfo()->getNumSinglePathParts();
		}
	
		$cmdPath = $controllerContext->getCmdPath();
		$cmdContextPath = $controllerContext->getCmdContextPath();
	
		return new ControllerContext($cmdPath->sub($pathPartsToShift),
				$cmdContextPath->ext($cmdPath->sub(0, $pathPartsToShift)), $controller);
	}
	
	public function delegate(Controller $controller, $pathPartsToShift = null, $execute = true, bool $try = false) {
		return $this->delegateToControllerContext($this->createDelegateContext($controller, $pathPartsToShift), $execute, 
				$try);
	}
	
	public function delegateToControllerContext(ControllerContext $nextControllerContext, bool $execute = true, 
			bool $try = false) {
		$plan = $this->controllerContext->getControllingPlan();
	
		if ($plan->getStatus() == ControllingPlan::STATUS_FILTER) {
			$plan->addFilter($nextControllerContext, $execute);
			if ($execute) return $plan->executeNextFilter();
		} else {
			$plan->addMain($nextControllerContext, $execute);
			if ($execute) return $plan->executeNextMain($try);
		}
		
		return null;
	}
	
	public function send(ResponseThing $responseThing, bool $includeBuffer = true) {
		$this->assignCacheControls();
		$this->getResponse()->send($responseThing, $includeBuffer);
	}
	
	public function accepted(string ...$mimeTypes) {
		
	}
	
}
