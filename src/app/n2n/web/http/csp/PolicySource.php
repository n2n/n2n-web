<?php

namespace n2n\web\http\csp;

use n2n\util\uri\Url;
use n2n\util\StringUtils;

class PolicySource {

	private ?PolicySourceType $type = null;

	function __construct(private string $value) {
		if (0 === preg_match('/^[^\'\s]+$/', $this->value)
				&& 0 === preg_match('/^\'[^\'\s]+\'$/', $this->value)) {
			throw new InvalidCspException('Invalid content security policy source: ' . $this->value);
		}
	}

	function isGeneralInlineGrant(): bool {
		return $this->value === '\'' . PolicySourceKeyword::UNSAFE_INLINE->value . '\'';
	}

	function isSpecificInlineGrant(): booL {
		return match ($this->getType()) {
			PolicySourceType::HASH, PolicySourceType::NONCE => true,
			default => false,
		};
	}

	private function getType(): PolicySourceType {
		if ($this->type !== null) {
			return $this->type;
		}

		if (StringUtils::startsWith('\'nonce-', $this->value)) {
			return $this->type = PolicySourceType::NONCE;
		}

		if (StringUtils::startsWith('\'sha256-', $this->value)
				|| StringUtils::startsWith('\'sha384-', $this->value)
				|| StringUtils::startsWith('\'sha512-', $this->value)) {
			return $this->type = PolicySourceType::HASH;
		}

		return $this->type = PolicySourceType::OTHER;

	}

	function getValue(): string {
		return $this->value;
	}

	function equals(mixed $arg): bool {
		return $arg instanceof PolicySource && $this->value === $arg->getValue();
	}

	static function createKeyword(PolicySourceKeyword $keyword): PolicySource {
		$source = new PolicySource('\'' . $keyword->value . '\'');
		$source->type = PolicySourceType::OTHER;
		return $source;
	}

	static function createUrl(Url $url): PolicySource {
		$source = new PolicySource((string) $url->chQuery(null)->chFragment(null));
		$source->type = PolicySourceType::OTHER;
		return $source;
	}

	static function createHash(string $content): PolicySource {
		$source = new PolicySource('\'sha256-' . base64_encode(hash('sha256', $content, true)) . '\'');
		$source->type = PolicySourceType::HASH;
		return $source;
	}

	static function fromHash(string $hash): PolicySource {
		$policySource = new PolicySource('\'' . $hash . '\'');
		if ($policySource->getType() === PolicySourceType::HASH) {
			return $policySource;
		}

		throw new \InvalidArgumentException('Invalid policy source hash: ' . $hash);
	}
}

enum PolicySourceType {
	case HASH;
	case NONCE;
	case OTHER;
}