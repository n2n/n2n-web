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
namespace n2n\web\http\payload\impl;

use n2n\web\http\payload\BufferedPayload;
use n2n\web\http\Response;
use n2n\util\type\ArgUtils;

class XmlPayload extends BufferedPayload { 
	private $simpleXmlElement;
	
	/**
	 * @param \SimpleXMLElement|string $data
	 */
	public function __construct($data)  {
		if ($data instanceof \SimpleXMLElement) {
			$this->simpleXmlElement = $data;
			return;
		}
		
		if (is_string($data)) {
			try {
				$this->simpleXmlElement = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
			} catch (\Error $e) {
				throw new \InvalidArgumentException('Invalid xml string.', 0, $e); 
			}
		}
		
		ArgUtils::valType($data, ['string', \SimpleXMLElement::class], 'data');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\Payload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		return $this->simpleXmlElement->asXML();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\Payload::prepareForResponse()
	 */
	public function prepareForResponse(Response $response) {
		$response->setHeader('Content-type: text/xml');
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return 'XML Response';
	}	
}
