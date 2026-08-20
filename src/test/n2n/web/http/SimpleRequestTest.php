<?php

namespace n2n\web\http;

use n2n\util\uri\Url;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

class SimpleRequestTest extends TestCase {

	function testToPsr(): void {
		$contextUrl = Url::create('https://www.holeradio.ch/atusch');
		$cmdUrl = Url::create('btusch?param1=ctusch&param2=dtusch');
		$url = $contextUrl->ext($cmdUrl);

		$simpleRequest = new SimpleRequest($contextUrl);
		$simpleRequest->setMethod(Method::POST);
		$simpleRequest->setCmdUrl($cmdUrl);
		$body = json_encode(['prop1' => 'value1', 'prop2' => 'value2']);
		$simpleRequest->setBody($body);
		$simpleRequest->setHeader('Content-Type', 'application/json');

		$httpFactory = new HttpFactory();

		$psrRequest = $simpleRequest->toPsr($httpFactory, $httpFactory, $httpFactory);
		$this->assertSame((string) $url, (string) $psrRequest->getUri());
		$this->assertSame($body, $psrRequest->getBody()->getContents());
	}
}