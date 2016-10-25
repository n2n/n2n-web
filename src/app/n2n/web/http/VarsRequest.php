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
namespace n2n\web\http;

use n2n\l10n\N2nLocale;
use n2n\util\uri\Path;
use n2n\util\uri\Url;
use n2n\util\uri\Query;
use n2n\web\http\UploadDefinition;
use n2n\util\uri\Authority;
use n2n\reflection\ArgUtils;
use n2n\web\http\AcceptRange;

class VarsRequest implements Request {
	private $serverVars;
	
	private $method;
	private $requestedUrl;
	private $cmdContextPath;
	private $cmdPath;
	private $postQuery;
	private $uploadDefinitions;
	private $n2nLocale;
	private $availableN2nLocaleAliases = array();
	private $availableSubsystems = array();
	private $assetsDirName;
	
	public function __construct(array $serverVars, array $getVars, array $postVars, 
			array $fileVars) {
		$this->serverVars = $serverVars;
		$this->postQuery = new Query($postVars);
		$this->uploadDefinitions = $this->extractUploadDefinitions($fileVars);
		
		$this->initUrl($getVars);
	}
	
	private function extractServerVar($name) {
		if (isset($this->serverVars[$name])) {
			return $this->serverVars[$name];
		}
		
		throw new IncompleRequestException('Missing Server-Variable: ' . $name); 
	}
	
	private function extractUploadDefinitions(array $fileVars) {
		$uploadDefinitions = array();
		foreach ($fileVars as $key => $fileVar) {
			$uploadDefinitions[$key] = $this->extractUploadDefinition($fileVar['error'], 
					$fileVar['name'], $fileVar['tmp_name'], $fileVar['type'], $fileVar['size']);
		}
		return $uploadDefinitions;
	}
	
	private function extractUploadDefinition($errorNo, $name, $tmpName, $type, $size) {
		if (is_numeric($errorNo) && is_scalar($name) && is_scalar($tmpName) && is_scalar($type)
				&& is_numeric($size)) {
			if ($errorNo == UPLOAD_ERR_OK && !is_uploaded_file($tmpName)) {
				throw new IncompleRequestException('Corrupted upload file: ' . $tmpName);
			}
			return new UploadDefinition($errorNo, $name, $tmpName, $type, $size);
		}
		
		if (!is_array($errorNo) || !is_array($name) || !is_array($tmpName) || !is_array($type)
				|| !is_array($size)) {
			throw new IncompleRequestException('Corrupted files var');		
		}
		
		$uploadDefinitions = array();
		foreach ($errorNo as $key => $errorNoField) {
			if (!isset($name[$key]) || !isset($tmpName[$key]) || !isset($type[$key]) 
					|| !isset($size[$key])) {
				throw new IncompleRequestException('Corrupted files var');
			}
				
			$uploadDefinitions[$key] = $this->extractUploadDefinition($errorNoField, $name[$key], 
					$tmpName[$key], $type[$key], $size[$key]);
		}
		return $uploadDefinitions;
	}
	
	private function initUrl(array $getVars) {
		$requestUrl = null;
		if (isset($this->serverVars['HTTP_X_REWRITE_URL'])) {
			$requestUrl = $this->serverVars['HTTP_X_REWRITE_URL'];
		} else {
			$requestUrl = $this->extractServerVar('REQUEST_URI');
		}
		
		$queryLength = mb_strlen($this->extractServerVar('QUERY_STRING'));
		if ($queryLength > 0) $requestUrl = mb_substr($requestUrl, 0, -($queryLength + 1));
		
		$scriptDirName = str_replace('\\', '/', dirname($this->extractServerVar('SCRIPT_NAME')));
		
		$this->cmdContextPath = Path::create($scriptDirName, true)->chEndingDelimiter(true);
		$this->cmdPath = Path::create(mb_substr($requestUrl, mb_strlen($scriptDirName)), true);
		
		$protocol = isset($this->serverVars['HTTPS']) && $this->serverVars['HTTPS'] != 'off' 
				&& $this->serverVars['HTTPS'] ? Request::PROTOCOL_HTTPS : Request::PROTOCOL_HTTP;
		$hostName = null;
		if (isset($this->serverVars['HTTP_HOST'])) {
			$hostName = $this->serverVars['HTTP_HOST'];
		} else {
			$hostName = $this->extractServerVar('SERVER_NAME');	
		}
		
		$this->method = Method::createFromString($this->extractServerVar('REQUEST_METHOD'));
		$this->requestedUrl = new Url($protocol, new Authority(null, $hostName), 
				$this->cmdContextPath->ext($this->cmdPath), new Query($getVars));		
	}	
	
	public function getMethod(): int {
		return $this->method;
	}
	
	public function getHeader($name) {
		$varKey = 'HTTP_' . str_replace('-', '_', mb_strtoupper($name));
		if (isset($this->serverVars[$varKey])) {
			return $this->serverVars[$varKey];
		}
		return null;
	}
	/**
	 * @return boolean
	 */
	public function isSsl() {
		return $this->requestedUrl->getScheme() == self::PROTOCOL_HTTPS;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\Request::getUrl()
	 */
	public function getUrl(): Url {
		return $this->requestedUrl;
	}
	
	public function getCmdContextPath(): Path {
		return $this->cmdContextPath;
	}
	
	public function getCmdPath(): Path {
		return $this->cmdPath;
	}
	
	public function getN2nLocale(): N2nLocale {
		if ($this->n2nLocale === null) {
			throw new IncompleRequestException('No N2nLocale assigned to request.');
		}
		
		return $this->n2nLocale;
	}
	
	public function setN2nLocale(N2nLocale $n2nLocale) {
		$this->n2nLocale = $n2nLocale;
	}
	
	public function setAvailableN2nLocaleAliases(array $availableN2nLocaleAliases) {
		ArgUtils::valArray($availableN2nLocaleAliases, 'scalar');
		$this->availableN2nLocaleAliases = $availableN2nLocaleAliases;
	}
	
	public function getAvailableN2nLocaleAliases() {
		return $this->availableN2nLocaleAliases;
	}
	
	public function getN2nLocaleAlias($n2nLocale) {
		if (isset($this->availableN2nLocaleAliases[(string) $n2nLocale])) {
			return $this->availableN2nLocaleAliases[(string) $n2nLocale];
		}
		
		return N2nLocale::create($n2nLocale)->toHttpId();
	}
	
	public function getHostUrl() {
		return new Url($this->requestedUrl->getScheme(), $this->requestedUrl->getAuthority());
	}
	
	public function getProtocol(): string {
		if ($this->requestedUrl->hasScheme()) {
			return $this->requestedUrl->getScheme();
		}
		
		return self::PROTOCOL_HTTP;
	}

	public function getHostName(): string {
		return $this->requestedUrl->getAuthority()->getHost();
	}
	
	public function getPort() {
		return $this->requestedUrl->getAuthority()->getPort();
	}
	/**
	 * @param string $pathExt
	 * @param array $queryParams
	 * @param string $fragment
	 * @return string
	 */
	public function getContextPath(): Path {
		return $this->cmdContextPath;
	}
	/**
	 * @param string $path
	 * @param string $query
	 * @param string $fragment
	 * @return \n2n\util\uri\RelativeUrl
	 */
	public function extContext($path = null, $query = null, $fragment = null) {
		return $this->cmdContextPath->ext($path)->toUrl($query, $fragment);
	}
	
	
	public function getPath(): Path {
		return $this->requestedUrl->getPath();
	}
	
	public function getRelativeUrl() {
		return $this->requestedUrl->toRelativeUrl();
	}
	
	public function getQuery() {
		return $this->requestedUrl->getQuery();
	}
	
	public function getPostQuery() {
		return $this->postQuery;
	}
	
	public function getUploadDefinitions() {
		return $this->uploadDefinitions;
	}
	
	public function getSubsystemName() {
		if ($this->subsystem !== null) {
			return $this->subsystem->getName();
		}
		return null;
	}
		
	public function getSubsystem() {
		return $this->subsystem;
	}
	
	public function setSubsystem(Subsystem $subsystem = null) {
		$this->subsystem = $subsystem;
	}
	
	public function setAvailableSubsystems(array $subsystems) {
		$this->availableSubsystems = array();
		foreach ($subsystems as $subsystem) {
			$this->availableSubsystems[$subsystem->getName()] = $subsystem;
		}
	}
	
	public function getAvailableSubsystems() {
		return $this->availableSubsystems;
	}
	
	public function getAvailableSubsystemByName($name) {
		if (isset($this->availableSubsystems[$name])) {
			return $this->availableSubsystems[$name];
		}
		
		throw new UnknownSubsystemException('Unknown subystem name: ' . $name);
	}
	
	private $acceptRange;
	
	public function getAcceptRange(): AcceptRange {
		if ($this->acceptRange !== null) {
			return $this->acceptRange;
		}
		
		if (isset($this->serverVars['HTTP_ACCEPT'])) {
			return $this->acceptRange = AcceptRange::createFromStr($this->serverVars['HTTP_ACCEPT']);
		}
		
		return $this->acceptRange = new AcceptRange(array());
	}
	
// 	public function completeUrl($relativeUrl, $ssl, $subsystemName) {
// 		$protocol = null;
// 		if ($ssl === null) {
// 			$protocol = $this->getProtocol();
// 		} else {
// 			$protocol = ($ssl ? self::PROTOCOL_HTTPS : self::PROTOCOL_HTTP);
// 		}

// 		$hostName = null;
// 		if ($subsystemName === null) {
// 			$hostName = $this->getHostName();
// 		} else {			
// 			$subsystemDef = $this->getSubsystemByName($subsystemName);
// 			if (null !== ($ssHostName = $subsystemDef->getHostName())) {
// 				$hostName = $ssHostName;
// 			}
// 			if (null !== ($ssContextPath = $subsystemDef->getContextPath())) {
// 				throw new NotYetImplementedException();
// 			}
// 		}
		
// 		return new Url($protocol, $hostName, $relativeUrl); 
// 	}	

// 	public function completeUrlComponent($relativeUrl, $ssl, $subsystemName) {		
// 		$hostUrlRequired = false;
	
// 		$protocol = $this->getProtocol();
// 		if ($ssl !== null) {
// 			if ($ssl && !$this->isSsl()) {
// 				$hostUrlRequired = true;
// 				$protocol = Request::PROTOCOL_HTTPS;
// 			} else if (!$ssl && $this->isSsl()) {
// 				$hostUrlRequired = true;
// 				$protocol = Request::PROTOCOL_HTTP;
// 			}
// 		}
	
		
	
// 		if ($hostUrlRequired) {
// 			return new Url($protocol, $hostName, $relativeUrl);
// 		}
	
// 		return $relativeUrl;
// 	}
	
	public function getRemoteIp() {
		return $this->extractServerVar('REMOTE_ADDR');
	}
}
