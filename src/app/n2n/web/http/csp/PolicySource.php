<?php

namespace n2n\web\http\csp;

use n2n\util\uri\Url;

class PolicySource {

	function __construct(private string $value) {
		if (0 === preg_match('/^[^\'\s]+$/', $this->value)
				&& 0 === preg_match('/^\'[^\'\s]+\'$/', $this->value)) {
			throw new InvalidCspException('Invalid content security policy source: ' . $this->value);
		}
	}

	function getValue(): string {
		return $this->value;
	}

	function equals(mixed $arg): bool {
		return $arg instanceof PolicySource && $this->value === $arg->getValue();
	}

	static function createKeyword(PolicySourceKeyword $keyword): PolicySource {
		return new PolicySource('\'' . $keyword->value . '\'');
	}

	static function createUrl(Url $url): PolicySource {
		return new PolicySource((string) $url);
	}

	static function createHash(string $content): PolicySource {
		return new PolicySource('\'sha256-' . hash('sha256', $content) . '\'');
	}

}