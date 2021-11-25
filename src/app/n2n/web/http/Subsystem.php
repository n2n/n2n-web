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

use n2n\util\type\ArgUtils;
use n2n\util\ex\IllegalStateException;
use n2n\l10n\N2nLocale;
use n2n\util\col\ArrayUtils;

class Subsystem {
	/**
	 * @var SubsystemMatcher[]
	 */
	private array $matchers;

	public function __construct(private string $name, array $matchers = []) {
		$this->setMatchers($matchers);
	}

	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * @return SubsystemMatcher[]
	 */
	function getMatchers() {
		return $this->matchers;
	}

	function setMatchers(array $matchers) {
		ArgUtils::valArray($matchers, SubsystemMatcher::class);
		$this->matchers = [];
		foreach ($matchers as $matcher) {
			$this->matchers[$matcher->getName()] = $matcher;
		}
	}

	function addMatcher(SubsystemMatcher $matcher) {
		$this->matchers[$matcher->getName()] = $matcher;
	}

	/**
	 * @deprecated
	 * @return mixed
	 */
	public function getHostName() {
		IllegalStateException::assertTrue(count($this->matchers) === 1, 'Multiple matchers.');
		return current($this->matchers)->getHostName();
	}

	/**
	 * @deprecated
	 * @return string|null
	 */
	public function getContextPath() {
		IllegalStateException::assertTrue(count($this->matchers) === 1, 'Multiple matchers.');
		return current($this->matchers)->getContextPath();
	}

	/**
	 * @return N2nLocale[]
	 */
	function getN2nLocales()  {
		$n2nLocales = [];
		foreach ($this->matchers as $matcher) {
			$n2nLocales = array_merge($n2nLocales, $matcher->getN2nLocales());
		}
		return $n2nLocales;
	}

	function getMatcherByN2nLocale(N2nLocale $n2NLocale) {
		foreach ($this->matchers as $matcher) {
			if ($matcher->containsN2nLocaleId($n2NLocale->getId())) {
				return $matcher;
			}
		}

		throw new UnknownSubsystemException('Subsystem contains no SubsystemMatcher with locale: ' . $n2NLocale);
	}

	/**
	 * @param N2nLocale $n2NLocale
	 * @return SubsystemMatcher|null
	 */
	function findBestMatcherByN2nLocale(N2nLocale $n2NLocale) {
		if (count($this->matchers) === 1) {
			return ArrayUtils::current($this->matchers);
		}

		$fallbackMatcher = null;
		foreach ($this->matchers as $matcher) {
			if ($matcher->containsN2nLocaleId($n2NLocale->getId())) {
				return $matcher;
			}

			if ($fallbackMatcher === null || $matcher->containsLanguageId($n2NLocale->getLanguageId())) {
				$fallbackMatcher = $matcher;
			}
		}

		return $fallbackMatcher;
	}
}
