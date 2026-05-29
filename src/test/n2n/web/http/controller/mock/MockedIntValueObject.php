<?php

namespace n2n\web\http\controller\mock;

use n2n\spec\valobj\scalar\IntValueObject;

class MockedIntValueObject implements IntValueObject {
	public function __construct(public int $value) {
	}

	function toScalar(): int {
		return $this->value;
	}
}
