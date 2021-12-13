<?php

namespace n2n\web\http\mock;

use n2n\web\http\controller\ParamBody;
use n2n\web\http\attribute\Consums;
use n2n\web\http\attribute\Get;
use n2n\web\http\attribute\Post;
use n2n\web\http\attribute\Delete;
use n2n\web\http\attribute\Put;
use n2n\web\http\attribute\Ext;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\attribute\Path;

class ControllerInterpreterTestMock extends ControllerAdapter {
	#[Consums('text/json')]
	public function postDoConsumsJson(ParamBody $json) {
	}

	#[Get]
	public function getDoTest() {
	}

	#[Post]
	public function postDoTest() {
	}

	#[Delete]
	public function deleteDoTest() {
	}

	#[Put]
	public function putDoTest() {
	}

	#[Path('param:#^[0-9]$#'), Ext('txt', 'html')]
	public function ext() {
	}

	#[Path('/get'), Get]
	public function getTest() {
	}

	#[Path('/post'), Post]
	public function postTest() {
	}

	#[Path('/put'), Put]
	public function putTest() {
	}

	#[Path('/delete'), Delete]
	public function deleteTest() {
	}

	public function interceptTest() {

	}
}