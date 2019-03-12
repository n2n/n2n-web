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

use n2n\core\container\N2nContext;
use n2n\l10n\N2nLocale;
use n2n\util\uri\Url;
use n2n\util\uri\Path;
use n2n\core\VarStore;
use n2n\util\col\ArrayUtils;
use n2n\web\http\controller\ControllerContext;
use n2n\util\type\ArgUtils;

class HttpContext {
	private $request;
	private $response;
	private $session;
	private $baseAssetsUrl;
	private $supersystem;
	private $availableSubsystems;
	private $n2nContext;
	
	public function __construct(Request $request, Response $response, Session $session, Url $baseAssetsUrl, 
			Supersystem $supersystem, array $availableSubsystems, N2nContext $n2nContext) {
		$this->request = $request;
		$this->response = $response;
		$this->session = $session;
		$this->baseAssetsUrl = $baseAssetsUrl;
		$this->supersystem = $supersystem;
		$this->availableSubsystems = $availableSubsystems;
		$this->n2nContext = $n2nContext;
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
		return $this->request->getN2nLocale();
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
		
		$subsystem = $this->request->getSubsystem();
		if ($subsystem !== null && $subsystem->containsN2nLocaleId($n2nLocaleId)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getContextN2nLocales(): array {
		$contextN2nLocales = $this->supersystem->getN2nLocales();
		$subsystem = $this->request->getSubsystem();
		if ($subsystem !== null) {
			return array_merge($contextN2nLocales, $subsystem->getN2nLocales());
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
	 * @return \n2n\web\http\Supersystem
	 */
	public function getSupersystem() {
		return $this->supersystem;
	}
	
	/**
	 * @return Subsystem[] 
	 */
	public function getAvailableSubsystems() {
		return $this->availableSubsystems;
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
	 * @param bool $ssl
	 * @param mixed $subsystem name or instance of {@link \n2n\web\http\Subsystem}
	 * @return \n2n\util\uri\Url
	 */
	public function buildContextUrl(bool $ssl = null, $subsystem = null, bool $absolute = false): Url {
		$url = null;
		
		if ($subsystem === null) {
			$url = $this->request->getContextPath()->toUrl();
		} else {
			if (!($subsystem instanceof Subsystem)) {
				ArgUtils::valType($subsystem, array('string', 'Subsystem'), true, 'subsystem');
				$subsystem = $this->getAvailableSubsystemByName($subsystem);
			}
		
			$url = $this->buildSubsystemUrl($subsystem);
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
			if ((!$url->hasScheme() && $this->isSsl()) || $url->getScheme() == self::PROTOCOL_HTTPS) {
				return $url;
			}
				
			return $url->chScheme(self::PROTOCOL_HTTPS);
		}
	
		if ((!$url->hasScheme() && !$this->isSsl()) || $url->getScheme() == self::PROTOCOL_HTTP) {
			return $url;
		}
			
		return $url->chScheme(self::PROTOCOL_HTTP);
	}
	
	/**
	 * @param Subsystem $subsystem
	 * @return \n2n\util\uri\Url
	 */
	private function buildSubsystemUrl(Subsystem $subsystem) {
		$url = new Url();
		
		if (null !== ($subsystemHostName = $subsystem->getHostName())) {
			if ($this->request->getHostName() != $subsystemHostName) {
				$url = $url->chHost($subsystemHostName);
			}
		}
	
		if (null !== ($contextPath = $subsystem->getContextPath())) {
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
	 */
	public function getAvailableSubsystemByName($name) {
		if (isset($this->availableSubsystems[$name])) {
			return $this->availableSubsystems[$name];
		}
		
		throw new UnknownSubsystemException('Unknown subsystem name: ' . $name);
	}
	
	/**
	 * @return N2nContext
	 */
	public function getN2nContext(): N2nContext {
		return $this->n2nContext;
	}
}
