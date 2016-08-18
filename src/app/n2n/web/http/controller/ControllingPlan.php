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

use n2n\l10n\N2nLocale;
use n2n\core\container\N2nContext;
use n2n\web\http\PageNotFoundException;
use n2n\web\http\StatusException;
use n2n\web\http\UnknownControllerContextException;

class ControllingPlan {
	const STATUS_READY = 'ready';
	const STATUS_FILTER = 'filter';
	const STATUS_MAIN = 'main';
	const STATUS_EXECUTED = 'executed';
	const STATUS_ABORTED = 'aborted';
			
	private $n2nContext;
	private $status = self::STATUS_READY;
	private $n2nLocale;
	private $filterControllerContexts = array();
	private $currentFilterIndex = -1;
	private $mainControllerContexts = array();
	private $currentMainIndex = -1; 

	public function __construct(N2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
	}
	
	public function getN2nContext() {
		return $this->n2nContext;
	}
	
	public function getStatus() {
	 	return $this->status;
	}
	
	public function getN2nLocale() {
		return $this->n2nLocale;
	}
	
	public function setN2nLocale(N2nLocale $n2nLocale = null) {
		$this->n2nLocale = $n2nLocale;
	}
	
	public function addFilter(ControllerContext $filterControllerContext, $afterCurrent = false) {
		$filterControllerContext->setControllingPlan($this);
		
		if ($this->currentFilterIndex < 0 || !$afterCurrent) {
			$this->filterControllerContexts[] = $filterControllerContext;
			return;
		}
		
		$this->insertControllerContext($this->filterControllerContexts, $this->currentFilterIndex, 
				$filterControllerContext);
	}
	
	public function addMain(ControllerContext $mainControllerContext, $afterCurrent = false) {
		$mainControllerContext->setControllingPlan($this);
		
		if ($this->currentMainIndex < 0 || !$afterCurrent) {
			$this->mainControllerContexts[] = $mainControllerContext;
			return;
		}
		
		$this->insertControllerContext($this->mainControllerContexts, $this->currentMainIndex,
				$mainControllerContext);
	}
	
	private function insertControllerContext(&$arr, $currentIndex, ControllerContext $controllerContext) {
		$num = count($arr);
		for ($i = $currentIndex + 1; $i < $num; $i++) {
			$cc = $arr[$i];
			$arr[$i] = $controllerContext;
			$controllerContext = $cc;
		}
		
		$arr[] = $controllerContext;
	}
	
	private function nextFilter() {
		$this->currentFilterIndex++;
		
		if (isset($this->filterControllerContexts[$this->currentFilterIndex])) {
			return $this->filterControllerContexts[$this->currentFilterIndex];
		}
		
		$this->currentFilterIndex--;
		return null;
	}
	
	private function nextMain() {
		$this->currentMainIndex++;
		
		if (isset($this->mainControllerContexts[$this->currentMainIndex])) {
			return $this->mainControllerContexts[$this->currentMainIndex];
		}

		$this->currentMainIndex--;
		return null;
	}
	
	public function execute() {
		if ($this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('ControllingPlan already executed.');
		}
		
		if ($this->n2nLocale !== null) {
			$this->n2nContext->getHttpContext()->getRequest()->setN2nLocale($this->n2nLocale);
		}
		
		$this->status = self::STATUS_FILTER;
		while ($this->status == self::STATUS_FILTER 
				&& null !== ($nextFilter = $this->nextFilter())) {
			$nextFilter->execute();
		}

		if ($this->status != self::STATUS_FILTER) return;
		
		$this->status = self::STATUS_MAIN;
		while ($this->status == self::STATUS_MAIN && null !== ($nextMain = $this->nextMain())) {
			try {
				if (!$nextMain->execute()) {
					throw new PageNotFoundException('No matching method found in Controller ' 
							. get_class($nextMain->getController()));
				}
			} catch (StatusException $e) {
				$this->status = self::STATUS_ABORTED;
				throw $e;
			}
		}
		
		$this->status = self::STATUS_EXECUTED;
		
		if (empty($this->mainControllerContexts)) {
			throw new PageNotFoundException();
		}
	}
	
	public function executeNextMain(bool $try = false) {
		if ($this->status !== self::STATUS_MAIN) {
			throw new ControllingPlanException('ControllingPlan is not executing main controllers.');
		}
		
		$nextMain = $this->nextMain();
		if (null === $nextMain) {
			throw new ControllingPlanException('No main controller to execute.');
		}
		
		if ($nextMain->execute()) return true;
		
		if ($try) return false;
		
		throw new PageNotFoundException();
	}
	
	public function isExecuting() {
		return $this->status == self::STATUS_FILTER || $this->status == self::STATUS_MAIN;
	} 
	
	public function hasCurrentFilter() {
		return $this->status == self::STATUS_FILTER 
				&& $this->filterControllerContexts[$this->currentFilterIndex];
	}
	
	public function getCurrentFilter() {
		if ($this->hasCurrentFilter()) {
			return $this->filterControllerContexts[$this->currentFilterIndex];
		}
		
		throw new ControllingPlanException('No filter controller active.');
	}

	public function hasCurrentMain() {
		return $this->status == self::STATUS_MAIN
				&& $this->mainControllerContexts[$this->currentMainIndex];
	}
	
	public function getCurrentMain() {
		if ($this->hasCurrentMain()) {
			return $this->mainControllerContexts[$this->currentMainIndex];
		}
		
		throw new ControllingPlanException('No main controller active.');
	}
	
	public function getByName(string $name) {
		if (null !== ($controllerContext = $this->findByName($this->mainControllerContexts, $name))) {
			return $controllerContext;
		}
		
		if (null !== ($controllerContext = $this->findByName($this->filterControllerContexts, $name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown ControllerContext name: ' . $name);
	}
	
	private function findByName(array &$controllerContexts, $name) {
		for ($i = count($controllerContexts) - 1; $i >= 0; $i--) {
			$ccName = $controllerContexts[$i]->getName();
				
			if ($ccName !== null && $ccName == $name) {
				return $controllerContexts[$i];
			}
				
			if (get_class($controllerContexts[$i]->getController()) == $name) {
				return $controllerContexts[$i];
			}
		}
	}
	
	public function getFilterByName($name) {
		if (null !== ($controllerContext = $this->findByName($this->filterControllerContexts, $name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown filter ControllerContext name: ' . $name);
	}
	
	public function getMainByName($name) {
		if (null !== ($controllerContext = $this->findByName($this->mainControllerContexts, $name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown main ControllerContext name: ' . $name);
	}
	
	public function skipFilter() {
		$this->currentFilterIndex = count($this->filterControllerContexts) - 1;
	}
	
	public function skipMain() {
		$this->currentMainIndex = count($this->mainControllerContexts) - 1;
	}
	
	public function abort() {
		$this->status = self::STATUS_ABORTED;
	}
	
	public function getMainControllerContextByKey($key) {
		foreach ($this->mainControllerContexts as $mainCc) {
			if ($mainCc->getName() == $key) {
				return $mainCc;
			}
		}
	}
}
