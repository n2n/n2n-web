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

class N2nLocaleFormat {
	private $aliasN2nLocales;
	
	public function __construct(array $aliasN2nLocales) {
		$this->aliasN2nLocales = $aliasN2nLocales;
	}
	
	public function formatHttpId(N2nLocale $n2nLocale): string {
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
	public function parseN2nLocale(string $httpId): N2nLocale {
		if (isset($this->aliasN2nLocales[$httpId])) {
			return $this->aliasN2nLocales[$httpId];
		}
		
		return N2nLocale::createFromHttpId($httpId);
	}
}
