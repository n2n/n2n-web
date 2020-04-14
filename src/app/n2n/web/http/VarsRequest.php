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
use n2n\util\uri\Path;
use n2n\util\uri\Url;
use n2n\util\uri\Query;
use n2n\util\uri\Authority;
use n2n\util\type\ArgUtils;
use n2n\util\dev\Version;
use n2n\io\IoUtils;

class VarsRequest implements Request {
	const PROTOCOL_VERSION_SEPARATOR = '/';
	
	private $serverVars;
	
	private $method;
	private $origMethodName;
	private $realMethod;
	private $requestedUrl;
	private $cmdContextPath;
	private $cmdPath;
	private $postQuery;
	private $uploadDefinitions;
	private $n2nLocale;
	private $availableN2nLocaleAliases = array();
	private $availableSubsystems = array();
	private $assetsDirName;
	private $protocolVersion;
	
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
		
		$this->origMethodName = mb_strtoupper($this->extractServerVar('REQUEST_METHOD'));
		try {
			$this->method = Method::createFromString($this->origMethodName);
		} catch (\InvalidArgumentException $e) {
			$this->method = Method::GET;
		}
		
		$this->requestedUrl = new Url($protocol, Authority::create($hostName), 
				$this->cmdContextPath->ext($this->cmdPath), new Query($getVars));		
	}	
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getMethod()
	 */
	public function getMethod(): int {
		return $this->method;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getOrigMethodName()
	 */
	public function getOrigMethodName(): string {
		return $this->origMethodName;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getHeader()
	 */
	public function getHeader($name): ?string {
		$varKey = 'HTTP_' . str_replace('-', '_', mb_strtoupper($name));
		if (isset($this->serverVars[$varKey])) {
			return $this->serverVars[$varKey];
		}
		
		if (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			if (isset($requestHeaders[$name])) {
				return trim($requestHeaders[$name]);
			}
		}
		
		if (function_exists('getallheaders')) {
			$requestHeaders = getallheaders();
			if (isset($requestHeaders[$name])) {
				return trim($requestHeaders[$name]);
			}
		}
		
		return null;
	}
	/**
	 * @return boolean
	 */
	public function isSsl() {
		return $this->requestedUrl->getScheme() == self::PROTOCOL_HTTPS;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getUrl()
	 */
	public function getUrl(): Url {
		return $this->requestedUrl;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getCmdContextPath()
	 */
	public function getCmdContextPath(): Path {
		return $this->cmdContextPath;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getCmdPath()
	 */
	public function getCmdPath(): Path {
		return $this->cmdPath;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getN2nLocale()
	 */
	public function getN2nLocale(): N2nLocale {
		if ($this->n2nLocale === null) {
			throw new IncompleRequestException('No N2nLocale assigned to request.');
		}
		
		return $this->n2nLocale;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::setN2nLocale()
	 */
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
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getHostUrl()
	 */
	public function getHostUrl() {
		return new Url($this->requestedUrl->getScheme(), $this->requestedUrl->getAuthority());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getProtocol()
	 */
	public function getProtocol(): string {
		if ($this->requestedUrl->hasScheme()) {
			return $this->requestedUrl->getScheme();
		}
		
		return self::PROTOCOL_HTTP;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getProtocolVersion()
	 */
	public function getProtocolVersion(): Version {
		if ($this->protocolVersion !== null) {
			return $this->protocolVersion;
		}
		
		$parts = explode(self::PROTOCOL_VERSION_SEPARATOR, $this->getProtocol(), 2);
		
		if (isset($parts[1])) {
			return $this->protocolVersion = Version::create($parts[1]);
		}
		
		return $this->protocolVersion = new Version([1]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getHostName()
	 */
	public function getHostName(): string {
		return $this->requestedUrl->getAuthority()->getHost();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getPort()
	 */
	public function getPort() {
		return $this->requestedUrl->getAuthority()->getPort();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getContextPath()
	 */
	public function getContextPath(): Path {
		return $this->cmdContextPath;
	}
	
// 	/**
// 	 * @param string $path
// 	 * @param string $query
// 	 * @param string $fragment
// 	 * @return Url
// 	 */
// 	public function extContext($path = null, $query = null, $fragment = null) {
// 		return $this->cmdContextPath->ext($path)->toUrl($query, $fragment);
// 	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getPath()
	 */
	public function getPath(): Path {
		return $this->requestedUrl->getPath();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getRelativeUrl()
	 */
	public function getRelativeUrl() {
		return $this->requestedUrl->toRelativeUrl();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getQuery()
	 */
	public function getQuery() {
		return $this->requestedUrl->getQuery();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getPostQuery()
	 */
	public function getPostQuery() {
		return $this->postQuery;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getUploadDefinitions()
	 */
	public function getUploadDefinitions() {
		return $this->uploadDefinitions;
	}
	
	public function getSubsystemName() {
		if ($this->subsystem !== null) {
			return $this->subsystem->getName();
		}
		return null;
	}
		
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getSubsystem()
	 */
	public function getSubsystem() {
		return $this->subsystem;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::setSubsystem()
	 */
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
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getAcceptRange()
	 */
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
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getRemoteIp()
	 */
	public function getRemoteIp() {
		return $this->extractServerVar('REMOTE_ADDR');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getBody()
	 */
	public function getBody(): string {
		return IoUtils::getContents('php://input');
	}
}

// $rawInput = fopen('php://input', 'r');
// $tempStream = fopen('php://temp', 'r+');
// stream_copy_to_stream($rawInput, $tempStream);
// rewind($tempStream);

// return $tempStream;
