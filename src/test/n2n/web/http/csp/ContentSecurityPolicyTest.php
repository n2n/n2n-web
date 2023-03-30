<?php

namespace n2n\web\http\csp;

class ContentSecurityPolicyTest extends \PHPUnit\Framework\TestCase {


	function testSimple() {
		$cspStr = 'default-src \'self\' https://www.von-burg.net; script-src \'self\' https: data:';
		$csp = ContentSecurityPolicy::parse($cspStr);

		$policies = $csp->getPolicies();
		$this->assertCount(2, $policies);

		$this->assertEquals(PolicyDirective::DEFAULT_SRC, $policies['default-src']->getDirective());
		$this->assertCount(2, $policies['default-src']->getSources());

		$this->assertEquals(PolicyDirective::SCRIPT_SRC, $policies['script-src']->getDirective());
		$sources = $policies['script-src']->getSources();
		$this->assertCount(3, $sources);
		$this->assertEquals('data:', $sources['data:']->getValue());

		$this->assertEquals(ContentSecurityPolicy::HEADER_NAME . ': ' . $cspStr, $csp->toHeaderStr());

		$csp->append(ContentSecurityPolicy::parse('script-src \'unsafe-eval\'; img-src \'unsafe-hashes\''));
	}

	function testAppend() {
		$cspStr = 'default-src \'self\' https://www.von-burg.net; script-src \'self\' https: data:';
		$csp = ContentSecurityPolicy::parse($cspStr);
		$csp->append(ContentSecurityPolicy::parse('script-src \'unsafe-eval\'; img-src \'unsafe-hashes\''));

		$policies = $csp->getPolicies();
		$this->assertCount(3, $policies);

		$this->assertEquals(PolicyDirective::DEFAULT_SRC, $policies['default-src']->getDirective());
		$this->assertCount(2, $policies['default-src']->getSources());

		$this->assertEquals(PolicyDirective::SCRIPT_SRC, $policies['script-src']->getDirective());
		$sources = $policies['script-src']->getSources();
		$this->assertCount(4, $sources);
		$this->assertEquals('\'unsafe-eval\'', $sources['\'unsafe-eval\'']->getValue());

		$this->assertEquals(PolicyDirective::IMG_SRC, $policies['img-src']->getDirective());
		$sources = $policies['img-src']->getSources();
		$this->assertCount(1, $sources);
		$this->assertEquals('\'unsafe-hashes\'', $sources['\'unsafe-eval\'']->getValue());

	}

	function testInvalid() {
		$this->expectException(InvalidCspException::class);
		ContentSecurityPolicy::parse('default-src \'self\' https://www.von-burg.net; script-src \'self\', \'unsafe-inline\'');
	}
}