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
namespace n2n\web\http\controller;

use n2n\web\http\StatusException;
use n2n\web\http\Response;
use n2n\reflection\ObjectAdapter;
use n2n\web\http\Request;
use n2n\web\http\controller\impl\ControllingUtilsTrait;
use n2n\model\Lookupable;

abstract class ControllerAdapter extends ObjectAdapter implements Controller, Lookupable {
	use ControllingUtilsTrait;
	
	
	/* (non-PHPdoc)
	 * @see \n2n\web\http\controller\Controller::execute()
	 */
	public final function execute(ControllerContext $controllerContext): bool {
		$this->init($controllerContext);
		
		$request = $this->getRequest();
		$invokerFactory = new ActionInvokerFactory(
				$controllerContext->getCmdPath(), $controllerContext->getCmdContextPath(), 
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				$this->getN2nContext());
		$invokerFactory->setConstantValues($controllerContext->getParams());
		$interpreter = new ControllerInterpreter(new \ReflectionClass($this), $invokerFactory);
		
		$this->resetCacheControl();
		
		$catchedStatusException = null;

		try {
			$prepareInvokers = $interpreter->interpret(ControllerInterpreter::DETECT_PREPARE_METHOD);
			foreach ($prepareInvokers as $prepareInvoker) {
				$this->cu()->setInvokerInfo($prepareInvoker);
				$prepareInvoker->getInvoker()->invoke($this);
			}
		
			$invokerInfos = $interpreter->interpret(ControllerInterpreter::DETECT_ALL & ~ ControllerInterpreter::DETECT_PREPARE_METHOD);
			if (!empty($invokerInfos)) {
				foreach ($invokerInfos as $invokerInfo) {
					$this->cu()->setInvokerInfo($invokerInfo);
					$invokerInfo->getInvoker()->invoke($this);
				}
				return true;
			}
		} catch (StatusException $e) {
			$catchedStatusException = $e;
		}
		
		if (empty($invokerInfos) || ($catchedStatusException !== null 
				&& $catchedStatusException->getStatus() == Response::STATUS_404_NOT_FOUND)) {
			try {
				$notFoundInvokers = $interpreter->interpret(ControllerInterpreter::DETECT_NOT_FOUND_METHOD);
				
				if (!empty($notFoundInvokers)) {
					foreach ($notFoundInvokers as $invokerInfo) {
						$this->cu()->setInvokerInfo($invokerInfo);
						$invokerInfo->getInvoker()->invoke($this);
						$catchedStatusException = null;
					}
					return true;
				}
			} catch (StatusException $e) {
				$catchedStatusException = $e;
			}
		}
		
		$this->cu()->reset();
		
		if ($catchedStatusException !== null) {
			throw $catchedStatusException;
		}
		
		return false;
	}
}

class NoHttpRefererGivenException extends \Exception {
	
}
