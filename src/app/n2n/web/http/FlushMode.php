<?php

namespace n2n\web\http;

enum FlushMode {
	case OUT;
	case SILENT;

	function isEchoEnabled(): bool {
		return match ($this) {
			self::OUT => true,
			default => false
		};
	}
}