<?php

namespace n2n\web\http\mock;

use n2n\reflection\annotation\AnnoInit;
use n2n\web\http\annotation\AnnoConsums;

class ControllerInterpreterTestMock {
	private static function _annos(AnnoInit $ai) {
		$ai->m('getDoConsumsJson', new AnnoConsums('text/json'));
	}

	public function getDoConsumsJson($json) {

	}
}