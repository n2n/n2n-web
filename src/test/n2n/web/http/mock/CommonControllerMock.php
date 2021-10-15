<?php
namespace n2n\web\http\mock;

use PHPUnit\Framework\TestCase;
use n2n\web\http\controller\ControllerAdapter;

class CommonControllerMock extends ControllerAdapter {
	
	function doInt(int $num) {
		
	}
	
	function doStringInt(string|int $num) {
		
	}
	
}