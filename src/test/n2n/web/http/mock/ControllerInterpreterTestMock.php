<?php

namespace n2n\web\http\mock;

use n2n\reflection\annotation\AnnoInit;
use n2n\web\http\annotation\AnnoConsums;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\controller\ParamBody;

class ControllerInterpreterTestMock extends ControllerAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->m('postDoConsumsJson', new AnnoConsums('text/json'));
	}

	public function postDoConsumsJson(ParamBody $json) {
		return 'test';
	}
}