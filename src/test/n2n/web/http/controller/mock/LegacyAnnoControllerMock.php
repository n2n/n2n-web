<?php
namespace n2n\web\http\controller\mock;

use n2n\web\http\controller\ControllerAdapter;
use n2n\reflection\annotation\AnnoInit;
use n2n\web\http\annotation\AnnoPath;
use n2n\web\http\annotation\AnnoGet;
use n2n\web\http\annotation\AnnoPost;
use n2n\web\http\annotation\AnnoPut;
use n2n\web\http\annotation\AnnoDelete;
use n2n\web\http\annotation\AnnoExt;
use n2n\web\http\annotation\AnnoConsums;
use n2n\web\http\annotation\AnnoIntercept;

class LegacyAnnoControllerMock extends ControllerAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoExt('json'), new AnnoIntercept('some\ClassInterceptor'));
		$ai->m('getHoleradio', new AnnoPath('holeradio/param:*'), new AnnoGet(),
				new AnnoIntercept('some\MethodInterceptor'));
		$ai->m('postHoleradio', new AnnoPath('holeradio/param:*'), new AnnoPost(),
				new AnnoConsums('application/json'));
		$ai->m('doSimple', new AnnoPut(), new AnnoDelete(), new AnnoExt('html', 'xml'));
	}

	function getHoleradio($param) {
	}

	function postHoleradio($param) {
	}

	function doSimple($param) {
	}

	function index() {
	}
}
