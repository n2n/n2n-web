<?php

namespace n2n\web\http\mock;

use n2n\reflection\annotation\AnnoInit;
use n2n\web\http\annotation\AnnoConsums;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\controller\ParamBody;
use n2n\web\http\annotation\AnnoDelete;
use n2n\web\http\annotation\AnnoGet;
use n2n\web\http\annotation\AnnoPost;
use n2n\web\http\annotation\AnnoPut;

class ControllerInterpreterTestMock extends ControllerAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->m('postDoConsumsJson', new AnnoConsums('text/json'));
		$ai->m('getDoTest', new AnnoGet());
		$ai->m('postDoTest', new AnnoPost());
		$ai->m('deleteDoTest', new AnnoDelete());
		$ai->m('putDoTest', new AnnoPut());
	}

	public function postDoConsumsJson(ParamBody $json) {
	}

	public function getDoTest() {
	}

	public function postDoTest() {
	}

	public function deleteDoTest() {
	}

	public function putDoTest() {
	}
}