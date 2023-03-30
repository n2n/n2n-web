<?php

namespace n2n\web\http\csp;

use n2n\util\type\ArgUtils;
use n2n\web\http\Response;

class ContentSecurityPolicy {

	const HEADER_NAME = 'Content-Security-Policy';
	const POLICY_SEPARATOR = ';';

	private \SplObjectStorage $policyStorage;

	function __construct(array $policies = []) {
		ArgUtils::valArray($policies, Policy::class);
		foreach ($policies as $policy) {
			$this->addPolicy($policy);
		}
	}

	function getPolicy(PolicyDirective $directive): Policy {
		return $this->policyStorage[$directive] ?? $this->policyStorage[$directive] = new Policy($directive);
	}

	/**
	 * @return Policy[]
	 */
	function getPolicies(): array {
		$policies = [];
		foreach ($this->policyStorage as $policy) {
			$policies[] = $policy;
		}
		return $policies;
	}

	function addPolicy(Policy $policy): static {
		$this->policyStorage[$policy->getDirective()] = $policy;
		return $this;
	}

	function addSource(PolicyDirective $directive, PolicySource $policySource): static {
		$this->getPolicy($directive)->addSource($policySource);
		return $this;
	}

	function isEmpty(): bool {
		if ($this->policyStorage->count() === 0) {
			return true;
		}

		foreach ($this->policyStorage as $policy) {
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
		}

		$csp = $this->copy();
		foreach ($existingHeaderValues as $value) {
			$csp->append(ContentSecurityPolicy::parse($value));
		}

		$response->removeHeader(self::HEADER_NAME);
		$response->setHeader($csp->toHeaderStr(), true);
	}

	function toHeaderStr(): string {
		return self::HEADER_NAME . ': ' . implode(self::POLICY_SEPARATOR, $this->getPolicies());
	}

	function copy(): ContentSecurityPolicy {
		return new ContentSecurityPolicy(array_map(fn (Policy $p) => $p->copy(), $this->getPolicies()));
	}

	static function parse(string $str): ContentSecurityPolicy {
		$parts = preg_split('\s*;\s*', trim($str));

		return new ContentSecurityPolicy(array_map(fn ($p) => Policy::parse($p), $parts));
	}
}