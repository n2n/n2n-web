<?php

namespace n2n\web\http\mock;

use n2n\spec\valobj\scalar\BoolValueObject;
use n2n\spec\valobj\scalar\IntValueObject;
use n2n\spec\valobj\scalar\FloatValueObject;
use n2n\spec\valobj\scalar\StringValueObject;
use n2n\spec\valobj\err\IllegalValueException;

class ScalarValueObjectEntityMock {
}
class MockBoolValueObject implements BoolValueObject {
	public function __construct(public bool $value) {
	}

	function toScalar(): bool {
		return $this->value;
	}
}

class MockIntValueObject implements IntValueObject {
	public function __construct(public int $value) {
	}

	function toScalar(): int {
		return $this->value;
	}
}

class MockFloatValueObject implements FloatValueObject {
	public function __construct(public float $value) {
	}

	function toScalar(): float {
		return $this->value;
	}
}

class MockStringValueObject implements StringValueObject {
	public function __construct(public string $value) {
	}

	function toScalar(): string {
		return $this->value;
	}
}

class MockSecretValueObject implements StringValueObject {

	public function __construct(public string $value) {
		if ($value !== 'secret') {
			throw new IllegalValueException("you need to provide the correct secret value");
		}
	}

	function toScalar(): string {
		return $this->value;
	}
}