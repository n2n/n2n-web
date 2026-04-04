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
use n2n\core\N2N;

class HttpContext {

	/**
	 * @var Subsystem[]
	 */
	private array $subsystems;
	private N2nContext $n2nContext;

	const DEFAULT_STATUS_DEV_VIEW = 'n2n\web\view\errorpages\statusDev.html';
	const DEFAULT_STATUS_LIVE_VIEW = 'n2n\web\view\errorpages\statusLive.html';
	const DEFAULT_500_DEV_VIEW = 'n2n\web\view\errorpages\500Dev.html';
	const DEFAULT_500_LIVE_VIEW = 'n2n\web\view\errorpages\500Live.html';
	
	private array $errorStatusViewNames = [];
	private ?string $errorStatusDefaultViewName = null;
	private ?StatusException $prevStatusException = null;
	private HttpSystemContext $httpSystemContext;
	
	public function __construct(private Request $request, private Response $response, private Session $session,
			private Url $baseAssetsUrl, Supersystem $supersystem, array $subsystems, N2nContext $n2nContext) {

		$this->setSubsystems($subsystems);
		$this->n2nContext = $n2nContext;

		$this->httpSystemContext = new HttpSystemContext($supersystem,
				$this->determineSubsystemRule($request->getHostName(), $request->getContextPath()));
	}
	
	/**
	 * @return Request
	 */
	public function getRequest(): Request {
		return $this->request;
	}
	
	/**
	 * @return Response
	 */
	public function getResponse(): Response {
		return $this->response;
	}
	
	/**
	 * @return Session
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
	 * @return N2nLocale
	 */
	public function getN2nLocale(): N2nLocale {
		return $this->n2nContext->getN2nLocale();
	}
	
	/**
	 * @return string
	 */
	public function getN2nLocaleHttpId(): string {
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
		return $this->httpSystemContext->mainN2nLocale;
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @return boolean
	 */
	public function containsContextN2nLocale(N2nLocale $n2nLocale): bool {
		return $this->httpSystemContext->containsContextN2nLocale($n2nLocale);
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getContextN2nLocales(): array {
		return $this->httpSystemContext->contextN2nLocales;
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getAvailableN2nLocales(): array {
		$contextN2nLocales = $this->httpSystemContext->supersystem->getN2nLocales();
		foreach ($this->getSubsystems() as $subsystem) {
			$contextN2nLocales = array_merge($contextN2nLocales, $subsystem->getN2nLocales());
		}
		return $contextN2nLocales;
	}

	function getActiveSubsystemRule(): ?SubsystemRule {
		return $this->httpSystemContext->subsystemRule;
	}

	function setActiveSubsystemRule(?SubsystemRule $activeSubsystemRule): static {
		$this->httpSystemContext->subsystemRule = $activeSubsystemRule;
		return $this;
	}

	public function getSupersystem(): Supersystem {
		return $this->httpSystemContext->supersystem;
	}
	
	/**
	 * @return Subsystem[]
	 */
	function getSubsystems(): array {
		return $this->subsystems;
	}

	/**
	 * @param Subsystem[] $subsystems
	 * @return HttpContext
	 */
	function setSubsystems(array $subsystems): static {
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
	public function getAvailableSubsystems(): array {
		return $this->subsystems;
	}
	
	/**
	 * @return Url
	 */
	public function getBaseAssetsUrl(): Url {
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
	 * @return Path
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
	public function findBestSubsystemRuleBySubsystemAndN2nLocale(Subsystem|string $subsystem, ?N2nLocale $n2nLocale = null): ?SubsystemRule {
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
	public function determineSubsystemRule(string $hostName, Path $contextPath): ?SubsystemRule {
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
	 * @param bool|null $ssl
	 * @param Subsystem|SubsystemRule|string|null $subsystem
	 * @param bool $absolute
	 * @return Url
	 */
	public function buildContextUrl(?bool $ssl = null, Subsystem|SubsystemRule|string|null $subsystem = null, bool $absolute = false): Url {
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
	 * @return Url
	 */
	private function completeSchemaCheck(Url $url, ?bool $ssl = null): Url {
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

	private function buildSubsystemUrl(SubsystemRule $subsystemRule): Url {
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
	 * @return Subsystem
	 * @throws UnknownSubsystemException
	 * @deprecated {@link self::getSubsystemByName()}
	 */
	public function getAvailableSubsystemByName(string $name): Subsystem {
		if (isset($this->subsystems[$name])) {
			return $this->subsystems[$name];
		}

		throw new UnknownSubsystemException('Unknown subsystem name: ' . $name);
	}


	function getSubsystemRuleByName(string $subsystemRuleName): array {
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
	 * @return Subsystem
	 * @throws UnknownSubsystemException
	 */
	public function getSubsystemByName(string $name): Subsystem {
		if (isset($this->subsystems[$name])) {
			return $this->subsystems[$name];
		}

		throw new UnknownSubsystemException('Unknown subsystem name: ' . $name);
	}
	
	/**
	 * @return N2nContext
	 */
	public function getN2nContext(): N2nContext {
		return $this->n2nContext;
	}
	
	/**
	 * @return string[]
	 */
	function getErrorStatusViewNames(): array {
		return $this->errorStatusViewNames;
	}
	
	/**
	 * @param string[] $errorStatusViewNames
	 */
	function setErrorStatusViewNames(array $errorStatusViewNames): void {
		ArgUtils::valArray($errorStatusViewNames, 'string');
		$this->errorStatusViewNames = $errorStatusViewNames;
	}
	
	/**
	 * @param string|null $errorStatusDefaultViewName
	 */
	function setErrorStatusDefaultViewName(?string $errorStatusDefaultViewName): void {
		$this->errorStatusDefaultViewName = $errorStatusDefaultViewName;
	}
	
	/**
	 * @return string|null
	 */
	function getErrorStatusDefaultViewName(): ?string {
		return $this->errorStatusDefaultViewName;
	}

	function determineErrorStatusViewName(int $httpStatus): string {
		$viewName = $this->errorStatusViewNames[$httpStatus] ?? $this->errorStatusDefaultViewName;
		if ($viewName !== null) {
			return $viewName;
		}

		if ($httpStatus === Response::STATUS_500_INTERNAL_SERVER_ERROR) {
			return (N2N::isDevelopmentModeOn() ? self::DEFAULT_500_DEV_VIEW : self::DEFAULT_500_LIVE_VIEW);
		}

		return (N2N::isDevelopmentModeOn() ? self::DEFAULT_STATUS_DEV_VIEW : self::DEFAULT_STATUS_LIVE_VIEW);
	}
	
	/**
	 * @param StatusException|null $prevStatusException
	 */
	function setPrevStatusException(?StatusException $prevStatusException): void {
		$this->prevStatusException = $prevStatusException;
	}

	function getPrevStatusException(): ?StatusException {
		return $this->prevStatusException;
	}

	/**
	 * @return N2nLocale
	 */
	function determineBestN2nLocale(): N2nLocale {
		$n2nLocales = $this->httpSystemContext->supersystem->getN2nLocales();
		if ($this->httpSystemContext->subsystemRule !== null) {
			$n2nLocales = array_merge($n2nLocales, $this->httpSystemContext->subsystemRule->getN2nLocales());
		}

		return $this->request->detectBestN2nLocale($n2nLocales);
	}
}
