<?php

namespace n2n\web\http;

use n2n\l10n\N2nLocale;
use n2n\util\col\ArrayUtils;

class HttpSystemContext {
	function __construct(public Supersystem $supersystem, public ?SubsystemRule $subsystemRule) {

	}


	public $mainN2nLocale {
		get {
			$mainN2nLocale = ArrayUtils::first($this->contextN2nLocales);
			if ($mainN2nLocale !== null) {
				return $mainN2nLocale;
			}

			return N2nLocale::getDefault();
		}
	}

	function containsContextN2nLocale(N2nLocale $n2nLocale): bool {
		$n2nLocaleId = $n2nLocale->getId();
		if ($this->supersystem->containsN2nLocaleId($n2nLocaleId)) {
			return true;
		}

		$subsystemRule = $this->subsystemRule;
		if ($subsystemRule !== null && $subsystemRule->containsN2nLocaleId($n2nLocaleId)) {
			return true;
		}

		return false;
	}

	/**
	 * @return N2nLocale[]
	 */
	public array $contextN2nLocales {
		get {
			$contextN2nLocales = $this->supersystem->getN2nLocales();
			if ($this->subsystemRule !== null) {
				return array_merge($contextN2nLocales, $this->subsystemRule->getN2nLocales());
			}
			return $contextN2nLocales;
		}
	}
}