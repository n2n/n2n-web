<?php
namespace n2n\web\http\mock;

use PHPUnit\Framework\TestCase;
use n2n\web\http\controller\ControllerAdapter;

class CommonControllerMock extends ControllerAdapter {
	public $num;
	
	function doInt(int $num) {
		$this->num = $num;
	}
	
	function doStringInt(string|int $num) {
		$this->num = $num;
	}
	
}