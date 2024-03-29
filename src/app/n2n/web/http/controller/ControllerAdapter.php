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
namespace n2n\web\http\controller;

use n2n\web\http\StatusException;
use n2n\web\http\Response;
use n2n\reflection\ObjectAdapter;
use n2n\web\http\controller\impl\ControllingUtilsTrait;
use n2n\context\Lookupable;
use n2n\util\io\ob\OutputBuffer;
use n2n\reflection\ReflectionUtils;

abstract class ControllerAdapter extends ObjectAdapter implements Controller, Lookupable {
	use ControllingUtilsTrait;

	/**
	 * @return OutputBuffer
	 * @throws \n2n\util\io\ob\OutputBufferDisturbedException
	 */
	private function startBuffer(ControllerContext $controllerContext) {
		$this->init($controllerContext);

		$outputBuffer = $this->getResponse()->createOutputBuffer();
		$outputBuffer->start();
		return $outputBuffer;
	}

	/**
	 * @param OutputBuffer $outputBuffer
	 */
	private function endBuffer(OutputBuffer $outputBuffer, bool $commit) {
		$outputBuffer->end();
		$outputBuffer->seal();
		$this->getResponse()->addContent($outputBuffer->getBufferedContents());

		$this->cu()->reset($commit);
	}

	/* (non-PHPdoc)
	 * @see \n2n\web\http\controller\Controller::execute()
	 */
	public final function execute(ControllerContext $controllerContext): bool {
		$outputBuffer = $this->startBuffer($controllerContext);

		$request = $this->getRequest();
		$invokerFactory = new ActionInvokerFactory(
				$controllerContext->getCmdPath(), $controllerContext->getCmdContextPath(), $request,
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				$request->getAcceptRange(), $this->getN2nContext());
		$invokerFactory->setConstantValues($controllerContext->getParams());
		$interpreter = new ControllerInterpreter(new \ReflectionClass($this), $invokerFactory,
				new InterceptorFactory($controllerContext->getControllingPlan()->getHttpContext()->getN2nContext()));
		
		$this->resetCacheControl();

		$caughtStatusException = null;
		try {
			if (!$this->intercept(...$interpreter->findControllerInterceptors())) {
				$this->endBuffer($outputBuffer, true);
				return true;
			}

			$prepareInvokers = $interpreter->interpret(ControllerInterpreter::DETECT_PREPARE_METHOD);
			foreach ($prepareInvokers as $prepareInvoker) {
				$this->cu()->setInvokerInfo($prepareInvoker);
				if ($this->intercept(...$prepareInvoker->getInterceptors())) {
					$prepareInvoker->getInvoker()->invoke($this);
				} else {
					$this->endBuffer($outputBuffer, true);
					return true;
				}
			}

			$invokerInfos = $interpreter->interpret(ControllerInterpreter::DETECT_ALL & ~ ControllerInterpreter::DETECT_PREPARE_METHOD);
			if (!empty($invokerInfos)) {
				foreach ($invokerInfos as $invokerInfo) {
					$this->cu()->setInvokerInfo($invokerInfo);
					
					if (!$this->intercept(...$invokerInfo->getInterceptors())) continue;

					$invokerInfo->getInvoker()->invoke($this);
				}

				$this->endBuffer($outputBuffer, true);
				return true;
			}
		} catch (StatusException $e) {
			$caughtStatusException = $e;
		}
		
		if (empty($invokerInfos) || ($caughtStatusException !== null
				&& $caughtStatusException->getStatus() == Response::STATUS_404_NOT_FOUND)) {
			try {
				$notFoundInvokers = $interpreter->interpret(ControllerInterpreter::DETECT_NOT_FOUND_METHOD);
				
				if (!empty($notFoundInvokers)) {
					foreach ($notFoundInvokers as $invokerInfo) {
						$this->cu()->setInvokerInfo($invokerInfo);
						
						if (!$this->intercept(...$invokerInfo->getInterceptors())) continue;
						
						$invokerInfo->getInvoker()->invoke($this);
						$caughtStatusException = null;
					}

					$this->endBuffer($outputBuffer, true);
					return true;
				}
			} catch (StatusException $e) {
				$caughtStatusException = $e;
			}
		}

		if ($caughtStatusException !== null) {
			$this->endBuffer($outputBuffer, true);
			throw $caughtStatusException;
		}

		$this->endBuffer($outputBuffer, true);
		return false;
	}
}