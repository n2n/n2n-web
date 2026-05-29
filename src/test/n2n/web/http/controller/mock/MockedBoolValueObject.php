<?php

namespace n2n\web\http\controller\mock;

use n2n\spec\valobj\scalar\BoolValueObject;
use n2n\spec\valobj\scalar\IntValueObject;
use n2n\spec\valobj\scalar\FloatValueObject;
use n2n\spec\valobj\scalar\StringValueObject;
use n2n\spec\valobj\err\IllegalValueException;

class MockedBoolValueObject implements BoolValueObject {
	public function __construct(public bool $value) {
	}

	function toScalar(): bool {
		return $this->value;
	}
}

 // TODO: split classes into separated files.

class MockedIntValueObject implements IntValueObject {
	public function __construct(public int $value) {
	}

	function toScalar(): int {
		return $this->value;
	}
}

class MockedFloatValueObject implements FloatValueObject {
	public function __construct(public float $value) {
	}

	function toScalar(): float {
		return $this->value;
	}
}

class MockedStringValueObject implements StringValueObject {
	public function __construct(public string $value) {
	}

	function toScalar(): string {
		return $this->value;
	}
}

class MockedSecretValueObject implements StringValueObject {

	public function __construct(public string $value) {
		if ($value !== 'secret') {
			throw new IllegalValueException("you need to provide the correct secret value");
		}
	}

	function toScalar(): string {
		return $this->value;
	}
}