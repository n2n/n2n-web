<?php

namespace n2n\web\http;

use n2n\util\uri\Url;
use n2n\core\container\N2nContext;
use n2n\l10n\N2nLocale;
use PHPUnit\Framework\TestCase;

class HttpContextTest extends TestCase  {

	private HttpContext $httpContext;

	function setUp(): void {
		$request = new SimpleRequest(Url::create(['context']));
		$response = new Response($request);

		$n2nContext = $this->getMockBuilder(N2nContext::class)->getMock();

		$this->httpContext = new HttpContext($request, $response, new SimpleSession(), Url::create(['assets']),
				new Supersystem([new N2nLocale('de_CH')]),
				[
					new Subsystem('stusch', [
						new SubsystemRule('stusch-de', 'de.stusch.ch', null, [new N2nLocale('de_CH')])
					]),
					new Subsystem('holeradio', [
						new SubsystemRule('holeradio-de', 'de.holeradio.ch', null, [new N2nLocale('de_CH')]),
						new SubsystemRule('holeradio-it', 'it.holeradio.ch', null,
								[new N2nLocale('it_CH'), new N2nLocale('rm_CH')])
					])
				],
				$n2nContext);
	}

	function testDetermineSubsystemRule() {
		$this->assertEquals('holeradio-de',
				$this->httpContext->determineSubsystemRule('holeradio', new N2nLocale('de_CH'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->determineSubsystemRule('holeradio', new N2nLocale('it'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->determineSubsystemRule('holeradio', new N2nLocale('rm_CH'))->getName());

		$this->assertEquals('holeradio-de',
				$this->httpContext->determineSubsystemRule('holeradio', new N2nLocale('fr_CH'))->getName());
	}
}