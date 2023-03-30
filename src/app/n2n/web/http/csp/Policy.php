<?php

namespace n2n\web\http\csp;

use n2n\util\type\ArgUtils;

class Policy {

	/**
	 * @var PolicySource[] $sources
	 */
	private array $sources = [];

	function __construct(private PolicyDirective $directive, array $policySources = []) {
		ArgUtils::valArray($policySources, PolicySource::class);

		foreach ($policySources as $policySource) {
			$this->addSource($policySource);
		}
	}

	function getDirective(): PolicyDirective {
		return $this->directive;
	}

	function isEmpty(): bool {
		return empty($this->sources);
	}

	function addSource(PolicySource $policySource): static {
		$this->sources[$policySource->getValue()] = $policySource;
		return $this;
	}

	/**
	 * @param PolicySource[] $sources
	 * @return $this
	 */
	function addSources(array $sources): static {
		ArgUtils::valArray($sources, PolicySource::class);

		foreach ($sources as $source) {
			$this->addSource($source);
		}

		return $this;
	}

	function getSources(): array {
		return $this->sources;
	}

	function removeSource(PolicySource $policySource): static {
		unset($this->sources[$policySource->getValue()]);
		return $this;
	}

	function copy(): Policy {
		return new Policy($this->directive, $this->sources);
	}

	const SEPARATOR = ' ';

	function __toString(): string {
		return $this->directive->value . self::SEPARATOR . implode(self::SEPARATOR, array_keys($this->sources));
	}

	/**
	 * @param string $str
	 * @return Policy
	 * @throws InvalidCspException
	 */
	static function parse(string $str): Policy {
		$parts = preg_split('/\s+/', trim($str));

		try {
			$directive = PolicyDirective::tryFrom(array_shift($parts));

			if ($directive === null) {
				throw new InvalidCspException('Invalid content security directive: ' . $directive);
			}

			return new Policy($directive, array_map(fn($s) => new PolicySource($s), $parts));
		} catch (InvalidCspException $e) {
			throw new InvalidCspException('Invalid content security policy: ' . $str, previous: $e);
		}
	}
}