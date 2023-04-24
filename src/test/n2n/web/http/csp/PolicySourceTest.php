<?php

namespace n2n\web\http\csp;

use PHPUnit\Framework\TestCase;
use n2n\util\uri\Url;

class PolicySourceTest extends TestCase {

	function testUrlQueryFragment() {
		$source = PolicySource::createUrl(Url::create('https://www.n2n.rocks/somepath?holeradio=1#frag'));

		$this->assertEquals('https://www.n2n.rocks/somepath', $source->getValue());

	}
}