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
use n2n\web\http\ResponseCacheControl;
use n2n\web\http\HttpCacheControl;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\ui\view\View;
use n2n\web\dispatch\Dispatchable;
use n2n\web\http\Redirect;
use n2n\web\http\NoHttpRefererGivenException;
use n2n\web\http\controller\Controller;
use n2n\web\http\controller\ControllingPlan;
use n2n\web\http\ResponseThing;
use n2n\core\container\N2nContext;
use n2n\web\http\HttpContext;
use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\ReflectionUtils;
use n2n\web\http\controller\ControllerErrorException;
use n2n\core\TypeNotFoundException;

trait ControllingUtilsTrait {
	private $controllingUtils;
	
	/**
	 * @return \n2n\web\http\controller\impl\ControllingUtils
	 */
	private function cu() {
		if ($this->controllingUtils !== null) {
			return $this->controllingUtils;
		}
		
		throw new IllegalStateException('Controller not active.');
	}
	
	private function init(ControllerContext $controllerContext) {
		return $this->controllingUtils = new ControllingUtils(get_class($this), $controllerContext);
	}
	
	protected final function getControllingUtils() {
		return $this->cu();
	}
	
	/**
	 *
	 * @return string
	 */
	protected final function getModuleNamespace(): string {
		return $this->cu()->getModuleNamespace();
	}
	
	protected final function getHttpContext(): HttpContext {
		return $this->cu()->getHttpContext();
	}
	/**
	 * @return \n2n\web\http\Request
	 */
	protected final function getRequest(): Request {
		return $this->cu()->getRequest();
	}
	/**
	 * @return \n2n\web\http\Response
	 */
	protected final function getResponse(): Response {
		return $this->cu()->getResponse();
	}
	/**
	 * @return \n2n\core\container\N2nContext
	 */
	protected final function getN2nContext(): N2nContext {
		return $this->cu()->getN2nContext();
	}
	/**
	 * @return ControllerContext
	 */
	protected final function getControllerContext(): ControllerContext {
		return $this->cu()->getControllerContext();
	}
	/**
	 * @return ControllingPlan
	 */
	protected final function getControllingPlan(): ControllingPlan {
		return $this->cu()->getControllingPlan();
	}
	
	protected function resetCacheControl() {
		$this->cu()->resetCacheControl();
	}

	protected final function beginTransaction($readOnly = false) {
		$this->cu()->beginTransaction($readOnly);
	}
	
	
	protected final function commit() {
		$this->cu()->commit();
	}
	
	protected final function rollBack() {
		$this->cu()->rollBack();
	}
	/**
	 *
	 * @param ViewCacheControl $viewCacheControl
	 */
	protected final function assignViewCacheControl(\DateInterval $cacheInterval = null, array $characteristics = array()) {
		$this->cu()->assignViewCacheControl($cacheInterval, $characteristics);
	}
	/**
	 *
	 * @param HttpCacheControl $httpCacheControl
	 */
	protected final function assignHttpCacheControl(\DateInterval $maxAge = null, array $directives = null) {
		$this->cu()->assignHttpCacheControl($maxAge, $directives);
	}
	
	protected final function resetHttpCacheControl() {
		$this->cu()->resetHttpCacheControl();
	}
	/**
	 * @param ResponseCacheControl $responseCacheControl
	 */
	protected final function assignResponseCacheControl(\DateInterval $cacheInterval = null,
			$includeQuery = false, array $characteristics = array()) {
		$this->cu()->assignResponseCacheControl($cacheInterval, $includeQuery, $characteristics);
	}
	
	protected final function resetResponseCacheControl() {
		$this->cu()->resetResponseCacheControl();
	}
	
	/**
	 *
	 * @param string $viewName
	 * @param ViewCacheControl $viewCacheControl
	 */
	protected final function createViewFromCache(string $viewNameExpression, string $moduleNamespace = null) {
		try {
			return $this->cu()->createViewFromCache($viewNameExpression, $moduleNamespace);
		} catch (TypeNotFoundException $e) {
			throw $this->decorateException($viewNameExpression, $e);
		}
	}
	
	/**
	 *
	 * @param string $viewName
	 * @param mixed $params
	 * @return View
	 */
	protected final function createView(string $viewNameExpression, array $params = null, string $moduleNamespace = null) {
		try {
			return $this->cu()->createView($viewNameExpression, $params, $moduleNamespace);
		} catch (TypeNotFoundException $e) {
			throw $this->decorateException($viewNameExpression, $e);
		}
	}
	/**
	 *
	 * @param string $viewNameExpression
	 * @param ViewCacheControl $viewCacheControl
	 * @param HttpCacheControl $httpCacheControl
	 * @param StaticCacheControl $staticCacheControl
	 * @return bool
	 */
	protected final function forwardCache(string $viewNameExpression, ViewCacheControl $viewCacheControl = null) {
		try {
			return $this->cu()->forwardCache($viewNameExpression, $viewCacheControl);
		} catch (TypeNotFoundException $e) {
			throw $this->decorateException($viewNameExpression, $e);
		}
	}
	/**
	 *
	 * @param string $viewNameExpression
	 * @param mixed $params
	 */
	protected final function forward(string $viewNameExpression, array $params = null, 
			ViewCacheControl $viewCacheControl = null) {
		try {
			$this->cu()->forward($viewNameExpression, $params, $viewCacheControl);
		} catch (TypeNotFoundException $e) {
			throw $this->decorateException($viewNameExpression, $e);
		}
	}
	
	private function decorateException(string $viewNameExpression, \Exception $e) {
		if (!($e instanceof \ErrorException) &&
				null !== ($lutp = ReflectionUtils::getLastMatchingUserTracemPointOfException($e, 0, (new \ReflectionClass($this))->getFileName()))) {
			return new ControllerErrorException('Failed to lookup view: ' . $viewNameExpression,
					$lutp['file'], $lutp['line'], null, null, $e);
		}
	
		return $e;
	}
	
	protected final function forwardView(View $view) {
		$this->cu()->forwardView($view);
	}
	
	protected final function dispatch(Dispatchable $dispatchable, $methodName = null) {
		return $this->cu()->dispatch($dispatchable, $methodName);
	}
	
	/**
	 * @see ControllingUtils::hasDispatch()
	 */
	protected function hasDispatch(Dispatchable $dispatchable = null, $methodName = null) {
		return $this->cu()->hasDispatch($dispatchable, $methodName);
	}
	
	protected final function refresh(int $httpStatus = null) {
		$this->cu()->refresh($httpStatus);
	}
	
	protected final function redirect($murl, int $httpStatus = null) {
		$this->cu()->redirect($murl, $httpStatus);
	}
	
	protected final function getUrlToContext($pathExt = null, array $queries = null,
			string $fragment = null, bool $ssl = null, $subsystem = null) {
		return $this->cu()->getUrlToContext($pathExt, $queries, $fragment, $ssl, $subsystem);
	}
	
	protected final function redirectToContext($pathExt = null, array $queries = null, int $httpStatus = null,
			string $fragment = null, bool $ssl = null, $subsystem = null) {
		$this->cu()->redirectToContext($pathExt, $queries, $httpStatus, $fragment, $ssl, $subsystem);
	}
	
	protected final function getUrlToController($pathExt = null, array $queries = null, $controllerContext = null, 
			string $fragment = null, bool $ssl = null, $subsystem = null) {
		return $this->cu()->getUrlToController($pathExt, $queries, $controllerContext, $fragment, $ssl, $subsystem);
	}
	
	protected final function redirectToController($pathExt = null, array $queries = null, int $httpStatus = null,
			$controllerContext = null, string $fragment = null, bool $ssl = null, $subsystem = null) {
		$this->cu()->redirectToController($pathExt, $queries, $httpStatus, $controllerContext, $fragment, $ssl, 
				$subsystem);
	}

	protected final function getUrlToPath($pathExt = null, array $queries = null, string $fragment = null, 
			bool $ssl = null, $subsystem = null) {
		return $this->cu()->getUrlToPath($pathExt, $queries, $fragment, $ssl, $subsystem);
	}
	
	protected final function redirectToPath($pathExt = null, array $queries = null, int $httpStatus = null,
			$fragment = null, bool $ssl = null, $subsystem = null) {
		$this->cu()->redirectToPath($pathExt, $queries, $httpStatus, $fragment, $ssl, $subsystem);
	}
	/**
	 * @param string $httpStatus
	 * @throws NoHttpRefererGivenException
	 */
	protected final function redirectToReferer(int $httpStatus = null) {
		$this->cu()->redirectToReferer($httpStatus);
	}
	
	/**
	 * @param Controller $controller
	 * @param unknown $pathPartsToShift
	 * @return ControllerContext
	 */
	protected final function createDelegateContext(Controller $controller = null, $pathPartsToShift = null) {
		return $this->cu()->createDelegateContext($controller, $pathPartsToShift);
	}
	
	protected final function delegate(Controller $controller, $pathPartsToShift = null, $execute = true,
			bool $tryIfMain = false) {
		return $this->cu()->delegate($controller, $pathPartsToShift, $execute, $tryIfMain);
	}
	
	protected final function delegateToControllerContext(ControllerContext $nextControllerContext, $execute = true,
			bool $tryIfMain = false) {
		return $this->cu()->delegateToControllerContext($nextControllerContext, $execute, $tryIfMain);
	}
	
	protected final function send(ResponseThing $responseThing, bool $includeBuffer = true) {
		$this->cu()->send($responseThing, $includeBuffer);
	}
	
	protected final function accepted(string ...$mimeTypes) {
		return $this->cu()->accepted(...$mimeTypes);
	}
	
	protected final function acceptQuality(string $mimeType) {
		return $this->cu()->acceptQuality($mimeType);
	}
}
