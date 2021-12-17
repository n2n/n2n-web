<?php

namespace n2n\web\http\mock;

use n2n\web\http\controller\impl\InterceptorAdapter;

class InterceptorMock extends InterceptorAdapter {
	function check() {
		$this->abort();
	}
}