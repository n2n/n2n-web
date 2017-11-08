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
namespace n2n\web\http\payload;

use n2n\web\http\Response;

/**
 * <p>A response object represents content which can be sent over {@see Response::send()} to the client. This could be a 
 * 	html document, a json string or a file for example.</p>
 * 
 * <p>
 * 	There are to kinds of response object; bufferable and not bufferable, which both have their own advantages. See 
 * {@see::isBufferable()} for more information. Implementations are started best by extending 
 * {@see BufferedPayload} or {@see ResourcePayload}
 * </p>
 */
interface Payload {
	
	/**
	 * <p>This method gets called before the respone object is flushed to the client and is supposed to make necessary 
	 * changes to the http header of the {@see Response} (e.g. Content-Type).</p>
	 * 
	 * <p>Example implementation:</p>
	 * <pre>
	 * 	public function prepareForResponse(Response $response) {
	 *		$response->setHeader('Content-Type: text/html; charset=utf-8');
	 *	}
	 * </pre>
	 * 
	 * @param Response $response
	 */
	public function prepareForResponse(Response $response);
	
	/**
	 * Returns a string which describes this object. This string is mainly used to be displayed in error messages.
	 * @return string
	 */
	public function toKownPayloadString(): string;

	/**
	 * <p>Returns true if the content of this {@see Payload} can be buffered and returned by 
	 * 	{@see self::getBufferedContents()}. This would be false if this {@see Payload} contained a large file which 
	 * 	can not be buffered due to lack of memory.</p> 
	 * 
	 * <p>
	 * 	Implemation examples:
	 * 	<pre>
	 * 		<ul>
	 * 			<li>{@see \n2n\web\http\payload\Payload} (bufferable)</li>
	 * 			<li>{@see \n2n\io\managed\File} (not bufferable)</li>
	 * 		</ul>
	 * 	</pre>
	 * </p>
	 *  
	 * @return bool
	 */
	public function isBufferable(): bool;
	
	/**
	 * Returns the buffered content of this response object. See {@see self::isBufferable()} for more information.
	 * @return string
	 * @throws \n2n\util\ex\IllegalStateException if {@see self::isBufferable()} returns false.
	 */
	public function getBufferedContents(): string;
	
	/**
	 * <p>Flushes the response object directly to the client. See {@see self::isBufferable()} for more information.</p>
	 * 
	 * <p>
	 *	Implementation example:
	 *	<pre>
	 * 		public function out() {
	 * 			IoUtils::readfile($this->fileFsPath);
	 *		}
	 *	</pre>
	 * </p>
	 * 
	 * @return string
	 * @throws \n2n\util\ex\IllegalStateException if {@see self::isBufferable()} returns true.
	 */
	public function responseOut();
	
	/**
	 * <p>
	 * 	Etag is sort of a hash of the content of this response object. It will be used to determine if the the 
	 * 	response object has changed since the last request. If not n2n will send the http status 304 Not Modified 
	 * 	which reduces traffice.
	 * </p>
	 * 
	 * <p>
	 * 	<strong>Notice:</strong> If {@see self::isBufferable()} is true, n2n is able to calcualte an  
	 * 	etag on its own and this method <strong>will not</strong> be called!
	 * </p> 
	 * 
	 * <p>
	 * 	<strong>Attention:</strong> Please make sure to return a proper etag according to the   
	 * 	{@link https://tools.ietf.org/html/rfc7232 http standard}
	 * </p>
	 * 
	 * @return string|null
	 * @throws \n2n\util\ex\IllegalStateException if {@see self::isBufferable()} returns true.
	 */
	public function getEtag();
	
	/**
	 * <p>Can be used as alternative to calculating an etag. Similar to etag the returned DateTime will be used to 
	 * 	determine if the response object has changed since the last request. If not a http status 304 Not Modified  will me 
	 * 	sent which reduces traffice.</p>
	 * 
	 * <p>Also see {@see self::getEtag()}</p>
	 * 
	 * <p>
	 * 	<strong>Notice:</strong> If {@see self::isBufferable()} is true, n2n already uses an etag to determine if the 
	 * 	if the response object has changed. So this method will not be called.
	 * </p> 
	 * 
	 * @return \DateTime|null The last time this response object has been changed or null if the DateTime shall not be
	 * used to determine if status code has bee.
	 * @throws \n2n\util\ex\IllegalStateException if {@see self::isBufferable()} returns true.
	 */
	public function getLastModified();
}
