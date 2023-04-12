<?php

namespace n2n\web\http\csp;

use n2n\util\type\ArgUtils;
use n2n\web\http\Response;

class ContentSecurityPolicy {

	const HEADER_NAME = 'Content-Security-Policy';
	const POLICY_SEPARATOR = ';';

	private array $policies = [];

	function __construct(array $policies = []) {
		ArgUtils::valArray($policies, Policy::class);
		foreach ($policies as $policy) {
			$this->addPolicy($policy);
		}
	}

	function getPolicy(PolicyDirective $directive): Policy {
		return $this->policies[$directive->value] ?? $this->policies[$directive->value] = new Policy($directive);
	}

	/**
	 * @return Policy[]
	 */
	function getPolicies(): array {
		return $this->policies;
	}

	function addPolicy(Policy $policy): static {
		$this->policies[$policy->getDirective()->value] = $policy;
		return $this;
	}

	function addSource(PolicyDirective $directive, PolicySource $policySource): static {
		$this->getPolicy($directive)->addSource($policySource);
		return $this;
	}

	function isEmpty(): bool {
		if (empty($this->policies)) {
			return true;
		}

		foreach ($this->policies as $policy) {
			if (!$policy->isEmpty()) {
				return false;
			}
		}

		return true;
	}

	function append(ContentSecurityPolicy $contentSecurityPolicy): static {
		foreach ($contentSecurityPolicy->getPolicies() as $policy) {
			$this->getPolicy($policy->getDirective())->addSources($policy->getSources());
		}

		return $this;
	}

	function applyHeaders(Response $response): void {
		if ($this->isEmpty()) {
			return;
		}

		$existingHeaderValues = $response->getHeaderValues(self::HEADER_NAME);
		if (empty($existingHeaderValues)) {
			$response->setHeader($this->toHeaderStr());
			return;
		}

		$csp = null;
		foreach ($existingHeaderValues as $value) {
			$existingCsp = ContentSecurityPolicy::parse($value);

			if ($csp === null) {
				$csp = $existingCsp;
			} else {
				$csp->append($existingCsp);
			}
		}
		$csp->append($this);

		$response->removeHeader(self::HEADER_NAME);
		$response->setHeader($csp->toHeaderStr(), true);
	}

	function toHeaderStr(): string {
		return self::HEADER_NAME . ': ' . implode(self::POLICY_SEPARATOR . ' ', $this->getPolicies());
	}

	function copy(): ContentSecurityPolicy {
		return new ContentSecurityPolicy(array_map(fn (Policy $p) => $p->copy(), $this->getPolicies()));
	}

	static function parse(string $str): ContentSecurityPolicy {
		$parts = preg_split('/\s*;\s*/', trim($str));

		return new ContentSecurityPolicy(array_map(fn ($p) => Policy::parse($p),
				array_filter($parts, fn ($p) => !empty($p))));
	}
}