<?php
namespace n2n\web\http\controller\mock;

use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\attribute\PathPattern;
use n2n\web\http\attribute\Get;
use n2n\web\http\attribute\Post;
use n2n\web\http\attribute\Put;
use n2n\web\http\attribute\Delete;
use n2n\web\http\attribute\Ext;
use n2n\web\http\attribute\Consums;
use n2n\web\http\attribute\Intercept;

#[Ext('json')]
#[Intercept('some\ClassInterceptor')]
class AttrControllerMock extends ControllerAdapter {

	#[PathPattern('holeradio/param:*'), Get]
	#[Intercept('some\MethodInterceptor')]
	function getHoleradio($param) {
	}

	#[PathPattern('holeradio/param:*'), Post]
	#[Consums('application/json')]
	function postHoleradio($param) {
	}

	#[Put, Delete, Ext('html', 'xml')]
	function doSimple($param) {
	}

	function index() {
	}
}
