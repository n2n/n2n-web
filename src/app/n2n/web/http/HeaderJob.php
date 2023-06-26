<?php

namespace n2n\web\http;

interface HeaderJob {

	function getName(): string;

	function isRemove(): bool;

	function getValue(): ?string;

	function flush(): void;

	function __toString(): string;
}