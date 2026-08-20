<?php

namespace n2n\web\http;

use n2n\util\uri\Url;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

class VarsRequestTest extends TestCase {

	function testToPsr(): void {
		$contextUrl = Url::create('https://www.holeradio.ch/atusch');
		$getVars = ['param1' => 'value1', 'param2' => 'value2'];
		$cmdUrl = Url::create('btusch')->queryExt($getVars);
		$url = $contextUrl->ext($cmdUrl);

		$body = json_encode(['prop1' => 'value1', 'prop2' => 'value2']);
		$simpleRequest = new VarsRequest(
				['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => (string) $url->toRelativeUrl(),
						'SCRIPT_NAME' => '/atusch/index.php', 'SERVER_NAME' => 'www.holeradio.ch',
						'REQUEST_SCHEME' => 'https', 'QUERY_STRING' => $url->getQuery()->__toString()],
				$getVars, [], [], $body);

		$httpFactory = new HttpFactory();

		$psrRequest = $simpleRequest->toPsr($httpFactory, $httpFactory, $httpFactory);
		$this->assertSame((string) $url, (string) $psrRequest->getUri());
		$this->assertSame($body, $psrRequest->getBody()->getContents());
	}
}