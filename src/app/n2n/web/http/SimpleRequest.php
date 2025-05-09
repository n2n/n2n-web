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
use n2n\util\type\ArgUtils;
use n2n\util\dev\Version;
use n2n\util\ex\IllegalStateException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class SimpleRequest implements Request {
	const PROTOCOL_VERSION_SEPARATOR = '/';
	
//	private $serverVars;
	
	private $method;
	private $origMethodName;
	private array $headers = [];
	private $contextUrl;
	private $cmdContextPath;
	private $cmdUrl;
	private $postQuery;
	private ?string $body = null;
	private $uploadDefinitions = [];
	private $n2nLocale;
	private $availableN2nLocaleAliases = array();
	private $availableSubsystems = array();
	private $assetsDirName;
	private $protocolVersion;
	private $subsystem;
	private $remoteIp;
	
	public function __construct(Url $contextUrl) {
		ArgUtils::assertTrue($contextUrl->getAuthority()->getHost()  !== null && $contextUrl->getQuery()->isEmpty()
						&& $contextUrl->getFragment() === null,
				'Context url can not have a query or fragment and must have a host.');
		$this->method = Method::GET;
		$this->contextUrl = $contextUrl;
		$this->cmdUrl = new Url();
		
		$this->postQuery = new Query([]);
	}
	
	function setMethod(int $method) {
		ArgUtils::valEnum($method, Method::getAll());
		$this->method = $method;
		return $method;
	}
	
	function setCmdUrl(Url $cmdUrl) {
		ArgUtils::assertTrue($cmdUrl->isRelative(), 'Cmd url must be relative.');
		$this->cmdUrl = $cmdUrl;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getMethod()
	 */
	function getMethod(): int {
		return $this->method;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getOrigMethodName()
	 */
	public function getOrigMethodName(): string {
		return $this->origMethodName ?? Method::toString($this->method);
	}

	function setOrigMethodName(?string $origMethodName) {
		$this->origMethodName = $origMethodName;
	}
	
	/**
	 * 
	 * @param string[] $headers
	 */
	function setHeaders(array $headers) {
		$this->headers = $headers;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 */
	function setHeader(string $name, string $value) {
		$this->headers[$name] = $value;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getHeader()
	 */
	public function getHeader(string $name): ?string {
		return $this->headers[$name] ?? null;
	}
	/**
	 * @return boolean
	 */
	public function isSsl() {
		return $this->contextUrl->getScheme() == self::PROTOCOL_HTTPS;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getUrl()
	 */
	public function getUrl(): Url {
		return $this->contextUrl->ext($this->cmdUrl);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getCmdContextPath()
	 */
	public function getCmdContextPath(): Path {
		return $this->contextUrl->getPath();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getCmdPath()
	 */
	public function getCmdPath(): Path {
		return $this->cmdUrl->getPath();
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
	public function getHostUrl(): Url {
		return new Url($this->contextUrl->getScheme(), $this->contextUrl->getAuthority());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getProtocol()
	 */
	public function getProtocol(): string {
		if ($this->contextUrl->hasScheme()) {
			return $this->contextUrl->getScheme();
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
		return $this->contextUrl->getAuthority()->getHost();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getPort()
	 */
	public function getPort(): int {
		return $this->contextUrl->getAuthority()->getPort();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getContextPath()
	 */
	public function getContextPath(): Path {
		return $this->contextUrl->getPath();
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
		return $this->contextUrl->ext($this->cmdUrl->getPath())->getPath();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getRelativeUrl()
	 */
	public function getRelativeUrl(): Url {
		return $this->contextUrl->toRelativeUrl()->ext($this->cmdUrl);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getQuery()
	 */
	public function getQuery(): Query {
		return $this->cmdUrl->getQuery();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getPostQuery()
	 */
	public function getPostQuery(): Query {
		return $this->postQuery;
	}

	function setPostQuery(Query $postQuery): void {
		$this->postQuery = $postQuery;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getUploadDefinitions()
	 */
	public function getUploadDefinitions(): array {
		return $this->uploadDefinitions;
	}
	
	/**
	 * @param array $uploadDefinitions
	 */
	function setUploadDefinitions(array $uploadDefinitions) {
		ArgUtils::valArray($uploadDefinitions, UploadDefinition::class);
		$this->uploadDefinitions = $uploadDefinitions;
	}
	
//	public function getSubsystemName() {
//		if ($this->subsystem !== null) {
//			return $this->subsystem->getName();
//		}
//		return null;
//	}
//
//	/**
//	 * {@inheritDoc}
//	 * @see \n2n\web\http\Request::getSubsystem()
//	 */
//	public function getSubsystem(): ?Subsystem {
//		return $this->subsystem;
//	}
//
//	/**
//	 * {@inheritDoc}
//	 * @see \n2n\web\http\Request::setSubsystem()
//	 */
//	public function setSubsystem(?Subsystem $subsystem) {
//		$this->subsystem = $subsystem;
//	}
//
//	public function setAvailableSubsystems(array $subsystems) {
//		$this->availableSubsystems = array();
//		foreach ($subsystems as $subsystem) {
//			$this->availableSubsystems[$subsystem->getName()] = $subsystem;
//		}
//	}
//
//	function getAvailableSubsystems() {
//		return $this->availableSubsystems;
//	}
//
//	function getAvailableSubsystemByName($name) {
//		if (isset($this->availableSubsystems[$name])) {
//			return $this->availableSubsystems[$name];
//		}
//
//		throw new UnknownSubsystemException('Unknown subystem name: ' . $name);
//	}
	
	private $acceptRange;
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getAcceptRange()
	 */
	public function getAcceptRange(): AcceptRange {
		if ($this->acceptRange !== null) {
			return $this->acceptRange;
		}
		
		return $this->acceptRange = new AcceptRange(array());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getRemoteIp()
	 */
	function getRemoteIp(): string {
		if ($this->remoteIp !== null) {
			return $this->remoteIp;
		}
		
		throw new IllegalStateException('Remote ip not defined.');
	}
	
	function setRemoteIp(string $remoteIp) {
		$this->remoteIp = $remoteIp;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\Request::getBody()
	 */
	function getBody(): string {
		if ($this->body !== null) {
			return $this->body;
		}
		
		throw new IllegalStateException('Request contains no body.');
	}
	
	/**
	 * @param string $body
	 */
	function setBody(?string $body) {
		$this->body = $body;
	}

	private $bestN2nLocale = null;

	/**
	 * @param N2nLocale|null $n2NLocale
	 */
	function setBestN2nLocale(?N2nLocale  $n2NLocale) {
		$this->bestN2nLocale = $n2NLocale;
	}

	/**
	 * @return N2nLocale|null
	 */
	function getBestN2nLocale() {
		return $this->bestN2nLocale;
	}

	public function detectBestN2nLocale(array $n2nLocales): N2nLocale {
		return $this->bestN2nLocale ?? N2nLocale::getDefault();
	}

	/**
	 * @param ServerRequestFactoryInterface $factory
	 * @param UploadedFileFactoryInterface|null $uploadedFileFactory
	 * @param StreamFactoryInterface|null $streamFactory
	 * @return ServerRequestInterface
	 */
	function toPsr(ServerRequestFactoryInterface $factory, ?UploadedFileFactoryInterface $uploadedFileFactory = null,
			?StreamFactoryInterface $streamFactory = null): ServerRequestInterface {
		$request = $factory->createServerRequest(Method::toString($this->getMethod()), (string) $this->getUrl(), []);

		$uploadedFiles = [];
		if ($uploadedFileFactory !== null && $streamFactory !== null) {
			foreach ($this->getUploadDefinitions() as $uploadDefinition) {
				$uploadedFiles[] = $uploadDefinition->toPsr($uploadedFileFactory, $streamFactory);
			}
		}

		$request->withUploadedFiles($uploadedFiles);

		return $request;
	}
}

// $rawInput = fopen('php://input', 'r');
// $tempStream = fopen('php://temp', 'r+');
// stream_copy_to_stream($rawInput, $tempStream);
// rewind($tempStream);

// return $tempStream;
