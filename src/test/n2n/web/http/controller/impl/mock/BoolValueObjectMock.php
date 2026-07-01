<?php

namespace n2n\web\http\controller\impl\mock;

use n2n\spec\valobj\scalar\BoolValueObject;

class BoolValueObjectMock implements BoolValueObject {
	public function __construct(public bool $value) {
	}

	function toScalar(): bool {
		return $this->value;
	}
}