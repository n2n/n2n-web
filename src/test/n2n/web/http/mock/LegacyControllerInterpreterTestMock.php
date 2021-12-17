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
use n2n\web\http\annotation\AnnoExt;
use n2n\web\http\annotation\AnnoPath;
use n2n\web\http\annotation\AnnoIntercept;

class LegacyControllerInterpreterTestMock extends ControllerAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->m('postDoConsumsJson', new AnnoConsums('text/json'));
		$ai->m('getDoTest', new AnnoGet());
		$ai->m('postDoTest', new AnnoPost());
		$ai->m('deleteDoTest', new AnnoDelete());
		$ai->m('putDoTest', new AnnoPut());
		$ai->m('ext', new AnnoPath('param:#^[0-9]$#'), new AnnoExt('txt', 'html'));
		$ai->m('getTest', new AnnoPath('/get'), new AnnoGet());
		$ai->m('postTest', new AnnoPath('/post'), new AnnoPost());
		$ai->m('putTest', new AnnoPath('/put'), new AnnoPut());
		$ai->m('deleteTest', new AnnoPath('/delete'),  new AnnoDelete());
		$ai->m('getDoIntercept', new AnnoIntercept(InterceptorMock::class));
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

	public function ext() {
	}

	public function getTest() {
	}

	public function postTest() {
	}

	public function putTest() {
	}

	public function deleteTest() {
	}

	public function getDointercept() {

	}
}