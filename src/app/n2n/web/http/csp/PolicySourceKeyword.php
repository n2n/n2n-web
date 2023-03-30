<?php

namespace n2n\web\http\csp;

enum PolicySourceKeyword: string {
	case SELF = 'self';
	case UNSAFE_INLINE = 'unsafe-inline';
	case UNSAFE_EVAL = 'unsafe-eval';
	case UNSAFE_HASHES = 'unsafe-hashes';
}
