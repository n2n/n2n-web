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

use n2n\web\http\path\PathPatternCompiler;
use n2n\core\container\N2nContext;
use n2n\util\uri\Path;
use n2n\l10n\N2nLocale;
use n2n\web\http\path\PlaceholderValidator;
use n2n\context\RequestScoped;
use n2n\core\config\WebConfig;
use n2n\web\http\UnknownSubsystemException;
use n2n\context\LookupFailedException;

class ControllerRegistry implements RequestScoped {
	private $webConfig;
	private $n2nContext;
	
	/**
	 * 
	 */
	private function _init(WebConfig $webConfig, N2nContext $n2nContext) {
		$this->webConfig = $webConfig;
		$this->n2nContext = $n2nContext;
	}
	
	
	/**
	 * @param string $alias
	 * @param N2nLocale $contextN2nLocale
	 */
	public function setContextN2nLocale($alias, N2nLocale $contextN2nLocale) {
		$this->contextN2nLocales[$alias] = $contextN2nLocale;
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 * @param string $subsystemName
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	public function createControllingPlan(Path $cmdPath, string $subsystemName = null) {
		$contextN2nLocales = new \ArrayObject();
		foreach ($this->webConfig->getSupersystem()->getN2nLocales() as $n2nLocale) {
			$contextN2nLocales[$n2nLocale->toWebId()] = $n2nLocale;	
		}
		
		if ($subsystemName !== null) {
			$subsystems = $this->webConfig->getSubsystems();
			if (!isset($subsystems[$subsystemName])) {
				throw new UnknownSubsystemException('Unknown subystem name: ' . $subsystemName);
			}
			
			foreach ($subsystems[$subsystemName]->getN2nLocales() as $n2nLocale) {
				$contextN2nLocales[$n2nLocale->toWebId()] = $n2nLocale;
			}
		}
		
		$controllingPlanFactory = new ControllingPlanFactory($contextN2nLocales);
		
		foreach ($this->webConfig->getPrecacheControllerDefs() as $precacheControllerDef) {
			if ($precacheControllerDef->getSubsystemName() !== null
					&& $precacheControllerDef->getSubsystemName() != $subsystemName) {
				continue;
			}
			
			$controllingPlanFactory->registerPrecacheControllerDef($precacheControllerDef);
		}
		
		foreach ($this->webConfig->getFilterControllerDefs() as $filterControllerDef) {
			if ($filterControllerDef->getSubsystemName() !== null
					&& $filterControllerDef->getSubsystemName() != $subsystemName) {
				continue;
			}

			$controllingPlanFactory->registerFilterControllerDef($filterControllerDef);
		}
		
		foreach ($this->webConfig->getMainControllerDefs() as $mainControllerDef) {
			if ($mainControllerDef->getSubsystemName() !== null
					&& $mainControllerDef->getSubsystemName() !== $subsystemName) {
				continue;
			}
			
			$controllingPlanFactory->registerMainControllerDef($mainControllerDef);
		}
		
		return $controllingPlanFactory->createControllingPlan($this->n2nContext, $cmdPath);
	}
}

class ControllingPlanFactory {
	const LOCALE_PLACEHOLDER = 'locale';
	
	private $contextN2nLocales;
	private $pathPatternCompiler;
	private $precacheControllerDefs = [];
	private $precachePathPatterns = array();
	private $filterControllerDefs = array();
	private $filterPathPatterns = array();
	private $mainControllerDefs = array();
	private $mainPathPatterns = array();
	
	public function __construct(\ArrayObject $contextN2nLocales) {
		$this->contextN2nLocales = $contextN2nLocales;
		$this->pathPatternCompiler = new PathPatternCompiler();
		$this->pathPatternCompiler->addPlaceholder(self::LOCALE_PLACEHOLDER,
				new N2nLocalePlaceholderValidator($contextN2nLocales));
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 * @param string $subsystemName
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	public function createControllingPlan(N2nContext $n2nContext, Path $cmdPath): ControllingPlan {
		$controllingPlan = new ControllingPlan($n2nContext);
		
		$this->applyPrecaches($controllingPlan, $n2nContext, $cmdPath);
		
		$controllingPlan->onPostPrecache(function () use ($controllingPlan, $n2nContext, $cmdPath) {
			$this->applyFilters($controllingPlan, $n2nContext, $cmdPath);
			$this->applyMain($controllingPlan, $n2nContext, $cmdPath);
		});
	
		return $controllingPlan;
	}
	
	/**
	 * @param ControllerDef $controllerDef
	 */
	public function registerPrecacheControllerDef(ControllerDef $controllerDef) {
		$this->precacheControllerDefs[] = $controllerDef;
	}
	
	/**
	 * @param ControllerDef $controllerDef
	 */
	public function registerFilterControllerDef(ControllerDef $controllerDef) {
		$this->filterControllerDefs[] = $controllerDef;
	}
	
	/**
	 * @param ControllerDef $controllerDef
	 */
	public function registerMainControllerDef(ControllerDef $controllerDef) {
		$this->mainControllerDefs[] = $controllerDef;
	}
	
	/**
	 * @param ControllerDef[] $controllerDefs
	 * @param ControllingPlan $controllingPlan
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 */
	private function applyPrecaches($controllingPlan, $n2nContext, $cmdPath) {
		foreach ($this->precacheControllerDefs as $key => $precacheControllerDef) {
			if (!isset($this->precachePathPatterns[$key])) {
				$this->precachePathPatterns[$key] = $this->pathPatternCompiler
						->compile($precacheControllerDef->getContextPath());
			}
			
			$matchResult = $this->precachePathPatterns[$key]->matchesPath($cmdPath, true);
			if (null === $matchResult) continue;
			
			$controllerContext = new ControllerContext($matchResult->getSurplusPath(),
					$matchResult->getUsedPath(), $n2nContext->lookup(
							$precacheControllerDef->getControllerClassName()));
			$controllerContext->setParams($matchResult->getParamValues());
			$controllingPlan->addPrecacheFilter($controllerContext);
		}
	}
	
	/**
	 * @param ControllerDef[] $controllerDefs
	 * @param ControllingPlan $controllingPlan
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 */
	private function applyFilters($controllingPlan, $n2nContext, $cmdPath) {
		foreach ($this->filterControllerDefs as $key => $filterControllerDef) {
			if (!isset($this->filterPathPatterns[$key])) {
				$this->filterPathPatterns[$key] = $this->pathPatternCompiler
						->compile($filterControllerDef->getContextPath());
			}
				
			$matchResult = $this->filterPathPatterns[$key]->matchesPath($cmdPath, true);
			if (null === $matchResult) continue;
				
			$controllerContext = new ControllerContext($matchResult->getSurplusPath(),
					$matchResult->getUsedPath(), $n2nContext->lookup(
							$filterControllerDef->getControllerClassName()));
			$controllerContext->setParams($matchResult->getParamValues());
			$controllingPlan->addFilter($controllerContext);
		}
	}
	
	/**
	 * @param ControllingPlan $controllingPlan
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 * @throws ControllingPlanException
	 */
	private function applyMain(ControllingPlan $controllingPlan, N2nContext $n2nContext, Path $cmdPath) {
		$currentMatchResult = null;
		$currentMainControllerDef = null;
		foreach ($this->mainControllerDefs as $key => $mainControllerDef) {
			if (!isset($this->mainPathPatterns[$key])) {
				$this->mainPathPatterns[$key] = $this->pathPatternCompiler
						->compile($mainControllerDef->getContextPath());
			}

			$matchResult = $this->mainPathPatterns[$key]->matchesPath($cmdPath, true);
			if (null === $matchResult) continue;
			
			if (null === $currentMatchResult 
					/*|| ($currentMatchResult->hasPlaceholderValues() 
							&& !$matchResult->hasPlaceholderValues())*/
					|| ($currentMainControllerDef->getSubsystemName() === null 
							&& $mainControllerDef->getSubsystemName() !== null)) {
				$currentMatchResult = $matchResult;
				$currentMainControllerDef = $mainControllerDef;
				continue;
			}

			$currentUsedPathSize = $currentMatchResult->getUsedPath()->size();
			$usedPathSize = $matchResult->getUsedPath()->size();
				
			if ($currentUsedPathSize == $usedPathSize) {
				if (!$currentMatchResult->hasPlaceholderValues() && $matchResult->hasPlaceholderValues()) {
					continue;
				} else if ($currentMatchResult->hasPlaceholderValues() && !$matchResult->hasPlaceholderValues()) {
					$currentMatchResult = $matchResult;
					$currentMainControllerDef = $mainControllerDef;
					continue;
				}
			}
			
			if ($currentUsedPathSize < $usedPathSize) {
				$currentMatchResult = $matchResult;
				$currentMainControllerDef = $mainControllerDef;
				continue;
			}
			
			if ($currentUsedPathSize > $usedPathSize 
					|| $currentMainControllerDef->getControllerClassName() 
							== $mainControllerDef->getControllerClassName()
					|| ($currentMainControllerDef->getSubsystemName() !== null 
							&& $mainControllerDef->getSubsystemName() === null)) continue;
			
			throw new ControllingPlanException('Multiple registered main controllers match path \'' 
					. $cmdPath->toRealString(true) . '\' with same quality: ' . $this->controllerDefToString($currentMainControllerDef) 
					. ' and ' . $this->controllerDefToString($mainControllerDef));
		}
		
		if ($currentMatchResult !== null) {
			$placeholderValues = $currentMatchResult->getPlaceholderValues();
			if (isset($placeholderValues[self::LOCALE_PLACEHOLDER]) 
					&& $this->contextN2nLocales->offsetExists($placeholderValues[self::LOCALE_PLACEHOLDER])) {
				$controllingPlan->setN2nLocale($this->contextN2nLocales->offsetGet(
						$placeholderValues[self::LOCALE_PLACEHOLDER]));
			}
			
			$controller = $n2nContext->lookup($currentMainControllerDef->getControllerClassName());
			if (!($controller instanceof Controller)) {
				throw new LookupFailedException('Registered controller ' . get_class($controller) 
						. ' does not implement: ' . Controller::class);
			}
			$controllerContext = new ControllerContext($currentMatchResult->getSurplusPath(), $currentMatchResult->getUsedPath(),
					$controller);
			$controllerContext->setParams($currentMatchResult->getParamValues());
			$controllingPlan->addMain($controllerContext);
		}
	} 
	/**
	 * @param ControllerDef $controllerDef
	 * @return string
	 */
	private function controllerDefToString(ControllerDef $controllerDef) {
		return $controllerDef->getControllerClassName() . ' (' . $controllerDef->getContextPath() . ')';
	}
}

class N2nLocalePlaceholderValidator implements PlaceholderValidator {
	private $n2nLocales;
	
	public function __construct(\ArrayObject $n2nLocales) {
		$this->n2nLocales = $n2nLocales;
	}
	
	public function matches($pathPart) {
		return $this->n2nLocales->offsetExists($pathPart);
	}
}
