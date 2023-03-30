<?php

namespace n2n\web\http\csp;

enum PolicyDirective: string {
	case CHILD_SRC = 'child-src';
	case CONNECT_SRC = 'connect-src';
	case DEFAULT_SRC = 'default-src';
	case FONT_SRC = 'font-src';
	case FRAME_SRC = 'frame-src';
	case IMG_SRC = 'img-src';
	case MANIFEST_SRC = 'manifest-src';
	case MEDIA_SRC = 'media-src';
	case OBJECT_SRC = 'object-src';
	case PREFETCH_SRC = 'prefetch-src';
	case SCRIPT_SRC = 'script-src';
	case SCRIPT_SRC_ELM = 'script-src-elem';
	case SCRIPT_SRC_ATTR = 'script-src-attr';
	case STYLE_SRC = 'style-src';
	case STYLE_SRC_ELEM = 'style-src-elem';
	case STYLE_SRC_ATTR = 'style-src-attr';
	case WORKER_SRC = 'worker-src';
}
