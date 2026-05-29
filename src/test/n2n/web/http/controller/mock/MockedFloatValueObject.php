<?php

namespace n2n\web\http\controller\mock;

use n2n\spec\valobj\scalar\FloatValueObject;

class MockedFloatValueObject implements FloatValueObject {
	public function __construct(public float $value) {
	}

	function toScalar(): float {
		return $this->value;
	}
}
