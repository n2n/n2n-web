<?php

namespace n2n\web\http\controller;

use n2n\web\http\StatusException;
use n2n\web\ui\ViewFactory;
use n2n\core\err\ThrowableModel;
use n2n\web\http\HttpContext;
use n2n\web\http\Response;
use n2n\core\N2N;

class SpecialViewRenderer {

	function __construct(private HttpContext $httpContext) {
	}

	function sendStatusView(StatusException $e, bool $flush):void {
		$view = $this->httpContext->getN2nContext()->lookup(ViewFactory::class)
				->create($this->httpContext->determineErrorStatusViewName($e->getStatus()),
						['throwableModel' => new ThrowableModel($e)]);
		$response = $this->httpContext->getResponse();
		$e->prepareResponse($response);
		$response->send($view);

		if ($flush) {
			$response->flush();
		}
	}

	function sendExceptionView(\Throwable $t, bool $flush): void {
		$response =  $this->httpContext->getResponse();

		$throwableModel = new ThrowableModel($t);
		$pendingOutputs = [];
		if ($response->isBuffering()) {
			$pendingOutputs[] = $response->fetchBufferedOutput(true);
		}

		$throwableModel->setOutputCallback(function () use ($pendingOutputs) {
			return implode('', $pendingOutputs);
		});

		$status = Response::STATUS_500_INTERNAL_SERVER_ERROR;
		$viewName = $this->httpContext->determineErrorStatusViewName($status);

		$view = $this->httpContext->getN2nContext()->lookup(ViewFactory::class)
				->create($viewName, array('throwableModel' => $throwableModel));
		$request = $this->httpContext->getRequest();
		$view->setControllerContext(new ControllerContext($request->getCmdPath(), $request->getCmdContextPath()));

		$response->reset();
		$response->setStatus($status);
		$response->send($view);

		if ($flush) {
			$response->flush();
		}
	}

}