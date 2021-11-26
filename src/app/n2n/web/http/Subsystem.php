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
	 * @var SubsystemRule[]
	 */
	private array $rules = [];

	public function __construct(private string $name) {
	}

	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * @return SubsystemRule[]
	 */
	function getRules() {
		return $this->rules;
	}

	/**
	 * @param string $name
	 * @param string|null $hostName
	 * @param string|null $contextPath
	 * @param array $n2nLocales
	 * @return Subsystem
	 */
	function createRule(string $name, ?string $hostName, ?string $contextPath, array $n2nLocales) {
		if (isset($this->rules[$name])) {
			throw new DuplicateElementException('Subsystem rule with name already exists: ' . $name);
		}

		$this->rules[$name] = new SubsystemRule($this, $name, $hostName, $contextPath, $n2nLocales);
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function containsRuleName(string $name) {
		return isset($this->rules[$name]);
	}

	/**
	 * @param string $name
	 * @return Subsystem
	 */
	function removeRuleByName(string $name) {
		unset($this->rules[$name]);
		return $this;
	}

	/**
	 * @param string $name
	 */
	function getRuleByName(string $name) {
		if ($this->rules[$name]) {
			return $this->rules[$name];
		}

		return new UnknownSubsystemException('Subsystem contains no rule with name: ' + $name);
	}

	/**
	 * @deprecated
	 * @return mixed
	 */
	public function getHostName() {
		IllegalStateException::assertTrue(count($this->rules) === 1, 'Multiple rules.');
		return current($this->rules)->getHostName();
	}

	/**
	 * @deprecated
	 * @return string|null
	 */
	public function getContextPath() {
		IllegalStateException::assertTrue(count($this->rules) === 1, 'Multiple rules.');
		return current($this->rules)->getContextPath();
	}

	/**
	 * @return N2nLocale[]
	 */
	function getN2nLocales()  {
		$n2nLocales = [];
		foreach ($this->rules as $matcher) {
			$n2nLocales = array_merge($n2nLocales, $matcher->getN2nLocales());
		}
		return $n2nLocales;
	}

	function getRuleByN2nLocale(N2nLocale $n2NLocale) {
		foreach ($this->rules as $matcher) {
			if ($matcher->containsN2nLocaleId($n2NLocale->getId())) {
				return $matcher;
			}
		}

		throw new UnknownSubsystemException('Subsystem contains no SubsystemRule with locale: ' . $n2NLocale);
	}

	/**
	 * @param N2nLocale $n2NLocale
	 * @return SubsystemRule|null
	 */
	function findBestRuleByN2nLocale(N2nLocale $n2NLocale) {
		if (count($this->rules) === 1) {
			return ArrayUtils::current($this->rules);
		}

		$fallbackRule = null;
		foreach ($this->rules as $rule) {
			if ($rule->containsN2nLocaleId($n2NLocale->getId())) {
				return $rule;
			}

			if ($fallbackRule === null || $rule->containsLanguageId($n2NLocale->getLanguageId())) {
				$fallbackRule = $rule;
			}
		}

		return $fallbackRule;
	}
}
