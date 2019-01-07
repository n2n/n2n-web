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
namespace n2n\web\ui\view;

use n2n\l10n\DynamicTextCollection;
use n2n\l10n\DateTimeFormat;
use n2n\l10n\L10nUtils;
use n2n\web\http\controller\ControllerContext;
use n2n\io\ob\OutputBuffer;
use n2n\core\N2N;
use n2n\web\ui\UiComponent;
use n2n\web\ui\UiException;
use n2n\web\http\Response;
use n2n\web\http\payload\BufferedPayload;
use n2n\core\module\Module;
use n2n\core\container\N2nContext;
use n2n\util\type\ArgUtils;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\ReflectionUtils;
use n2n\web\http\UnknownControllerContextException;
use n2n\l10n\N2nLocale;
use n2n\web\http\HttpContext;
use n2n\web\ui\ViewFactory;
use n2n\reflection\TypeExpressionResolver;
use n2n\util\type\CastUtils;
use n2n\web\http\nav\UrlBuilder;
use n2n\util\uri\UnavailableUrlException;
use n2n\web\ui\BuildContext;
use n2n\web\ui\SimpleBuildContext;

abstract class View extends BufferedPayload implements UiComponent {
	private $params = array();
	private $stateObjs = array();
	
	private $scriptPath;
	private $moduleNamespace;
	private $n2nContext;
	private $contentBuffer;

	private $contents;
	private $templateView;
	private $stateListeners;
	private $controllerContext = null;
	protected $contentsBuildContext;
	
	private $contentView = null;
	private $activePanelBuffer = null;
	private $bufferingPanel = null;
	private $panels = array();
	/**
	 * @param string $scriptPath
	 * @param string $name
	 * @param Module $module
	 * @param N2nContext $n2nContext
	 */
	public final function __construct(string $scriptPath, string $name, Module $module, N2nContext $n2nContext) {
		$this->scriptPath = $scriptPath;
		$this->name = $name;
		$this->moduleNamespace = $module;
		$this->n2nContext = $n2nContext;
		$this->stateListeners = array();
		$this->contentsBuildContext = new SimpleBuildContext($this);
		$this->reset();
	}
	
	public function getParams() {
		return $this->params;
	}
	
	public function setParams(array $params) {
		$this->params = $params;
	}
	/**
	 * 
	 * @throws UndefinedViewNameException
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * 
	 * @return string
	 */
	public function getScriptPath() {
		return $this->scriptPath;
	}
	/**
	 * 
	 * @return string
	 */
	public function getModuleNamespace(): string {
		if ($this->moduleNamespace === null) {
			$currentNamespace = ReflectionUtils::getNamespace($this->getName());
			$module = $this->getN2nContext()->getModuleManager()
					->getModuleOfTypeName($currentNamespace, false);
			if ($module === null) {
				$this->moduleNamespace = $currentNamespace;
			} else {
				$this->moduleNamespace = (string) $module;
			}
		}
		
		return $this->moduleNamespace;
	}
	
	private $resolver;
	
	private function resolveViewName(string $viewNameExpression) {
		if ($this->resolver === null) {
			$this->resolver = new TypeExpressionResolver(ReflectionUtils::getNamespace($this->getName()), 
					$this->getN2nContext()->getModuleManager());
		}
		
		return $this->resolver->resolve($viewNameExpression);
	}
	
	/**
	 * @return \n2n\web\http\HttpContext
	 */
	public function getHttpContext(): HttpContext {
		return $this->n2nContext->getHttpContext();
	}
	
	/**
	 * @return \n2n\core\container\N2nContext
	 */
	public function getN2nContext(): N2nContext {
		return $this->n2nContext;
	}
	
	/**
	 * @return \n2n\l10n\N2nLocale
	 */
	public function getN2nLocale(): N2nLocale {
		return $this->getHttpContext()->getRequest()->getN2nLocale();
	}
	
	public function getRequest() {
		return $this->getHttpContext()->getRequest();
	}
	
	/**
	 * 
	 * @param ControllerContext $controllerContext
	 */
	public function setControllerContext(ControllerContext $controllerContext = null) {
		$this->controllerContext = $controllerContext;
	} 
	
	public function hasControllerContext() {
		return $this->controllerContext !== null;
	}
	/**
	 * 
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function getControllerContext() {
		if ($this->controllerContext !== null) {
			return $this->controllerContext;
		}
		
		throw $this->decorateException(
				new UnknownControllerContextException('No Controller Context assigned to View.'));		
	}
	/**
	 *
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function getControllerContextByName($name) {
		return $this->getControllerContext()->getControllingPlan()->getByName($name);
	}
	
	protected function getStateObjs() {
		return $this->stateObjs;
	}
	
	protected function setStateObjs(array $stateObjs) {
		$this->stateObjs = $stateObjs;
	}
	
	public function getStateObj(string $refTypeName) {
		if (isset($this->stateObjs[$refTypeName])) {
			return $this->stateObjs[$refTypeName];
		}
		
		return null;
	}
	
	public function setStateObj(string $refTypeName, $stateObj) {
		$this->stateObjs[$refTypeName] = $stateObj;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function isInitialized() {
		return $this->contents !== null;
	}
	/**
	 * 
	 * @param Response $response
	 * @param View $contentView
	 */
	public final function initialize(View $contentView = null, BuildContext $buildContext = null) {
		$this->ensureContentsAreNotInitialized();
		$this->ensureBufferIsNotActive();
		
		$this->contentView = $contentView;
		if ($contentView !== null) {
			$this->panels = $contentView->getPanels();
		}
		
		if ($this->n2nContext->isHttpContextAvailable()) {
			$this->contentBuffer = $this->getHttpContext()->getResponse()->createOutputBuffer();
		} else {
			$this->contentBuffer = new OutputBuffer();
		}
		
		if ($buildContext === null) {
			$buildContext = new SimpleBuildContext();
		}

		$this->compile($this->contentBuffer, $buildContext);

		$this->contentBuffer = null;
		
		$this->triggerContentsInitialized();
	}
	/**
	 * @param string $data
	 * @throws \InvalidArgumentException if not good
	 */
	public function initializeFromCache($data) {
		$this->ensureContentsAreNotInitialized();
		$this->ensureBufferIsNotActive();
		
		ArgUtils::valType($data, 'scalar');
		$this->contents = $data;
	}
	/**
	 * @return mixed
	 */
	public function toCacheData() {
		$this->ensureContentsAreInitialized();
		return $this->contents;
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\ui.UiComponent::build()
	 */
	public function build(BuildContext $buildContext): string {
		if (!$this->isInitialized()) {
			$this->initialize(null, $buildContext);
		}
// 		$this->ensureContentsAreInitialized();
	
		return $this->contents;
	}
	
	/**
	 * @return string
	 */
	public function getContents() {
		return $this->build(new SimpleBuildContext());
	}
	
	/**
	 * 
	 * @param mixed $contents
	 */
	public function setContents($contents) {
		$this->ensureContentsAreNotInitialized();
		$this->ensureBufferIsNotActive();
		
		$this->contents = (string) $contents;
		
		$this->triggerContentsInitialized();
	}
	/**
	 * 
	 */
	public final function reset() {
		$this->ensureBufferIsNotActive();
		
		$this->contents = null;
		$this->contentView = null;
		$this->activePanelBuffer = null;
		$this->bufferingPanel = null;
		$this->panels = array();
	}
	
	/**
	 * @return \n2n\web\ui\SimpleBuildContext
	 */
	public function getContentsBuildContext() {
		return $this->contentsBuildContext;
	}
	
	/**
	 * 
	 */
	private function triggerContentsInitialized() {
		foreach ($this->stateListeners as $stateListener) {
			$stateListener->viewContentsInitialized($this);
		}
	}
	/**
	 * 
	 * @param ViewStateListener $stateListener
	 */
	public function registerStateListener(ViewStateListener $stateListener) {
		$this->stateListeners[spl_object_hash($stateListener)] = $stateListener;
	}
	/**
	 * 
	 * @param ViewStateListener $stateListener
	 */
	public function unregisterStateListener(ViewStateListener $stateListener) {
		unset($this->stateListeners[spl_object_hash($stateListener)]);
	}
	/**
	 * 
	 * @return boolean
	 */
	protected function getContentView() {
		return $this->contentView;
	}
	
	/**
	 * 
	 * @param array $viewVars
	 * @param \Closure $bufferingEnded
	 * @throws ViewPanelNerverEndedException
	 */
	protected final function bufferContents(array $viewVars, \Closure $bufferingEnded = null) {
		$this->ensureBufferIsNotActive();
		
		foreach ($this->stateListeners as $stateListener) {
			$stateListener->onViewContentsBuffering($this);
		}
		
		foreach($viewVars as $varName => $varValue) {
			$$varName = $varValue;
		}
	
		$this->contentBuffer->start();
		
		try {
			include($this->scriptPath);
		} catch (\Exception $e) {
			throw $this->decorateException($e);
		}
	
		if ($this->bufferingPanel !== null) {
			throw new ViewErrorException('Panel was never closed: ' . $this->bufferingPanel->getName(), 
					$this->getScriptPath());
		}

		$this->contentBuffer->end();
		if ($bufferingEnded !== null) {
			$bufferingEnded($this->contentBuffer);
		}
		
		$this->contents = $this->contentBuffer->getBufferedContents();
		$this->contentBuffer->clean();
		
		if ($this->templateView !== null) {
			$this->templateView->initialize($this);
			$this->contents = $this->templateView->build(new SimpleBuildContext());
		}
	}
	/**
	 * 
	 * @return boolean
	 */
	public final function isBuffering() {
		return isset($this->contentBuffer) && $this->contentBuffer->isBuffering();
	}
	
	public final function getContentBuffer() {
		$this->ensureBufferIsActive();
		
		return $this->contentBuffer;
	}

	public final function getActiveBuffer() {
		$this->ensureBufferIsActive();
	
		if ($this->activePanelBuffer !== null) {
			return $this->activePanelBuffer;
		}
	
		return $this->contentBuffer;
	}
	/**
	 * 
	 * @throws ViewNotInitializedException
	 */
	protected function ensureContentsAreInitialized() {
		if ($this->isInitialized()) return;
		
		throw new IllegalStateException('View not yet initialized: ' . $this->getName());
	}
	/**
	 * 
	 * @throws ViewIsAlreadyInitializedException
	 */
	protected function ensureContentsAreNotInitialized() {
		if (!$this->isInitialized()) return;
		
		throw new IllegalStateException('View already initialized: ' . $this->getName());
	}
	/**
	 * 
	 * @throws ViewIsNotBufferingException
	 */
	protected function ensureBufferIsActive() {
		if ($this->isBuffering()) return;
		
		throw new IllegalStateException('View has no active buffer:' . $this->getName());
	}
	/**
	 * 
	 * @throws ViewIsBufferingException
	 */
	protected function ensureBufferIsNotActive() {
		if (!$this->isBuffering()) return;
		
		throw new IllegalStateException('View has an active buffer: ' . $this->getName());
	}
	/**
	 * 
	 * @return string
	 */
	public abstract function getContentType();
	/**
	 * Builds view content
	 * @return OutputBuffer
	 */
	protected function compile(OutputBuffer $contentBuffer, BuildContext $buildContext) {
		$n2nContext = $this->getN2nContext();
		return $this->bufferContents(array('request' => $this->getHttpContext()->getRequest(), 
				'response' => $this->getHttpContext()->getResponse(), 'view' => $this,
				'httpContext' => $this->getHttpContext()));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\http.Payload::prepareForResponse()
	 */
	public function prepareForResponse(Response $response) {
		$response->setHeader('Content-Type: ' . $this->getContentType());

		if (!$this->isInitialized()) {
			$this->initialize();
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\http.Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return $this->__toString();
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\http.Payload::__toString()
	 */
	public function __toString(): string {
		return 'View' . '(' . $this->getName() . ')';
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\http.BufferedPayload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		return $this->build(new SimpleBuildContext());
	}
	/**
	 * 
	 * @return array
	 */
	public function getPanels() {
		return $this->panels;
	}
	/**
	 * 
	 * @param string $panelName
	 * @return boolean
	 */
	public function hasPanel($panelName) {
		return isset($this->panels[$panelName]);
	}
	/**
	 * 
	 * @param string $panelName
	 * @return Panel
	 */
	public function getOrCreatePanel($panelName) {
		$panelName = (string) $panelName;
		if (!$this->hasPanel($panelName)) {
			$this->putPanel(new Panel($panelName));
		}
		return $this->panels[$panelName];
	}
	/**
	 * 
	 * @param Panel $panel
	 */
	public function putPanel(Panel $panel) {
		$this->panels[$panel->getName()] = $panel;
	}
	/**
	 * 
	 * @param string $panelName
	 * @param UiComponent $uiComponent
	 */
	public function appendToPanel(string $panelName, UiComponent $uiComponent) {
		$this->getOrCreatePanel($panelName)->append($uiComponent);
	}
	/**
	 * 
	 * @param \Exception $e
	 * @return \Exception either ViewErrorException or a ViewStuffFailedException
	 */				 
	private function decorateException(\Exception $e) {
		if (!($e instanceof \ErrorException) && 
				null !== ($lutp = ReflectionUtils::getLastMatchingUserTracemPointOfException($e, 0, $this->getScriptPath()))) {
			return new ViewErrorException('Error while compiling view: ' . $this->getName(), 
					$lutp['file'], $lutp['line'], null, null, $e);
		}
		
		return $e;
	}
	
	/*
	 * VIEW SCRIPT UTILS
	 */
	
	private $dynamicTextCollection = null;
	
	public function getParam($name, $required = true, $default = null) {
		if (is_array($this->params) && array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}
	
		if (!$required) return $default;
	
		throw new InvalidViewArgumentException('Undefined view parameter: ' .  $name);
	}
	
	/**
	 * @param array $params
	 */
	public function mergeParams(array $params = array()) {
		if (empty($params)) return $this->params;
		
		return array_merge($this->params, $params);	
	}
	
	/**
	 * @param boolean 
	 */
	public function assert($e) {
		if ($e) return;
		
		throw $this->decorateException(
				new ViewAssertionFailedException('View assertion failed'));
	}
	/**
	 * 
	 * @param string|\ReflectionClass $useableClassName
	 * @return \n2n\context\Lookupable
	 */
	public function lookup($useableClassName) {
		return $this->getN2nContext()->lookup($useableClassName);
 	}
	/**
	 * 
	 * @param string $viewNameExpression
	 * @param mixed $params
	 */
	public function useTemplate(string $viewNameExpression, array $params = null) {
		$this->ensureContentsAreNotInitialized();
		
		$this->templateView = $this->getN2nContext()->lookup(ViewFactory::class)->create(
				$this->resolveViewName($viewNameExpression), 
				$params);
		
		if ($this->hasControllerContext()) {
			$this->templateView->setControllerContext($this->getControllerContext());
		}
	}
	/**
	 * 
	 * @param string $name
	 * @param bool $append
	 */
	public function panelStart(string $name, bool $append = false) {
		$this->ensureBufferIsActive();
		
		if (isset($this->bufferingPanel)) {
			throw new ViewPanelAlreadyStartedException('View panel already started: ' 
					. $this->bufferingPanel->getName());
		}
		
		if ($append) {
			$this->bufferingPanel = $this->getOrCreatePanel($name);
		} else {
			$this->bufferingPanel = new Panel($name);
			$this->putPanel($this->bufferingPanel);
		}
		
		$this->activePanelBuffer = $this->getHttpContext()->getResponse()->createOutputBuffer();
		$this->activePanelBuffer->start();
	}
	
	/**
	 * 
	 */
	public function panelEnd() {
		$this->ensureBufferIsActive();
	
		if ($this->bufferingPanel === null) {
			throw $this->decorateException(new IllegalStateException(
					'No panel started in View: ' . $this->getName()));
		}
	
		$this->activePanelBuffer->end();
		$this->bufferingPanel->append($this->activePanelBuffer->getBufferedContents());
		$this->bufferingPanel = null;
		$this->activePanelBuffer->clean();
		$this->activePanelBuffer = null;
	}

	/**
	 * @param string|View $viewName
	 * @param array $params
	 */
	public function import($viewNameExpression, array $params = null, ViewCacheControl $viewCacheControl = null, 
			Module $module = null) {
		$this->out($this->getImport($viewNameExpression, $params, $viewCacheControl, $module));
	}
	
	/**
	 * @param string|View $viewName
	 * @param mixed $params
	 */
	public function getImport($viewNameExpression, array $params = null, 
			ViewCacheControl $viewCacheControl = null, Module $module = null) {
		$view = null;
		if ($viewNameExpression instanceof View) {
			$view = $viewNameExpression;
		} else {
			ArgUtils::valType($viewNameExpression, ['string', View::class], 'viewNameExpression');
			$view = $this->createImportView($viewNameExpression, $params, $viewCacheControl, $module);
		}
		$view->setControllerContext($this->controllerContext);
		
		if (!$view->isInitialized()) {
			$view->setStateObjs($this->getStateObjs());
		}
		
		return $view;
	}
	
	protected function createImportView(string $viewNameExpression, array $params = null, 
			ViewCacheControl $viewCacheControl = null, Module $module = null) {
				
		$viewName = $this->resolveViewName($viewNameExpression);
		
		$viewFactory = $this->getHttpContext()->getN2nContext()->lookup(ViewFactory::class);
		CastUtils::assertTrue($viewFactory instanceof ViewFactory);
		
		if ($viewCacheControl !== null) {
			$view = $viewFactory->createFromCache($viewName, $viewCacheControl, $module);
			if (null !== $view) return $view;
		}
		
		$view = $viewFactory->create($viewName, $params, $module);
		if ($viewCacheControl !== null) {
			$viewFactory->cache($view, $viewCacheControl);
		}
		
		return $view; 
	}
	/**
	 * 
	 */
	public function importContentView() {
		if (isset($this->contentView)) {
			$this->import($this->contentView);
		}
	}
	/**
	 * 
	 * @param string $panelName
	 */
	public function importPanel(string $panelName) {
		foreach ($this->stateListeners as $stateListener) {
			$stateListener->onPanelImport($this, $panelName);
		}
		
		if (!$this->hasPanel($panelName)) return;
		$this->out($this->getOrCreatePanel($panelName)->buildContents($this->contentsBuildContext));
	} 
	/**
	 * @param UiComponent|string $contents
	 */
	public function out($contents) {
		// $this->ensureBufferIsActive();
		
		echo $this->getOut($contents); 
	}
	
	/**
	 * @param UiComponent|string $contents
	 */
	public function getOut($contents) {
// 		if ($contents instanceof View && !$contents->isInitialized()) {
// 			$contents->initialize();
// 		}
		
		if ($contents instanceof UiComponent) {
			return $contents->build($this->contentsBuildContext);
		}
		
		return (string) $contents;
	}
	
	/**
	 * @param UiComponent|string $contents
	 */
	public function delayedOut($contents) {
		$cb = $this->getContentBuffer();
		$key = $cb->breakPoint();
		$that = $this;
		$cb->on(function () use ($cb, $contents, $that) {
			$cb->insertOnBreakPoint($key, $that->getOut($contents));
		});
	}
	/**
	 * @param string $groupName
	 * @param string $severity
	 * @param string $translate
	 * @return \n2n\l10n\Message[]
	 */
	public function getMessages($groupName = null, $severity = null) {
		return $this->getHttpContext()->getMessageContainer()->getAll($groupName, $severity);
	}
	/**
	 * @return \n2n\l10n\DynamicTextCollection
	 */
	public function getDynamicTextCollection() {
		if ($this->dynamicTextCollection === null) {
			$this->dynamicTextCollection = new DynamicTextCollection($this->moduleNamespace, 
					$this->getHttpContext()->getRequest()->getN2nLocale());
		}
		return $this->dynamicTextCollection;
	}
	/**
	 * @param DynamicTextCollection $dynamicTextCollection
	 */
	public function setDynamicTextCollection(DynamicTextCollection $dynamicTextCollection) {
		$this->dynamicTextCollection = $dynamicTextCollection;
	}
	/**
	 * @return string
	 */
	public function getL10nText($code, array $args = null, $num = null, array $replacements = null, $module = null) {
		if ($module === null) {
			return $this->getDynamicTextCollection()->translate($code, $args, $num, $replacements);
		}
		
		return L10nUtils::translateModuleTextCode($this->getDynamicTextCollection(), $module, 
				$code, $args, $num, $replacements);
	}
	
	/**
	 * @param float $value
	 * @param string $currency The 3-letter ISO 4217 currency code indicating the currency to use.
	 * @return string
	 */
	public function getL10nCurrency($value, $currency = null) {
		if ($value === null) return $value;
		return L10nUtils::formatCurrency($value, $this->getN2nContext()->getN2nLocale(), $currency);
	}
	
	public function getL10nNumber($value, $style = \NumberFormatter::DECIMAL, $pattern = null) {
		if (is_null($value)) return $value;
		return L10nUtils::formatNumber($value, $this->getN2nContext()->getN2nLocale(), $style, $pattern);
	}
	
	public function getL10nDate(\DateTime $value = null, $dateStyle = null, \DateTimeZone $timeZone = null) {
		if (is_null($value)) return $value;
		return L10nUtils::formatDateTime($value, $this->getN2nContext()->getN2nLocale(), $dateStyle, DateTimeFormat::STYLE_NONE, $timeZone);
	}
	
	public function getL10nTime(\DateTime $value = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		if (is_null($value)) return $value;
		return L10nUtils::formatDateTime($value, $this->getN2nContext()->getN2nLocale(), DateTimeFormat::STYLE_NONE, $timeStyle, $timeZone);
	}	
	
	public function getL10nDateTime(\DateTime $value = null, $dateStyle = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		if (is_null($value)) return $value;
		return L10nUtils::formatDateTime($value, $this->getN2nContext()->getN2nLocale(), $dateStyle, $timeStyle, $timeZone);
	}
	
	public function getL10nDateTimeFormat(\DateTime $value = null, $icuPattern, \DateTimeZone $timeZone = null) {
		if (is_null($value)) return $value;
		return L10nUtils::formatDateTimeWithIcuPattern($value, $this->getN2nContext()->getN2nLocale(), $icuPattern, $timeZone);
	}
	
	public function buildUrl($murl, bool $required = true, string &$suggestedLabel = null) {
		try {
			return UrlBuilder::buildUrl($murl, $this->n2nContext, $this->controllerContext, $suggestedLabel);
		} catch (UnavailableUrlException $e) {
			if ($required) throw $e;
			return null;
		}
	}
	
	public function buildUrlStr($murl, bool $required = true, string &$suggestedLabel = null) {
		try {
			return UrlBuilder::buildUrlStr($murl, $this->n2nContext, $this->controllerContext, $suggestedLabel);
		} catch (UnavailableUrlException $e) {
			if ($required || $e->isCritical()) throw $e;
			return null;
		}
	}
	
	/**
	 * @param View $view
	 * @return View
	 */
	public static function view(View $view): View {
		return $view;
	}
	
	/**
	 * @param View $view
	 * @return \n2n\web\http\Request
	 */
	public static function request(View $view) {
		return $view->getRequest();
	}
	
	/**
	 * @param View $view
	 * @return \n2n\web\http\Response
	 */
	public static function response(View $view) {
		return $view->getN2nContext()->getHttpContext()->getResponse();
	}
	
	/**
	 * @param View $view
	 * @return \n2n\web\http\HttpContext
	 */
	public static function httpContext(View $view) {
		return $view->getHttpContext();
	}
}

class UndefinedViewNameException extends UiException {
	
}

class UndefinedViewParameterException extends UiException {
	
} 

class ViewIsAlreadyInitializedException extends UiException {

}

class ViewNotInitializedException extends UiException {
	
}

class ViewIsNotBufferingException extends UiException {
	
}

class ViewIsBufferingException extends UiException {
	
} 

class ViewPanelAlreadyStartedException extends UiException {
	
}

class NoViewPanelStartedException extends UiException {
	
}

class ViewPanelNerverEndedException extends UiException {
	
}


/**
 * hack to provide autocompletion in views
 */
return;

/**
 * @var \n2n\web\ui\view\View $view
 */
$view /*= new \n2n\web\ui\view\View()*/;
/**
 * @var \n2n\web\http\HttpContext $httpContext
 */
$httpContext = new \n2n\web\http\HttpContext();
/**
 * @var \n2n\web\http\VarsRequest $request
 */
$request = new \n2n\web\http\VarsRequest();
/**
 * @var \n2n\web\http\Response $response
 */
$response = new \n2n\web\http\Response();
