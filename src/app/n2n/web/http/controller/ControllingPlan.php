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
	private $nextFilterIndex = 0;
	private $currentFilterControllerContext = null;
	private $mainControllerContexts = array();
	private $nextMainIndex = 0; 
	private $currentMainControllerContext = null;
	
	private $onMainStartClosures = [];

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
		
		if (!$afterCurrent || $this->status !== self::STATUS_FILTER 
				|| !isset($this->filterControllerContexts[$this->nextFilterIndex])) {
			$this->filterControllerContexts[] = $filterControllerContext;
			return;
		}
		
		$this->insertControllerContext($this->filterControllerContexts, $this->nextFilterIndex, 
				$filterControllerContext);
	}
	
	public function addMain(ControllerContext $mainControllerContext, $afterCurrent = false) {
		$mainControllerContext->setControllingPlan($this);
		
		if (!$afterCurrent || $this->status !== self::STATUS_MAIN
				|| !isset($this->mainControllerContexts[$this->nextMainIndex])) {
			$this->mainControllerContexts[] = $mainControllerContext;
			return;
		}
		
		$this->insertControllerContext($this->mainControllerContexts, $this->nextMainIndex,
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
	
	/**
	 * @return ControllerContext|null
	 */
	private function nextFilter() {
		if (isset($this->filterControllerContexts[$this->nextFilterIndex])) {
			return $this->currentFilterControllerContext = $this->filterControllerContexts[$this->nextFilterIndex++];
		}
		
		$this->currentFilterControllerContext = null;
		return null;
	}
	
	/**
	 * @return ControllerContext|null
	 */
	private function nextMain() {
		if (isset($this->mainControllerContexts[$this->nextMainIndex])) {
			return $this->currentMainControllerContext = $this->mainControllerContexts[$this->nextMainIndex++];
		}

		$this->currentMainControllerContext = null;
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
		while ($this->status == self::STATUS_FILTER && null !== ($nextFilter = $this->nextFilter())) {
			$nextFilter->execute();
		}

		if ($this->status != self::STATUS_FILTER && $this->status != self::STATUS_MAIN) {
			return;
		}
		
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
	
	private function ensureFilterable() {
		if ($this->status !== self::STATUS_FILTER && $this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('ControllingPlan is not executing filter controllers.');
		}
	}
	
	public function executeNextFilter(bool $try = false) {
		$this->ensureFilterable();
		
		$nextFilter = $this->nextFilter();
		if (null === $nextFilter) {
			throw new ControllingPlanException('No filter controller to execute.');
		}
		
		if ($nextFilter->execute()) return true;
		
		if ($try) return false;
		
		throw new PageNotFoundException();
	}
	
	/**
	 * @return boolean returns false if the ControllingPlan was aborted by a following filter
	 */
	public function executeToMain() {
		$this->ensureFilterable();
		
		while (null !== ($nextFilter = $this->nextFilter())) {
			$nextFilter->execute();
		}
		
		if ($this->status !== self::STATUS_FILTER) {
			return false;
		}
		
		$this->status = self::STATUS_MAIN;
		return true;
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
		return $this->status == self::STATUS_FILTER && $this->currentFilterControllerContext !== null;
	}
	
	public function getCurrentFilter() {
		if ($this->hasCurrentFilter()) {
			return $this->currentFilterControllerContext;
		}
		
		throw new ControllingPlanException('No filter controller active.');
	}

	public function hasCurrentMain() {
		return $this->status == self::STATUS_MAIN && $this->currentMainControllerContext !== null;
	}
	
	public function getCurrentMain() {
		if ($this->hasCurrentMain()) {
			return $this->currentMainControllerContext;
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
		$this->nextFilterIndex = count($this->filterControllerContexts);
	}
	
	public function skipMain() {
		$this->nextMainIndex = count($this->mainControllerContexts);
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
