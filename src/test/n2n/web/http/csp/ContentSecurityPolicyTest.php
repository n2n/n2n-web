<?php

namespace n2n\web\http\csp;

class ContentSecurityPolicyTest extends \PHPUnit\Framework\TestCase {


	function testSimple() {
		$cspStr = 'default-src \'self\' https://www.von-burg.net; script-src \'self\' https: data: \'sha256-OSiqcPpkpIyna3ow2bY9UjXdWDQPvYRDxT8V2t9sFG4=\'';
		$csp = ContentSecurityPolicy::parse($cspStr);

		$policies = $csp->getPolicies();
		$this->assertCount(2, $policies);

		$this->assertEquals(PolicyDirective::DEFAULT_SRC, $policies['default-src']->getDirective());
		$this->assertCount(2, $policies['default-src']->getSources());

		$this->assertEquals(PolicyDirective::SCRIPT_SRC, $policies['script-src']->getDirective());
		$sources = $policies['script-src']->getSources();
		$this->assertCount(4, $sources);
		$this->assertEquals('data:', $sources['data:']->getValue());
		$this->assertEquals(true, $sources['\'sha256-OSiqcPpkpIyna3ow2bY9UjXdWDQPvYRDxT8V2t9sFG4=\'']->isSpecificInlineGrant());

		$this->assertEquals(ContentSecurityPolicy::HEADER_NAME . ': ' . $cspStr, $csp->toHeaderStr());

		$this->assertFalse($csp->isEmpty());
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
		$this->assertEquals('\'unsafe-hashes\'', $sources['\'unsafe-hashes\'']->getValue());
	}

	function testInvalid() {
		$this->expectException(InvalidCspException::class);
		ContentSecurityPolicy::parse('default-src \'self\' https://www.von-burg.net; script-src \'self\', \'unsafe-inline\'');
	}

	function testInlineGeneralGrant() {
		$policy = Policy::parse('script-src \'self\' \'unsafe-inline\' \'https://www.von-burg.net\' \'sha256-OSiqcPpkpIyna3ow2bY9UjXdWDQPvYRDxT8V2t9sFG4=\'');

		$policy->addSource(PolicySource::createHash('holeradio'));

		$sources = array_values($policy->getSources());
		$this->assertCount(5, $sources);
		$this->assertNull($this->extractPolicySourceType($sources[0]));
		$this->assertNull($this->extractPolicySourceType($sources[1]));
		$this->assertNull($this->extractPolicySourceType($sources[2]));
		$this->assertNull($this->extractPolicySourceType($sources[3]));
		$this->assertEquals(PolicySourceType::HASH, $this->extractPolicySourceType($sources[4]));

		$this->assertEquals('script-src \'self\' \'unsafe-inline\' \'https://www.von-burg.net\'',
				$policy->__toString());

		$this->assertEquals($sources, array_values($policy->getSources()));
		$this->assertEquals(PolicySourceType::OTHER, $this->extractPolicySourceType($sources[0]));
		$this->assertEquals(PolicySourceType::OTHER, $this->extractPolicySourceType($sources[1]));
		$this->assertTrue($sources[1]->isGeneralInlineGrant());
		$this->assertEquals(PolicySourceType::OTHER, $this->extractPolicySourceType($sources[2]));
		$this->assertFalse($sources[2]->isSpecificInlineGrant());
		$this->assertEquals(PolicySourceType::HASH, $this->extractPolicySourceType($sources[3]));
		$this->assertTrue($sources[3]->isSpecificInlineGrant());
		$this->assertEquals(PolicySourceType::HASH, $this->extractPolicySourceType($sources[4]));
	}

	private function extractPolicySourceType(PolicySource $policySource): mixed {
		$class = new \ReflectionClass($policySource);
		$property = $class->getProperty('type');
		$property->setAccessible(true);
		return $property->getValue($policySource);
	}
}