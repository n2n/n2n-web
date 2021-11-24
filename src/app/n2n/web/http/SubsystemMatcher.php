<?php

namespace n2n\web\http;

use n2n\util\type\ArgUtils;
use n2n\core\N2nLocaleNotFoundException;

class SubsystemMatcher {
	private array $n2nLocales;

	public function __construct(private string $name, private ?string $hostName, private ?string $contextPath,
			array $n2nLocales) {
		$this->name = $name;
		$this->hostName = $hostName;
		$this->contextPath = $contextPath;
		$this->setN2nLocales($n2nLocales);
	}

	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getHostName() {
		return $this->hostName;
	}

	/**
	 * @param string|null $hostName
	 */
	function setHostName(?string $hostName) {
		$this->hostName = $hostName;
	}

	/**
	 * @return string|null
	 */
	public function getContextPath() {
		return $this->contextPath;
	}

	/**
	 * @param string|null $contextPath
	 */
	function setContextPath(?string $contextPath) {
		$this->contextPath = $contextPath;
	}

	/**
	 * @return N2nLocale[]
	 */
	function getN2nLocales() {
		return $this->n2nLocales;
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

	/**
	 * @param string $id
	 * @return bool
	 */
	function containsLanguageId(string $id) {
		foreach ($this->n2nLocales as $n2NLocale) {
			if ($n2NLocale->getLanguageId() === $id) {
				return true;
			}
		}

		return false;
	}
}