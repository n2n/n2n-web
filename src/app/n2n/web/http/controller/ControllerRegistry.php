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
use n2n\web\http\HttpContext;
use n2n\web\http\SubsystemRule;
use n2n\web\http\Supersystem;
use n2n\core\config\RoutingConfig;
use n2n\core\config\routing\ControllerDef;

class ControllerRegistry {

	function __construct(private readonly WebConfig $webConfig, private RoutingConfig $routingConfig) {
	}

	public function setContextN2nLocale(string $alias, N2nLocale $contextN2nLocale): void {
		$this->contextN2nLocales[$alias] = $contextN2nLocale;
	}

	/**
	 * @param N2nContext $n2nContext
	 * @param Path $cmdPath
	 * @param string $subsystemRuleName
	 * @return \n2n\web\http\controller\ControllingPlan
	 */
	public function createControllingPlan(HttpContext $httpContext, Path $cmdPath,
			?SubsystemRule $subsystemRule = null): ControllingPlan {
		$contextN2nLocales = new \ArrayObject();
		foreach ($httpContext->getSupersystem()->getN2nLocales() as $n2nLocale) {
			$contextN2nLocales[$n2nLocale->toWebId()] = $n2nLocale;
		}

		if ($subsystemRule !== null) {
			foreach ($subsystemRule->getN2nLocales() as $n2nLocale) {
				$contextN2nLocales[$n2nLocale->toWebId()] = $n2nLocale;
			}
		}

		$controllingPlanFactory = new ControllingPlanFactory($contextN2nLocales,
				$this->webConfig->isPayloadCachingEnabled(), $this->webConfig->isViewCachingEnabled());

		$subsystemRuleName = $subsystemRule?->getName();
		$subsystemName = $subsystemRule?->getSubsystem()->getName();
		foreach ($this->routingConfig->getPrecacheControllerDefs() as $precacheControllerDef) {
			if (!$precacheControllerDef->acceptableBy($subsystemName, $subsystemRuleName)) {
				continue;
			}

			$controllingPlanFactory->registerPrecacheControllerDef($precacheControllerDef);
		}

		foreach ($this->routingConfig->getFilterControllerDefs() as $filterControllerDef) {
			if (!$filterControllerDef->acceptableBy($subsystemName, $subsystemRuleName)) {
				continue;
			}

			$controllingPlanFactory->registerFilterControllerDef($filterControllerDef);
		}

		foreach ($this->routingConfig->getMainControllerDefs() as $mainControllerDef) {
			if (!$mainControllerDef->acceptableBy($subsystemName, $subsystemRuleName)) {
				continue;
			}

			$controllingPlanFactory->registerMainControllerDef($mainControllerDef);
		}

		$controllingPlan = $controllingPlanFactory->createControllingPlan($httpContext, $cmdPath);
		
		$controllingPlan->setResponseHeaders($this->buildResponseHeaders($httpContext->getSupersystem(), 
				$subsystemRule));
		
		return $controllingPlan;
	}
	
	private function buildResponseHeaders(Supersystem $superSystem, ?SubsystemRule $subsystemRule) {
		$responseHeaders = $this->webConfig->getResponseDefaultHeaders();
		foreach ($superSystem->getResponseHeaders() as $key => $responseHeader) {
			$responseHeaders[$key] = $responseHeader;
		}
		
		if ($subsystemRule !== null) {
			foreach ($subsystemRule->getResponseHeaders() as $key => $responseHeader) {
				$responseHeaders[$key] = $responseHeader;
			}
		}
		
		if (!empty($responseHeaders)) {
			return $responseHeaders;
		}
		
		return ['Cache-Control: no-cache'];
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

	public function __construct(\ArrayObject $contextN2nLocales, public bool $payloadCachingEnabled = true,
			public bool $viewCachingEnabled = true) {
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
	public function createControllingPlan(HttpContext $httpContext, Path $cmdPath): ControllingPlan {
		$controllingPlan = new ControllingPlan($httpContext);

		$n2nContext = $httpContext->getN2nContext();

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
							$precacheControllerDef->getControllerClassName()),
					$this->payloadCachingEnabled, $this->viewCachingEnabled);
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
							$filterControllerDef->getControllerClassName()),
					$this->payloadCachingEnabled, $this->viewCachingEnabled);
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
							&& !$matchResult->hasPlaceholderValues())
					|| ($currentMainControllerDef->getSubsystemName() === null 
							&& $mainControllerDef->getSubsystemName() !== null)*/) {
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

			if ($currentUsedPathSize < $usedPathSize
					|| ($currentUsedPathSize == $usedPathSize
							&& $currentMainControllerDef->getSubsystemName() === null
							&& $mainControllerDef->getSubsystemName() !== null)) {
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
			$controllerContext = new ControllerContext($currentMatchResult->getSurplusPath(),
					$currentMatchResult->getUsedPath(), $controller, $this->payloadCachingEnabled,
					$this->viewCachingEnabled);
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
