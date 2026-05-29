<?php

namespace n2n\web\http\controller\mock;

use n2n\spec\valobj\scalar\StringValueObject;

class MockedStringValueObject implements StringValueObject {
	public function __construct(public string $value) {
	}

	function toScalar(): string {
		return $this->value;
	}
}
