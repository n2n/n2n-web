<?php

namespace n2n\web\http\nav;

use n2n\web\http\SimpleRequest;
use n2n\util\uri\Url;
use n2n\web\http\Response;
use n2n\core\container\N2nContext;
use n2n\web\http\HttpContext;
use n2n\web\http\SimpleSession;
use n2n\web\http\Supersystem;
use n2n\l10n\N2nLocale;
use n2n\web\http\Subsystem;
use n2n\web\http\SubsystemMatcher;
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
						new SubsystemMatcher('stusch-de', 'de.stusch.ch', null, [new N2nLocale('de_CH')])
					]),
					new Subsystem('holeradio', [
						new SubsystemMatcher('holeradio-de', 'de.holeradio.ch', null, [new N2nLocale('de_CH')]),
						new SubsystemMatcher('holeradio-it', 'it.holeradio.ch', null, [new N2nLocale('it_CH'), new N2nLocale('rm_CH')])
					])
				],
				$n2nContext);
	}

	function testDetermineSubsystemMatcher() {
		$this->assertEquals('holeradio-de',
				$this->httpContext->determineSubsystemMatcher('holeradio', new N2nLocale('de_CH'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->determineSubsystemMatcher('holeradio', new N2nLocale('it'))->getName());

		$this->assertEquals('holeradio-it',
				$this->httpContext->determineSubsystemMatcher('holeradio', new N2nLocale('rm_CH'))->getName());

		$this->assertEquals('holeradio-de',
				$this->httpContext->determineSubsystemMatcher('holeradio', new N2nLocale('fr_CH'))->getName());
	}
}