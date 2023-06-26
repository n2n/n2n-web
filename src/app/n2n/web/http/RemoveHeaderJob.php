<?php

namespace n2n\web\http;

use n2n\util\ex\UnsupportedOperationException;

class RemoveHeaderJob implements HeaderJob {

	function __construct(private string $name) {
	}

	function isRemove(): bool {
		return true;
	}

	public function getName(): string {
		return $this->name;
	}

	function getValue(): ?string {
		throw new UnsupportedOperationException();
	}

	function __toString(): string {
		return 'remove: ' . $this->name;
	}

	function flush(): void {
		header_remove($this->name);
	}
}