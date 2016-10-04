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
use n2n\l10n\IllegalN2nLocaleFormatException;

class N2nLocaleFormat {
	private $aliasN2nLocales;
	
	public function __construct(array $aliasN2nLocales) {
		$this->aliasN2nLocales = $aliasN2nLocales;
	}
		
	/**
	 * @param N2nLocale $n2nLocale
	 * @return boolean
	 */
	public function getAliasForN2nLocale(N2nLocale $n2nLocale) {
		foreach ($this->aliasN2nLocales as $httpId => $n2nLocale) {
			if ($n2nLocale->equals($n2nLocale)) return $httpId;
		}
		
		return null;
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function formatHttpId(N2nLocale $n2nLocale) {
		foreach ($this->aliasN2nLocales as $alias => $aliasN2nLocale) {
			if ($n2nLocale->equals($aliasN2nLocale)) return $alias;
		}
		
		return $n2nLocale->toHttpId();
	}
	
	/**
	 * @param string $httpId
	 * @return \n2n\l10n\N2nLocale
	 * @throws IllegalN2nLocaleFormatException
	 */
	public function parseN2nLocale(string $httpId, bool $lenient = false): N2nLocale {
		if (isset($this->aliasN2nLocales[$httpId])) {
			return $this->aliasN2nLocales[$httpId];
		}
		
		$n2nLocale = N2nLocale::createFromHttpId($httpId);
		$alias = $this->getAliasForN2nLocale($n2nLocale);
		if (null === $alias) {
			return $n2nLocale;
		}
		
		throw new IllegalN2nLocaleFormatException('Invalid http locale id \'' . $httpId 
				. '\'. For N2nLocale ' . $n2nLocale . ' is only the alias \'' . $alias 
				. '\' acceptable.');
	}
}