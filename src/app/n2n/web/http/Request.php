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
use n2n\util\dev\Version;

interface Request {
	const PROTOCOL_HTTP = 'http';
	const PROTOCOL_HTTPS = 'https';
	
	/**
	 * @return string 
	 */
	public function getMethod(): int;
	
	/**
	 * @return string
	 */
	public function getOrigMethodName(): string;

	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getUrl(): Url;
	
	/**
	 * @param string $name
	 * @return string header, null if header not available
	 */
	public function getHeader($name): ?string;
	
	/**
	 * @return \n2n\util\uri\Path
	 */
	public function getCmdContextPath(): Path;
	
	/**
	 * @return \n2n\util\uri\Path 
	 */
	public function getCmdPath(): Path;
	
	/**
	 * @return \n2n\web\http\Subsystem
	 */
	public function getSubsystem();
	
	/**
	 * @param Subsystem $subsystem
	 */
	public function setSubsystem(Subsystem $subsystem = null);
	
	/**
	 * @return N2nLocale 
	 */
	public function getN2nLocale(): N2nLocale;
	
	/**
	 * @param N2nLocale $n2nLocale
	 */
	public function setN2nLocale(N2nLocale $n2nLocale);
	
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getHostUrl();
	/**
	 * @return string 
	 */
	public function getProtocol(): string;
	
	/**
	 * @return Version
	 */
	public function getProtocolVersion(): Version;
	
	/**
	 * @return string 
	 */
	public function getHostName(): string;
	/**
	 * @return int 
	 */
	public function getPort();
	/**
	 *
	 * @return \n2n\util\uri\Path
	 */
	public function getContextPath(): Path;

	/** 
	 * @return \n2n\util\uri\Path
	 */
	public function getPath(): Path;
	/**
	 * @return \n2n\util\uri\Url
	 */
	public function getRelativeUrl();
	/**
	 * @return \n2n\util\uri\Query
	 */
	public function getQuery();
	/**
	 * @return \n2n\util\uri\Query
	 */
	public function getPostQuery();
	
	/**
	 * @return string
	 */
	public function getBody(): string;
	/**
	 * @return UploadDefinition[] 
	 */
	public function getUploadDefinitions();
		
	/**
	 * @return string 
	 */
	public function getRemoteIp();
	
	/**
	 * @return AcceptRange
	 */
	public function getAcceptRange(): AcceptRange;
}
