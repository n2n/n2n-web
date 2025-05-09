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
namespace n2n\web\http\controller\impl;

use n2n\web\http\controller\ControllerContext;
use n2n\web\http\controller\ControllingPlanException;
use n2n\reflection\TypeExpressionResolver;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\IllegalStateException;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\http\controller\ControllingPlan;
use n2n\web\http\payload\Payload;
use n2n\web\http\NoHttpRefererGivenException;
use n2n\web\http\nav\UrlComposer;
use n2n\web\http\payload\impl\Redirect;
use n2n\web\dispatch\DispatchContext;
use n2n\web\dispatch\Dispatchable;
use n2n\web\http\cache\ResponseCacheControl;
use n2n\web\http\HttpCacheControl;
use n2n\web\ui\ViewFactory;
use n2n\util\type\CastUtils;
use n2n\web\http\controller\Controller;
use n2n\web\http\Response;
use n2n\web\http\Request;
use n2n\web\http\HttpContext;
use n2n\web\http\controller\InvokerInfo;
use n2n\web\ui\view\View;
use n2n\web\http\nav\Murl;
use n2n\web\http\BadRequestException;
use n2n\web\http\payload\impl\JsonPayload;
use n2n\web\http\payload\impl\HtmlPayload;
use n2n\web\http\payload\impl\HtmlUiPayload;
use n2n\web\http\payload\impl\FilePayload;
use n2n\util\io\fs\FsPath;
use n2n\web\http\payload\impl\FsPathPayload;
use n2n\web\ui\UiComponent;
use n2n\util\type\ArgUtils;
use n2n\web\http\controller\Interceptor;
use n2n\web\http\controller\InterceptorFactory;
use n2n\web\http\nav\UrlBuilder;
use n2n\util\uri\Linkable;
use n2n\util\uri\UnavailableUrlException;
use n2n\web\http\payload\impl\XmlPayload;
use n2n\web\http\StatusException;
use n2n\core\container\N2nContext;
use n2n\util\io\Downloadable;
use Psr\Http\Message\ResponseInterface;
use n2n\util\magic\MagicTaskExecutionException;
use n2n\util\magic\MagicTask;
use n2n\core\container\Transaction;
use n2n\web\http\controller\ParamPost;
use n2n\web\http\controller\ParamQuery;
use SimpleXMLElement;
use JsonSerializable;
use n2n\web\http\cache\PayloadCacheControl;
use n2n\web\http\cache\PayloadCacheStore;
use n2n\web\http\cache\CachedPayload;
use n2n\core\util\N2nUtil;
use n2n\util\magic\MagicArray;
use n2n\util\magic\TaskResult;
use n2n\util\magic\impl\TaskResults;
use n2n\reflection\UnresolvableTypeExpressionException;
use n2n\web\http\PageNotFoundException;

class ControllingUtils {
	private $relatedTypeName;
	private $typeExpressionResolver;
	private ?InvokerInfo $invokerInfo = null;
	private $viewCacheControl;
	private $httpCacheControl;
	private $responseCacheControl;
	private $transactions = array();
	
	public function __construct(string $relatedTypeName, private ?ControllerContext $controllerContext) {
		$this->relatedTypeName = $relatedTypeName;
		$this->typeExpressionResolver = new TypeExpressionResolver(ReflectionUtils::getNamespace($relatedTypeName),
				$this->getN2nContext()->getModuleManager());
	}

	/**
	 *
	 * @return string
	 */
	public function getModuleNamespace(): string {
		$moduleNamespace = $this->getControllerContext()->getModuleNamespace();
		if ($moduleNamespace !== null) return $moduleNamespace;
	
		return $this->getN2nContext()->getModuleManager()
				->getModuleOfTypeName($this->relatedTypeName, false)->getNamespace();
	}
	
	public function getHttpContext(): HttpContext {
		return $this->getControllerContext()->getControllingPlan()->getHttpContext();
	}
	
	/**
	 * @return \n2n\web\http\Request
	 */
	public function getRequest(): Request {
		return $this->getHttpContext()->getRequest();
	}

	function getParamQuery(string $name): ?ParamQuery {
		$str = $this->getRequest()->getPostQuery()->get($name);
		if ($str !== null) {
			return new ParamQuery($str);
		}

		return null;
	}

	function getParamPost(string $name): ?ParamPost {
		$str = $this->getRequest()->getPostQuery()->get($name);
		if ($str !== null) {
			return new ParamPost($str);
		}

		return null;
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
		return $this->getControllerContext()->getControllingPlan()->getHttpContext()->getN2nContext();
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
	 * @return \n2n\util\uri\Path
	 */
	public function getControllerPath() {
		return $this->getHttpContext()->getControllerContextPath($this->getControllerContext());
	}
	
	/**
	 * @return ControllingPlan
	 */
	public function getControllingPlan(): ControllingPlan {
		return $this->getControllerContext()->getControllingPlan();
	}
	
	/**
	 * 
	 */
	public function reset(bool $commit) {
		while (null !== ($transaction = array_pop($this->transactions))) {
			if ($commit) {
				$transaction->commit();
			} else {
				$transaction->rollback();
			}
		}
	
		$this->controllerContext = null;
		$this->invokerInfo = null;
		$this->resetCacheControl();
	}
	
	/**
	 * 
	 */
	public function resetCacheControl(): void {
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
	public function commit(): void {
		$this->peakTransaction()->commit();
	}
	
	/**
	 * 
	 */
	public function rollBack(): void {
		$this->peakTransaction()->rollBack();
	}

	/**
	 *
	 * @param \DateInterval|null $cacheInterval
	 * @param array $characteristics
	 */
	public function assignViewCacheControl(?\DateInterval $cacheInterval = null, array $characteristics = array()): void {
		$this->viewCacheControl = new ViewCacheControl($cacheInterval, $characteristics);
	}

	function resetViewCacheControl(): void {
		$this->viewCacheControl = null;
	}

	function assignPayloadCacheControl(?\DateInterval $cacheInterval = null, array $characteristics = array(),
			bool $shared = true): void {
		$this->payloadCacheControl = new PayloadCacheControl($cacheInterval, $characteristics, $shared);
	}

	function resetPayloadCacheControl(): void {
		$this->payloadCacheControl = null;
	}

	/**
	 *
	 * @param \DateInterval|null $maxAge
	 * @param array|null $directives
	 */
	public function assignHttpCacheControl(?\DateInterval $maxAge = null, ?array $directives = null): void {
		$this->httpCacheControl = new HttpCacheControl($maxAge, $directives);
	}
	
	public function resetHttpCacheControl(): void {
		$this->httpCacheControl = null;
	}

	/**
	 * @param \DateInterval|null $cacheInterval
	 * @param bool $includeQuery
	 * @param array $characteristics
	 */
	public function assignResponseCacheControl(?\DateInterval $cacheInterval = null,
			bool $includeQuery = false, array $characteristics = array(), bool $shared = true,
			?string $verifierCheckLookupId = null): void {
		$queryParamNames = null;
		if ($includeQuery) {
			$queryParamNames = array_keys($this->getInvokerInfo()->getQueryParams());
		}

		$this->responseCacheControl = new ResponseCacheControl($cacheInterval, $queryParamNames, $characteristics,
				$shared, $verifierCheckLookupId);
	}
	
	public function resetResponseCacheControl(): void {
		$this->responseCacheControl = null;
	}

	private function isViewCachingEnabled(): bool {
		return $this->getControllerContext()->isViewCachingEnabled();
	}

	/**
	 *
	 * @param string $viewNameExpression
	 * @param string|null $moduleNamespace
	 * @return View|null
	 */
	public function createViewFromCache(string $viewNameExpression, ?string $moduleNamespace = null): ?View {
		if ($this->viewCacheControl === null || !$this->isViewCachingEnabled()) {
			return null;
		}
	
		$viewFactory = $this->getN2nContext()->lookup(ViewFactory::class);
		CastUtils::assertTrue($viewFactory instanceof ViewFactory);
	
		$viewName = $this->typeExpressionResolver->resolve($viewNameExpression);
		return $viewFactory->createFromCache($viewName, $this->viewCacheControl, $moduleNamespace);
	}

	/**
	 *
	 * @param string $viewNameExpression
	 * @param mixed $params
	 * @param string|null $moduleNamespace
	 * @return View
	 * @throws UnresolvableTypeExpressionException
	 */
	public function createView(string $viewNameExpression, ?array $params = null, ?string $moduleNamespace = null): View {
		$viewName = $this->typeExpressionResolver->resolve($viewNameExpression);
	
		$viewFactory = $this->getN2nContext()->lookup(ViewFactory::class);
		CastUtils::assertTrue($viewFactory instanceof ViewFactory);
	
		$view = $viewFactory->create($viewName, $params, $moduleNamespace);
		$view->setControllerContext($this->getControllerContext());
	
		if (null !== $this->viewCacheControl && $this->isViewCachingEnabled()) {
			$viewFactory->cache($view, $this->viewCacheControl);
		}
	
		return $view;
	}
	/**
	 *
	 * @param string $viewNameExpression
	 * @param ViewCacheControl $viewCacheControl
	 */
	public function forwardCache(string $viewNameExpression, ?ViewCacheControl $viewCacheControl = null) {
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
	public function forward(string $viewNameExpression, ?array $params = null,
			?ViewCacheControl $viewCacheControl = null) {
		$this->forwardView($this->createView($viewNameExpression, $params, $viewCacheControl));
	}
	
	public function forwardView(View $view) {
		$this->assignCacheControls();
		$this->getResponse()->send($view);
	}
	
	/**
	 * @return DispatchContext
	 */
	private function getDispatchContext() {
		return $this->getN2nContext()->lookup(DispatchContext::class);
	}
	
	/**
	 * @param Dispatchable $dispatchable
	 * @param string|null $methodName
	 * @return boolean
	 */
	public function hasDispatch(?Dispatchable $dispatchable = null, $methodName = null) {
		$dc = $this->getDispatchContext();
		
		return $dc->hasDispatchJob() && ($dispatchable === null 
				|| $dc->getDispatchJob()->matches($dispatchable, $methodName));
	}
	
	/**
	 * @param Dispatchable $dispatchable
	 * @param string|null $methodName
	 * @throws BadRequestException
	 * @return mixed
	 */
	public function dispatch(Dispatchable $dispatchable, $methodName = null) {
		try {
			return $this->getDispatchContext()->dispatch($dispatchable, $methodName,
					$this->getN2nContext());
		} catch (\n2n\web\dispatch\map\CorruptedDispatchException $e) {
			throw new BadRequestException(null, 0, $e);
		}
	}
	
	/**
	 * @param int|null $httpStatus
	 */
	public function refresh(?int $httpStatus = null) {
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
	
	/**
	 * @param string|UrlComposer|Linkable $murl will be the first arg in the call of {@see self::buildUrl()}.
	 * @param int $httpStatus
	 */
	public function redirect($murl, ?int $httpStatus = null) {
		$this->assignCacheControls();
	
		$url = $this->buildUrl($murl);
	
		$this->getResponse()->send(new Redirect($url, $httpStatus));
	}
	
	/**
	 * @param string|UrlComposer|Linkable $murl will be the first arg in the call of {@see UrlBuilder::buildUrl()}.
	 * @param bool $required
	 * @param string $suggestedLabel
	 * @throws UnavailableUrlException
	 * @return \n2n\util\uri\Url|NULL
	 */
	public function buildUrl($murl, bool $required = true, ?string &$suggestedLabel = null) {
		try {
			return UrlBuilder::buildUrl($murl, $this->getN2nContext(), $this->getControllerContext(), $suggestedLabel);
		} catch (UnavailableUrlException $e) {
			if ($required) throw $e;
			return null;
		}
	}
	
	public function getUrlToContext($pathExt = null, ?array $queries = null, ?int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		return $this->getHttpContext()->buildContextUrl($ssl, $subsystem)->extR($pathExt, $queries, $fragment);
	}
	
	public function redirectToContext($pathExt = null, ?array $queries = null, ?int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect(
				$this->getUrlToContext($pathExt, $queries, $httpStatus,	$fragment, $ssl, $subsystem),
				$httpStatus));
	}
	
	public function getUrlToController($pathExt = null, ?array $queries = null, $controllerContext = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		if (isset($controllerContext)) {
			if (!($controllerContext instanceof ControllerContext)) {
				$controllerContext = $this->getControllingPlan()->getMainControllerContextByKey((string) $controllerContext);
			}
		} else {
			$controllerContext = $this->getControllerContext();
		}

		return Murl::controller($controllerContext)->pathExt($pathExt)
				->queryExt($queries)->fragment($fragment)->ssl($ssl)->subsystem($subsystem)
				->toUrl($this->getN2nContext());
	}
	
	public function redirectToController($pathExt = null, ?array $queries = null, ?int $httpStatus = null,
			$controllerContext = null, $fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect(
				$this->getUrlToController($pathExt, $queries, $controllerContext, $fragment, $ssl, $subsystem),
				$httpStatus));
	}
	

	public function getUrlToPath($pathExt = null, ?array $queries = null, ?string $fragment = null, ?bool $ssl = null, 
			$subsystem = null) {
		return $this->getHttpContext()->buildContextUrl($ssl, $subsystem)->extR($this->getRequest()->getCmdPath())
				->extR($pathExt, $queries, $fragment);
	}
	
	public function redirectToPath($pathExt = null, ?array $queries = null, ?int $httpStatus = null,
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->assignCacheControls();
		$this->getResponse()->send(new Redirect($this->getUrlToPath($pathExt, $queries, $fragment, $ssl, $subsystem), 
				$httpStatus));
	}
	/**
	 * @param string $httpStatus
	 * @throws NoHttpRefererGivenException
	 */
	public function redirectToReferer(?int $httpStatus = null) {
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
	
	public function setInvokerInfo(?InvokerInfo $invokerInfo = null) {
		$this->invokerInfo = $invokerInfo;
	}
	
	/**
	 * @param Controller $controller
	 * @param int $pathPartsToShift
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function createDelegateContext(?Controller $controller = null, ?int $numPathPartsToShift = null) {
		$controllerContext = $this->getControllerContext();
	
		if ($numPathPartsToShift === null) {
			$numPathPartsToShift = $this->getInvokerInfo()->getNumSinglePathParts();
		}
		
		$cmdPath = $controllerContext->getCmdPath();
		$cmdContextPath = $controllerContext->getCmdContextPath();
		
		if ($numPathPartsToShift < 0) {
			$newCmdPath = $cmdContextPath->sub($numPathPartsToShift)->ext($cmdPath);
			$newCmdContextPath = $cmdContextPath->reduced(abs($numPathPartsToShift));
			
			return new ControllerContext($newCmdPath, $newCmdContextPath, $controller);
		}
	
		return new ControllerContext($cmdPath->sub($numPathPartsToShift),
				$cmdContextPath->ext($cmdPath->sub(0, $numPathPartsToShift)), $controller);
	}

	/**
	 * @throws PageNotFoundException
	 */
	public function delegate(Controller $controller, ?int $numPathPartsToShift = null, $execute = true, bool $try = false): ?bool {
		return $this->delegateToControllerContext($this->createDelegateContext($controller, $numPathPartsToShift), $execute, 
				$try);
	}

	/**
	 * @throws PageNotFoundException
	 */
	public function delegateToControllerContext(ControllerContext $nextControllerContext, bool $execute = true,
			bool $try = false): ?bool {
		$plan = $this->getControllerContext()->getControllingPlan();
	
		if ($plan->getStatus() == ControllingPlan::STATUS_FILTER) {
			$plan->addFilter($nextControllerContext, $execute);
			if ($execute) return $plan->executeNextFilter();
		} else {
			$plan->addMain($nextControllerContext, $execute);
			if ($execute) return $plan->executeNextMain($try);
		}
		
		return null;
	}


	/**
	 * @param array|JsonSerializable $data
	 * @param bool $includeBuffer
	 */
	public function sendJson($data, bool $includeBuffer = true): void {
		$this->send(new JsonPayload($data), $includeBuffer);
	}
	
	/**
	 * @param SimpleXMLElement|string $data
	 * @param bool $includeBuffer
	 */
	public function sendXml($data, bool $includeBuffer = true): void {
		$this->send(new XmlPayload($data), $includeBuffer);
	}
	
	/**
	 * @param string $htmlStr
	 * @param bool $includeBuffer
	 */
	public function sendHtml(string $htmlStr, bool $includeBuffer = true): void {
		$this->send(new HtmlPayload($htmlStr), $includeBuffer);
	}
	
	/**
	 * @param string|UiComponent $uiComponent
	 * @param bool $includeBuffer
	 */
	public function sendHtmlUi($uiComponent, bool $includeBuffer = true): void {
		$this->send(new HtmlUiPayload($uiComponent), $includeBuffer);
	}
	
	/**
	 * @param Downloadable $file
	 * @param bool $includeBuffer
	 */
	public function sendFile(Downloadable $file, bool $includeBuffer = true): void {
		$this->send(new FilePayload($file), $includeBuffer);
	}

	/**
	 * @param Downloadable $file
	 * @param string|null $name
	 * @param bool $includeBuffer
	 */
	public function sendFileAttachment(Downloadable $file, ?string $name = null, bool $includeBuffer = true): void {
		$this->send(new FilePayload($file, true, $name), $includeBuffer);
	}
	
	/**
	 * @param FsPath|string $fsPath
	 * @param bool $includeBuffer
	 */
	public function sendFsPath($fsPath, bool $includeBuffer = true): void {
		$this->send(new FsPathPayload(FsPath::create($fsPath)), $includeBuffer);
	}

	/**
	 * @param FsPath|string $fsPath
	 * @param string|null $name
	 * @param bool $includeBuffer
	 */
	public function sendFsPathAttachment($fsPath, ?string $name = null, bool $includeBuffer = true): void {
		$this->send(new FsPathPayload(FsPath::create($fsPath), true, $name), $includeBuffer);
	}

	private ?PayloadCacheControl $payloadCacheControl = null;

	private function createPayloadSrcName(): string {
		$method = $this->getInvokerInfo()->getInvoker()->getMethod();
		return $this->relatedTypeName . '::' . $method->getName();
	}

	public function send(Payload|ResponseInterface $responseThing, bool $includeBuffer = true): void {
		$this->assignCacheControls();
		$this->getResponse()->send($responseThing, $includeBuffer);

		$this->storePayloadCache();
	}

	private function isPayloadCachingEnabled(): bool {
		return $this->getControllerContext()->isPayloadCachingEnabled();
	}

	private function storePayloadCache(): void {
		if ($this->payloadCacheControl === null || !$this->isPayloadCachingEnabled()) {
			return;
		}

		$expireDate = new \DateTime();
		$expireDate->add($this->payloadCacheControl->getCacheInterval());
		$cachedPayload = CachedPayload::createFromSentPayload($this->getResponse(), $expireDate);

		$this->getN2nContext()->lookup(PayloadCacheStore::class)
				->store($this->createPayloadSrcName(), $this->payloadCacheControl->getCharacteristics(),
						$cachedPayload, $this->payloadCacheControl->isShared());
	}

	public function createPayloadFromCache(): ?Payload {
		if ($this->payloadCacheControl === null || !$this->isPayloadCachingEnabled()) {
			return null;
		}

		return $this->getN2nContext()->lookup(PayloadCacheStore::class)
				->get($this->createPayloadSrcName(), $this->payloadCacheControl->getCharacteristics(),
						$this->payloadCacheControl->isShared());
	}

	public function sendCache(): bool {
		$payload = $this->createPayloadFromCache();
		if ($payload === null) {
			return false;
		}

		$this->getResponse()->send($payload);
		return true;
	}

	public function accepted(string ...$mimeTypes) {
		return $this->getRequest()->getAcceptRange()->bestMatch($mimeTypes);
	}
	
	public function acceptQuality(string $mimeType): float|int {
		return $this->getRequest()->getAcceptRange()->matchQuality($mimeType);
	}
	
	private ?InterceptorFactory $interceptorFactory = null;

	/**
	 * @return InterceptorFactory|null
	 */
	private function getInterceptorFactory(): ?InterceptorFactory {
		if ($this->interceptorFactory === null) {
			$this->interceptorFactory = new InterceptorFactory($this->getN2nContext());
		}
		
		return $this->interceptorFactory;
	}
	
	/**
	 * @param Interceptor|string ...$interceptors
	 */
	public function intercept(...$interceptors): bool {
		ArgUtils::valArray($interceptors, ['string', Interceptor::class]);
		
		foreach ($interceptors as $interceptor) {
			if (!($interceptor instanceof Interceptor)) {
				$interceptor = $this->getInterceptorFactory()->createByLookupId($interceptor);
			}
			
			if (!$interceptor->invoke($this)) return false;
		}
		
		return true;
	}
	
	/**
	 * Executes a {@see ValidationJob} and automatically converts {@see ValidationException}s to {@see StatusException}s
	 *
	 * @param MagicTask $validationJob
	 * @param int $rejectStatus
	 * @return ValResult
	 * @throws StatusException
	 */
	function val(MagicTask $validationJob, int $rejectStatus = Response::STATUS_400_BAD_REQUEST): ValResult {
		try {
			return new ValResult($validationJob->exec($this->getN2nContext()), $this);
		} catch (MagicTaskExecutionException $e) {
			throw new StatusException($rejectStatus, $e->getMessage(), null, $e);
		}
	}

	/**
	 * Executes a {@see MagicTask} and automatically converts {@see MagicTaskExecutionException}s to
	 * {@see StatusException}s if parameter $rejectStatus is not null.
	 *
	 *
	 * @template T
	 * @param MagicTask<T> $magicTask
	 * @param mixed|null $input
	 * @param int|null $rejectStatus
	 * @return ExecResult<T>
	 * @throws StatusException
	 */
	function exec(MagicTask $magicTask, mixed $input = null, ?int $rejectStatus = Response::STATUS_400_BAD_REQUEST): ExecResult {
		try {
			return new ExecResult($magicTask->exec($this->getN2nContext(), $input), $this);
		} catch (MagicTaskExecutionException $e) {
			if ($rejectStatus !== null) {
				throw new StatusException($rejectStatus, $e->getMessage(), null, $e);
			}

			throw new IllegalStateException('MagicTask execution failed. Reason: ' . $e->getMessage(),
					previous: $e);
		}
	}

	function sendErrJson(MagicArray $errorMap): void {
		(new ExecResult(TaskResults::invalid($errorMap), $this))->sendErrJson();
	}

	function execIsolated(\Closure $closure, int $tries = 3, ?\Closure $deadlockHandler = null,
			bool $readOnly = false): mixed {
		return (new N2nUtil($this->getN2nContext()))->container()
				->execIsolated($closure, $tries, $deadlockHandler, $readOnly);
	}

}
