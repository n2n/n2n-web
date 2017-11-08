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
use n2n\web\ui\SimpleBuildContext;
use n2n\impl\web\ui\view\html\HtmlUtils;

class HtmlUiPayload extends BufferedPayload {
	private $uiComponent;
	
	public function __construct($uiComponent) {
		$this->uiComponent = $uiComponent;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\BufferedPayload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		return HtmlUtils::contentsToHtml($this->uiComponent, new SimpleBuildContext());
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\Payload::prepareForResponse()
	 */
	public function prepareForResponse(Response $response) {
		$response->setHeader('Content-Type: text/html; charset=utf-8');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\payload\Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return 'Html Ui Payload';
	}
}
