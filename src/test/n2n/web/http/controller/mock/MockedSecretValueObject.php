<?php

namespace n2n\web\http\controller\mock;

use n2n\spec\valobj\scalar\StringValueObject;
use n2n\spec\valobj\err\IllegalValueException;


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