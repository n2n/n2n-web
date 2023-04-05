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
use n2n\l10n\UnknownN2nLocaleException;
use n2n\util\type\ArgUtils;

class Supersystem {
	private array $n2nLocales = [];

	/**
	 * @param N2nLocale[] $n2nLocales
	 * @param string[] $responseHeaders
	 */
	public function __construct(array $n2nLocales = [], private array $responseHeaders = []) {
		$this->setN2nLocales($n2nLocales);
	}
	
	/**
	 * @return N2nLocale[]
	 */
	public function getN2nLocales() {
		return $this->n2nLocales;
	}
	
	/**
	 * @param string $id
	 * @throws UnknownN2nLocaleException
	 * @return N2nLocale
	 */
	public function getN2nLocaleById(string $id) {
		if (isset($this->n2nLocales[$id])) {
			return $this->n2nLocales[$id];
		}
		
		throw new UnknownN2nLocaleException('No N2nLocale found with id: ' . $id);
	}

	/**
	 * @param N2nLocale[] $n2nLocales
	 */
	function setN2nLocales(array $n2nLocales) {
		ArgUtils::valArray($n2nLocales, N2nLocale::class);

		$this->n2nLocales = array();
		foreach ($n2nLocales as $n2nLocale) {
			$this->addN2nLocale($n2nLocale);
		}
	}

	/**
	 * @param N2nLocale $n2nLocale
	 */
	function addN2nLocale(N2nLocale $n2nLocale) {
		$this->n2nLocales[$n2nLocale->getId()] = $n2nLocale;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	function containsN2nLocaleId(string $id) {
		return isset($this->n2nLocales[$id]);
	}
	
	function setResponseHeaders(array $responseHeaders) {
		$this->responseHeaders = $responseHeaders;
	}
	
	function getResponseHeaders() {
		return $this->responseHeaders;
	}
}
