<?php

namespace n2n\web\http\controller\impl\mock;
use n2n\spec\valobj\scalar\FloatValueObject;

class FloatValueObjectMock implements FloatValueObject {
	public function __construct(public float $value) {
	}

	function toScalar(): float {
		return $this->value;
	}
}