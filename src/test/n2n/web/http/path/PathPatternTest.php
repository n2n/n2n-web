<?php
namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\web\http\path\PathPattern;
use n2n\util\type\TypeConstraints;
use n2n\util\uri\Path;

class PathPatternTest extends TestCase {
	
	function testWithConstraints() {
		$pathPattern = new PathPattern();
		$pathPattern->addConstant('ptusch', true, false);
		$pathPattern->addTypeConstraint(true, TypeConstraints::int(false, true), false);
		
		$this->assertNotNull($pathPattern->matchesPath(new Path(['ptusch', '2'])));
		$this->assertNull($pathPattern->matchesPath(new Path(['ptusch', 'zwei'])));
	}
}
