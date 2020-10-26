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
	const STATUS_PRECACHE = 'precache';
	const STATUS_FILTER = 'filter';
	const STATUS_MAIN = 'main';
	const STATUS_EXECUTED = 'executed';
	const STATUS_ABORTED = 'aborted';
			
	private $n2nContext;
	private $status = self::STATUS_READY;
	private $n2nLocale;
	private $precacheQueue;
	private $responseCachePrevented = false;
	private $filterQueue;
	private $mainQueue;
// 	private $precacheFilterControllerContexts = array();
// 	private $nextPrecacheFilterIndex = 0;
// 	private $filterControllerContexts = array();
// 	private $nextFilterIndex = 0;
// 	private $currentFilterControllerContext = null;
// 	private $mainControllerContexts = array();
// 	private $nextMainIndex = 0; 
// 	private $currentMainControllerContext = null;
	
	private $onPostPrecacheClosures = [];
	private $onMainStartClosures = [];

	/**
	 * @param N2nContext $n2nContext
	 */
	function __construct(N2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
		$this->precacheQueue = new ControllerContextQueue();
		$this->filterQueue = new ControllerContextQueue();
		$this->mainQueue = new ControllerContextQueue();
	}
	
	/**
	 * @return \n2n\core\container\N2nContext
	 */
	function getN2nContext() {
		return $this->n2nContext;
	}
	
	/**
	 * @return string
	 */
	function getStatus() {
	 	return $this->status;
	}
	
	/**
	 * @return \n2n\l10n\N2nLocale
	 */
	function getN2nLocale() {
		return $this->n2nLocale;
	}
	
	/**
	 * @param N2nLocale|null $n2nLocale
	 * @return ControllingPlan
	 */
	function setN2nLocale(?N2nLocale $n2nLocale = null) {
		if ($n2nLocale === null && $this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('Can not set null locale on a already executing ControllingPlan.');
		}
		
		$this->n2nLocale = $n2nLocale;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	function isResponseCachePrevented() {
		return $this->responseCachePrevented;
	}
	
	/**
	 * @param bool $preventResponseCache
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function preventResponseCache(bool $preventResponseCache = true) {
		$this->responseCachePrevented = $preventResponseCache;
		return $this;
	}
	
	/**
	 * @param ControllerContext $precacheFilterControllerContext
	 * @param bool $afterCurrent
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function addPrecacheFilter(ControllerContext $precacheFilterControllerContext, bool $afterCurrent = false) {
		$precacheFilterControllerContext->setControllingPlan($this);
		
		if ($this->status !== self::STATUS_PRECACHE) {
			$afterCurrent = false;
		}
		
		$this->precacheQueue->add($precacheFilterControllerContext, $afterCurrent);
		return $this;
	}
	
	/**
	 * @param ControllerContext $filterControllerContext
	 * @param bool $afterCurrent
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function addFilter(ControllerContext $filterControllerContext, bool $afterCurrent = false) {
		$filterControllerContext->setControllingPlan($this);
		
		if ($this->status !== self::STATUS_FILTER) {
			$afterCurrent = false;
		}
		
		$this->filterQueue->add($filterControllerContext, $afterCurrent);
		return $this;
	}
	
	/**
	 * @param ControllerContext $mainControllerContext
	 * @param bool $afterCurrent
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	public function addMain(ControllerContext $mainControllerContext, bool $afterCurrent = false) {
		$mainControllerContext->setControllingPlan($this);
		
		if (!$afterCurrent) {
			$afterCurrent = false;
		}
		
		$this->mainQueue->add($mainControllerContext, $afterCurrent);
		return $this;
	}
	
// 	/**
// 	 * @return ControllerContext|null
// 	 */
// 	private function nextPrecacheFilter() {
// 		if (isset($this->precacheFilterControllerContexts[$this->nextFilterIndex])) {
// 			return $this->currentPrecacheFilterControllerContext = $this->precacheFilterControllerContexts[$this->nextFilterIndex++];
// 		}
		
// 		$this->currentFilterControllerContext = null;
// 		return null;
// 	}
	
// 	/**
// 	 * @return ControllerContext|null
// 	 */
// 	private function nextFilter() {
// 		if (isset($this->filterControllerContexts[$this->nextFilterIndex])) {
// 			return $this->currentFilterControllerContext = $this->filterControllerContexts[$this->nextFilterIndex++];
// 		}
		
// 		$this->currentFilterControllerContext = null;
// 		return null;
// 	}
	
// 	/**
// 	 * @return ControllerContext|null
// 	 */
// 	private function nextMain() {
// 		if (isset($this->mainControllerContexts[$this->nextMainIndex])) {
// 			return $this->currentMainControllerContext = $this->mainControllerContexts[$this->nextMainIndex++];
// 		}

// 		$this->currentMainControllerContext = null;
// 		return null;
// 	}
	
	/**
	 * @throws ControllingPlanException
	 * @throws PageNotFoundException
	 * @throws StatusException
	 */
	public function execute() {
		if ($this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('ControllingPlan already executed.');
		}
				
		if ($this->n2nLocale !== null) {
			$this->n2nContext->getHttpContext()->getRequest()->setN2nLocale($this->n2nLocale);
		}
		
		$this->status = self::STATUS_PRECACHE;
		while ($this->status == self::STATUS_PRECACHE && null !== ($nextPrecache = $this->precacheQueue->next())) {
			$nextPrecache->execute();
		}
		
		// return when aborted
		if ($this->status != self::STATUS_PRECACHE && $this->status != self::STATUS_FILTER) {
			return;
		}
		
		if (!$this->responseCachePrevented && $this->n2nContext->getHttpContext()->getResponse()->sendCachedPayload()) {
			return;
		}
		
		$this->triggerPostPrecache();
		
		$this->status = self::STATUS_FILTER;
		while ($this->status == self::STATUS_FILTER && null !== ($nextFilter = $this->filterQueue->next())) {
			$nextFilter->execute();
		}

		// return when aborted
		if ($this->status != self::STATUS_FILTER && $this->status != self::STATUS_MAIN) {
			return;
		}
		
		$this->status = self::STATUS_MAIN;
		while ($this->status == self::STATUS_MAIN && null !== ($nextMain = $this->mainQueue->next())) {
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
		
		if ($this->mainQueue->isEmpty()) {
			throw new PageNotFoundException();
		}
	}
	
	/**
	 * @throws ControllingPlanException
	 */
	private function ensurePrecorable() {
		if ($this->status !== self::STATUS_PRECACHE && $this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('ControllingPlan is not executing filter controllers.');
		}
	}
	
	/**
	 * @param bool $try
	 * @throws ControllingPlanException
	 * @throws PageNotFoundException
	 * @return boolean
	 */
	function executeNextPrecache(bool $try = false) {
		$this->ensurePrecorable();
		
		$nextPrecache = $this->precacheQueue->next();
		if (null === $nextPrecache) {
			throw new ControllingPlanException('No filter controller to execute.');
		}
		
		if ($nextPrecache->execute()) return true;
		
		if ($try) return false;
		
		throw new PageNotFoundException();
	}
	
	/**
	 * @throws ControllingPlanException
	 */
	private function ensureFilterable() {
		if ($this->status !== self::STATUS_FILTER && $this->status !== self::STATUS_READY) {
			throw new ControllingPlanException('ControllingPlan is not executing filter controllers.');
		}
	}
	
	/**
	 * @param bool $try
	 * @throws ControllingPlanException
	 * @throws PageNotFoundException
	 * @return boolean
	 */
	function executeNextFilter(bool $try = false) {
		$this->ensureFilterable();
		
		$nextFilter = $this->filterQueue->next();
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
	function executeToMain() {
		$this->ensureFilterable();
		
		while (null !== ($nextFilter = $this->filterQueue->next())) {
			$nextFilter->execute();
		}
		
		if ($this->status !== self::STATUS_FILTER) {
			return false;
		}
		
		$this->status = self::STATUS_MAIN;
		return true;
	}
	
	/**
	 * @param bool $try
	 * @throws ControllingPlanException
	 * @throws PageNotFoundException
	 * @return boolean
	 */
	function executeNextMain(bool $try = false) {
		if ($this->status !== self::STATUS_MAIN) {
			throw new ControllingPlanException('ControllingPlan is not executing main controllers.');
		}
		
		$nextMain = $this->mainQueue->next();
		if (null === $nextMain) {
			throw new ControllingPlanException('No main controller to execute.');
		}
		
		if ($nextMain->execute()) return true;
		
		if ($try) return false;
		
		throw new PageNotFoundException();
	}
	
	/**
	 * @return boolean
	 */
	function isExecuting() {
		return $this->status == self::STATUS_PRECACHE || $this->status == self::STATUS_FILTER || $this->status == self::STATUS_MAIN;
	} 
	
	/**
	 * @return boolean
	 */
	function hasCurrentPrecache() {
		return $this->status == self::STATUS_PRECACHE && $this->precacheQueue->getCurrent() !== null;
	}
	
	/**
	 * @throws ControllingPlanException
	 * @return \n2n\web\http\controller\ControllerContext|NULL
	 */
	function getCurrentPrecache() {
		if ($this->hasCurrentFilter()) {
			return $this->precacheQueue->getCurrent();
		}
		
		throw new ControllingPlanException('No precache controller active.');
	}
	
	/**
	 * @return boolean
	 */
	function hasCurrentFilter() {
		return $this->status == self::STATUS_FILTER && $this->filterQueue->getCurrent() !== null;
	}
	
	/**
	 * @throws ControllingPlanException
	 * @return \n2n\web\http\controller\ControllerContext|NULL
	 */
	function getCurrentFilter() {
		if ($this->hasCurrentFilter()) {
			return $this->filterQueue->getCurrent();
		}
		
		throw new ControllingPlanException('No filter controller active.');
	}

	/**
	 * @return boolean
	 */
	function hasCurrentMain() {
		return $this->status == self::STATUS_MAIN && $this->mainQueue->getCurrent() !== null;
	}
	
	/**
	 * @throws ControllingPlanException
	 * @return \n2n\web\http\controller\ControllerContext|NULL
	 */
	function getCurrentMain() {
		if ($this->hasCurrentMain()) {
			return $this->mainQueue->getCurrent();
		}
		
		throw new ControllingPlanException('No main controller active.');
	}
	
	/**
	 * @param string $name
	 * @throws UnknownControllerContextException
	 * @return ControllerContext
	 */
	function getByName(string $name) {
		if (null !== ($controllerContext = $this->precacheQueue->findByName($name))) {
			return $controllerContext;
		}
		
		if (null !== ($controllerContext = $this->filterQueue->findByName($name))) {
			return $controllerContext;
		}
		
		if (null !== ($controllerContext = $this->mainQueue->findByName($name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown ControllerContext name: ' . $name);
	}
	
	/**
	 * @param string $name
	 * @throws UnknownControllerContextException
	 * @return ControllerContext
	 */
	function getPrecacheByName(string $name) {
		if (null !== ($controllerContext = $this->precacheueue->findByName($name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown precache ControllerContext name: ' . $name);
	}
	
	/**
	 * @param string $name
	 * @throws UnknownControllerContextException
	 * @return ControllerContext
	 */
	function getFilterByName(string $name) {
		if (null !== ($controllerContext = $this->filterQueue->findByName($name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown filter ControllerContext name: ' . $name);
	}
	
	/**
	 * @param string $name
	 * @throws UnknownControllerContextException
	 * @return ControllerContext
	 */
	function getMainByName($name) {
		if (null !== ($controllerContext = $this->mainQueue->findByName($name))) {
			return $controllerContext;
		}
		
		throw new UnknownControllerContextException('Unknown main ControllerContext name: ' . $name);
	}
	
	/**
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function skipPrecache() {
		$this->precacheQueue->skip();
		return $this;
	}
	
	/**
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function skipFilter() {
		$this->filterQueue->skip();
		return $this;
	}
	
	/**
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function skipMain() {
		$this->mainQueue->skip();
		return $this;
	}
	
	/**
	 * 
	 */
	function abort() {
		$this->status = self::STATUS_ABORTED;
	}
	
	/**
	 * 
	 * @param string $key
	 * @return ControllerContext|null
	 */
	function getMainControllerContextByKey(string $key) {
		return $this->mainQueue->getByKey($key);
	}
	
	/**
	 * @param \Closure $closure
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	function onPostPrecache(\Closure $closure) { 
		$this->onPostPrecacheClosures[] = $closure;
		return $this;
	}
	
	/**
	 * 
	 */
	private function triggerPostPrecache() {
		while (null !== ($closure = array_shift($this->onPostPrecacheClosures))) {
			$closure();
		}
	}
}

class ControllerContextQueue {
	/**
	 * @var ControllerContext
	 */
	private $currentControllerContext = null;
	/**
	 * @var ControllerContext[]
	 */
	private $controllerContexts = [];
	/**
	 * @var int
	 */
	private $nextIndex = 0;
	
	/**
	 * 
	 */
	function __construct() {
	}
	
	/**
	 * @param ControllerContext $controllerContext
	 * @param bool $afterCurrent
	 */
	public function add(ControllerContext $controllerContext, bool $afterCurrent = false) {
		if (!$afterCurrent) {
			$this->controllerContexts[] = $controllerContext;
			return;
		}
		
		$this->insertControllerContext($this->controllerContexts, $this->nextIndex, $controllerContext);
	}
	
	/**
	 * @return ControllerContext|null
	 */
	function next() {
		if (isset($this->controllerContexts[$this->nextIndex])) {
			return $this->currentControllerContext = $this->controllerContexts[$this->nextIndex++];
		}
		
		$this->currentControllerContext = null;
		return null;
	}
	
	/**
	 * @return \n2n\web\http\controller\ControllerContext|null
	 */
	function getCurrent() {
		return $this->currentControllerContext;
	}
	
	/**
	 * 
	 */
	function skip() {
		$this->nextIndex = count($this->controllerContexts);
	}
	
	/**
	 * @param ControllerContext[] $arr
	 * @param int $currentIndex
	 * @param ControllerContext $controllerContext
	 */
	private function insertControllerContext(&$arr, $currentIndex, $controllerContext) {
		$num = count($arr);
		for ($i = $currentIndex + 1; $i < $num; $i++) {
			$cc = $arr[$i];
			$arr[$i] = $controllerContext;
			$controllerContext = $cc;
		}
		
		$arr[] = $controllerContext;
	}
	
	/**
	 * @param string $key
	 * @return ControllerContext|null
	 */
	function getByKey(string $key) {
		foreach ($this->mainControllerContexts as $mainCc) {
			if ($mainCc->getName() == $key) {
				return $mainCc;
			}
		}
		
		return null;
	}
	
	/**
	 * @param string $name
	 * @return \n2n\web\http\controller\ControllerContext|NULL
	 */
	function findByName(string $name) {
		for ($i = count($this->controllerContexts) - 1; $i >= 0; $i--) {
			$ccName = $this->controllerContexts[$i]->getName();
			
			if ($ccName !== null && $ccName == $name) {
				return $this->controllerContexts[$i];
			}
			
			if (get_class($this->controllerContexts[$i]->getController()) == $name) {
				return $this->controllerContexts[$i];
			}
		}
		
		return null;
	}
	
	function isEmpty() {
		return empty($this->controllerContexts);
	}
}
