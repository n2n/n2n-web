<?php

namespace n2n\web\http;

use n2n\util\uri\Url;
use n2n\core\container\N2nContext;
use n2n\l10n\N2nLocale;
use PHPUnit\Framework\TestCase;

class HttpContextTest extends TestCase  {

	private HttpContext $httpContext;

	function setUp(): void {
		$request = new SimpleRequest(Url::create('https://www.holeradio.ch/context'));
		$response = new Response($request);

		$n2nContext = $this->getMockBuilder(N2nContext::class)->getMock();

		$this->httpContext = new HttpContext($request, $response, new SimpleSession(), Url::create(['assets']),
				new Supersystem([new N2nLocale('de_CH')]),
				[
					(new Subsystem('stusch'))
							->createRule('stusch-de', 'de.stusch.ch', null, [new N2nLocale('de_CH')], []),
					(new Subsystem('holeradio'))
							->createRule('holeradio-de', 'de.holeradio.ch', null, [new N2nLocale('de_CH')], ['Cache-Control: no-cache'])
							->createRule('holeradio-it', 'it.holeradio.ch', null,
									[new N2nLocale('it_CH'), new N2nLocale('rm_CH')], ['Cache-Control: no-cache', 'X-Content-Type-Options: nosniff'])
				],
				$n2nContext);
	}

	function testFindBestSubsystemRuleBySubsystemAndN2nLocale() {
		$this->assertEquals('holeradio-de',
				$this->httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale('holeradio', new N2nLocale('de_CH'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale('holeradio', new N2nLocale('it'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale('holeradio', new N2nLocale('rm_CH'))->getName());

		$this->assertEquals('holeradio-de',
				$this->httpContext->findBestSubsystemRuleBySubsystemAndN2nLocale('holeradio', new N2nLocale('fr_CH'))->getName());
	}
}