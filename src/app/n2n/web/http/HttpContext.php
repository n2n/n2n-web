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
namespace n2n\web\http;

use n2n\l10n\N2nLocale;
use n2n\util\uri\Url;
use n2n\util\uri\Path;
use n2n\core\VarStore;
use n2n\util\col\ArrayUtils;
use n2n\web\http\controller\ControllerContext;
use n2n\util\type\ArgUtils;
use n2n\util\magic\MagicContext;
use n2n\web\ui\ViewFactory;
use n2n\core\container\N2nContext;

class HttpContext {
	
	private $request;
	private $response;
	private $session;
	private $baseAssetsUrl;
	private $supersystem;
	/**
	 * @var Subsystem[]
	 */
	private $subsystems;
	private $n2nContext;
	private ?SubsystemRule $activeSubsystemRule;
	
	
	const DEFAULT_STATUS_VIEW = 'n2n\\web\\http\\view\\status.html';
	
	private $errorStatusViewNames = [];
	private $errorStatusDefaultViewName = null;
	private $errorStatusException = null;
	private $prevStatusException = null;
	
	public function __construct(Request $request, Response $response, Session $session, Url $baseAssetsUrl, 
			Supersystem $supersystem, array $subsystems, N2nContext $n2nContext) {
		$this->request = $request;
		$this->response = $response;
		$this->session = $session;
		$this->baseAssetsUrl = $baseAssetsUrl;
		$this->supersystem = $supersystem;
		$this->setSubsystems($subsystems);
		$this->n2nContext = $n2nContext;

		$this->activeSubsystemRule = $this->determineSubsystemRule($request->getHostName(), $request->getContextPath());
	}
	
	/**
	 * @return \n2n\web\http\Request
	 */
	public function getRequest(): Request {
		return $this->request;
	}
	
	/**
	 * @return \n2n\web\http\Response
	 */
	public function getResponse(): Response {
		return $this->response;
	}
	
	/**
	 * @return \n2n\web\http\Session
	 */
	public function getSession(): Session {
		return $this->session;
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function n2nLocaleToHttpId(N2nLocale $n2nLocale): string {
		return $n2nLocale->toWebId();
	}
	
	/**
	 * @return \n2n\l10n\N2nLocale
	 */
	public function getN2nLocale() {
		return $this->n2nContext->getN2nLocale();
	}
	
	/**
	 * @return string
	 */
	public function getN2nLocaleHttpId() {
		return $this->n2nLocaleToHttpId($this->getN2nLocale());
	}
	
	/**
	 * @param string $n2nLocaleHttpId
	 * @return N2nLocale
	 * @throws \n2n\l10n\IllegalN2nLocaleFormatException
	 */
	public function httpIdToN2nLocale(string $n2nLocaleHttpId, bool $lenient = false): N2nLocale {
		return N2nLocale::fromWebId($n2nLocaleHttpId, false, $lenient);
	}
	
	/**
	 * @return N2nLocale
	 */
	public function getMainN2nLocale(): N2nLocale {
		$mainN2nLocale = ArrayUtils::first($this->getContextN2nLocales());
		if ($mainN2nLocale !== null) {
			return $mainN2nLocale;
		}
		
		return N2nLocale::getDefault();
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @return boolean
	 */
	public function containsContextN2nLocale(N2nLocale $n2nLocale) {
		$n2nLocaleId = $n2nLocale->getId();
		if ($this->supersystem->containsN2nLocaleId($n2nLocaleId)) {
			return true;
		}
		
		$subsystemRule = $this->getActiveSubsystemRule();
		if ($subsystemRule !== null && $subsystemRule->containsN2nLocaleId($n2nLocaleId)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getContextN2nLocales(): array {
		$contextN2nLocales = $this->supersystem->getN2nLocales();
		if ($this->activeSubsystemRule !== null) {
			return array_merge($contextN2nLocales, $this->activeSubsystemRule->getN2nLocales());
		}
		return $contextN2nLocales;
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getAvailableN2nLocales() {
		$contextN2nLocales = $this->supersystem->getN2nLocales();
		foreach ($this->getAvailableSubsystems() as $subsystem) {
			$contextN2nLocales = array_merge($contextN2nLocales, $subsystem->getN2nLocales());
		}
		return $contextN2nLocales;
	}

	/**
	 * @return SubsystemRule
	 */
	function getActiveSubsystemRule() {
		return $this->activeSubsystemRule;
	}

	/**
	 * @param SubsystemRule|null $activeSubsystemRule
	 * @return $this
	 */
	function setActiveSubsystemRule(?SubsystemRule $activeSubsystemRule) {
		$this->activeSubsystemRule = $activeSubsystemRule;
		return $this;
	}

	/**
	 * @return \n2n\web\http\Supersystem
	 */
	public function getSupersystem() {
		return $this->supersystem;
	}
	
	/**
	 * @return Subsystem[]
	 */
	function getSubsystems() {
		return $this->subsystems;
	}

	/**
	 * @param Subsystem[] $subsystems
	 * @return HttpContext
	 */
	function setSubsystems(array $subsystems) {
		ArgUtils::valArray($subsystems, Subsystem::class);
		$this->subsystems = [];
		foreach ($subsystems as $subsystem) {
			$this->subsystems[$subsystem->getName()] = $subsystem;
		}
		return $this;
	}
	
	/**
	 * @return Subsystem[] 
	 * @deprecated use {@link self::getSubsystems()}
	 */
	public function getAvailableSubsystems() {
		return $this->subsystems;
	}
	
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getBaseAssetsUrl() {
		return $this->baseAssetsUrl;
	}
	
	/**
	 * @param string $moduleNamespace
	 * @param bool $absolute
	 * @return Url
	 */
	public function getAssetsUrl(string $moduleNamespace, bool $absolute = false): Url {
		$assetsUrl = $this->baseAssetsUrl->extR(VarStore::namespaceToDirName($moduleNamespace));
		
		if (!$absolute) {
			return $assetsUrl;
		}

		return $this->request->getHostUrl()->ext($assetsUrl);
	}
	
	/**
	 * @param ControllerContext $controllerContext
	 * @return \n2n\util\uri\Path
	 */
	public function getControllerContextPath(ControllerContext $controllerContext): Path {
		return $this->request->getContextPath()->ext($controllerContext->getCmdContextPath());
	}

	/**
	 *
	 * @param Subsystem|string $subsystem
	 * @param N2nLocale|null $n2nLocale
	 * @return SubsystemRule|null
	 */
	public function findBestSubsystemRuleBySubsystemAndN2nLocale(Subsystem|string $subsystem, N2nLocale $n2nLocale = null) {
		if (is_string($subsystem)) {
			$subsystem = $this->getSubsystemByName($subsystem);
		}

		return $subsystem->findBestRuleByN2nLocale($n2nLocale ?? $this->getN2nLocale());
	}

	/**
	 * @param string $hostName
	 * @param Path $contextPath
	 * @return SubsystemRule|null
	 */
	public function determineSubsystemRule(string $hostName, Path $contextPath) {
		foreach ($this->subsystems as $subsystem) {
			foreach ($subsystem->getRules() as $subsystemRule) {
				$ruleHostName = $subsystemRule->getHostName();
				$ruleContextPath = $subsystemRule->getContextPath();

				if ($ruleHostName !== null && $ruleHostName !== $hostName) {
					continue;
				}

				if ($ruleContextPath !== null && !$contextPath->equals($ruleContextPath)) {
					continue;
				}

				return $subsystemRule;
			}
		}

		return null;
	}

	/**
	 * @param bool $ssl
	 * @param Subsystem|SubsystemRule|string $subsystem
	 * @return \n2n\util\uri\Url
	 */
	public function buildContextUrl(bool $ssl = null, Subsystem|SubsystemRule|string $subsystem = null, bool $absolute = false): Url {
		$url = null;
		
		if ($subsystem !== null) {
			if ($subsystem instanceof SubsystemRule) {
				$url = $this->buildSubsystemUrl($subsystem);
			} elseif (null !== ($subsystemRule = self::findBestSubsystemRuleBySubsystemAndN2nLocale($subsystem, $this->getN2nLocale()))) {
				$url = $this->buildSubsystemUrl($subsystemRule);
			}
		}

		if ($url === null) {
			$url = $this->request->getContextPath()->toUrl();
		}
		
		if ($absolute && $url->isRelative()) {
			$url = $this->request->getHostUrl()->ext($url);
		}
		
		$url = $this->completeSchemaCheck($url, $ssl);
		
		if ($absolute && !$url->hasScheme()) {
			$url = $url->chScheme($this->request->getHostUrl()->getScheme());
		}
		
		return $url;
	}
	
	/**
	 * @param Url $url
	 * @param bool|null $ssl
	 * @return \n2n\util\uri\Url
	 */
	private function completeSchemaCheck(Url $url, bool $ssl = null) {
		if ($ssl === null) return $url;
	
		if ($ssl) {
			if ((!$url->hasScheme() && $this->request->isSsl()) || $url->getScheme() == Request::PROTOCOL_HTTPS) {
				return $url;
			}
				
			return $url->chScheme(Request::PROTOCOL_HTTPS);
		}
	
		if ((!$url->hasScheme() && !$this->request->isSsl()) || $url->getScheme() == Request::PROTOCOL_HTTP) {
			return $url;
		}
			
		return $url->chScheme(Request::PROTOCOL_HTTP);
	}
	
	/**
	 * @param Subsystem $subsystem
	 * @return \n2n\util\uri\Url
	 */
	private function buildSubsystemUrl(SubsystemRule $subsystemRule) {
		$url = new Url();

		if (null !== ($subsystemHostName = $subsystemRule->getHostName())) {
			if ($this->request->getHostName() != $subsystemHostName) {
				$url = $url->chHost($subsystemHostName);
			}
		}
	
		if (null !== ($contextPath = $subsystemRule->getContextPath())) {
			$url = $url->chPath($contextPath);
		} else {
			$url = $url->chPath($this->request->getContextPath());
		}
	
		return $url;
	}

	/**
	 * @param string $name
	 * @return \n2n\web\http\Subsystem
	 * @throws UnknownSubsystemException
	 * @deprecated {@link self::getSubsystemByName()}
	 */
	public function getAvailableSubsystemByName($name) {
		if (isset($this->subsystems[$name])) {
			return $this->subsystems[$name];
		}

		throw new UnknownSubsystemException('Unknown subsystem name: ' . $name);
	}


	function getSubsystemRuleByName(string $subsystemRuleName) {
		$subsystemRules = [];

		foreach ($this->subsystems as $subsystem) {
			if ($subsystem->containsRuleName($subsystemRuleName)) {
				$subsystemRules[] = $subsystem->getRuleByName($subsystemRuleName);
			}
		}

		return $subsystemRules;
	}

	/**
	 * @param string $name
	 * @return \n2n\web\http\Subsystem
	 * @throws UnknownSubsystemException
	 */
	public function getSubsystemByName($name) {
		if (isset($this->subsystems[$name])) {
			return $this->subsystems[$name];
		}

		throw new UnknownSubsystemException('Unknown subsystem name: ' . $name);
	}
	
	/**
	 * @return N2nContext
	 */
	public function getN2nContext() {
		return $this->n2nContext;
	}
	
	/**
	 * @return string[]
	 */
	function getErrorStatusViewNames() {
		return $this->errorStatusViewNames;
	}
	
	/**
	 * @param string[] $errorStatusViewNames
	 */
	function setErrorStatusViewNames(array $errorStatusViewNames) {
		ArgUtils::valArray($errorStatusViewNames, 'string');
		$this->errorStatusViewNames = $errorStatusViewNames;
	}
	
	/**
	 * @param string|null $errorStatusDefaultViewName
	 */
	function setErrorStatusDefaultViewName(?string $errorStatusDefaultViewName) {
		$this->errorStatusDefaultViewName = $errorStatusDefaultViewName;
	}
	
	/**
	 * @return string|null
	 */
	function getErrorStatusDefaultViewName() {
		return $this->errorStatusDefaultViewName;
	}
	
	/**
	 * @param int $httpStatus
	 * @return string
	 */
	function determineErrorStatusViewName(int $httpStatus) {
		return $this->errorStatusViewNames[$httpStatus] ?? $this->errorStatusDefaultViewName ?? self::DEFAULT_STATUS_VIEW;
	}
	
	/**
	 * @param StatusException|null $prevStatusException
	 */
	function setPrevStatusException(?StatusException $prevStatusException) {
		$this->prevStatusException = $prevStatusException;
	}
	
	/**
	 * @return StatusException
	 */
	function getPrevStatusException() {
		return $this->prevStatusException;
	}

	/**
	 * @return N2nLocale
	 */
	function determineBestN2nLocale() {
		$n2nLocales = $this->supersystem->getN2nLocales();
		if ($this->activeSubsystemRule !== null) {
			$n2nLocales = array_merge($n2nLocales, $this->activeSubsystemRule->getN2nLocales());
		}

		return $this->request->detectBestN2nLocale($n2nLocales);
	}
}
