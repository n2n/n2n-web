<?php
namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\web\http\HttpContext;
use n2n\web\http\SimpleRequest;
use n2n\util\uri\Url;
use n2n\web\http\Response;
use n2n\web\http\Supersystem;
use n2n\l10n\N2nLocale;
use n2n\web\ui\ViewFactory;
use n2n\util\magic\SimpleMagicContext;
use n2n\util\uri\Path;
use n2n\web\http\mock\CommonControllerMock;
use n2n\web\http\SimpleSession;
use n2n\core\container\N2nContext;

class ControllingPlanTest extends TestCase {
	private $httpContext;
	
	function setUp(): void {
		$request = new SimpleRequest(Url::create(['context']));
		$response = new Response($request);
		
		$n2nContext = $this->getMockBuilder(N2nContext::class)->getMock();
		
		$this->httpContext = new HttpContext($request, $response, new SimpleSession(), Url::create(['assets']), 
				new Supersystem([new N2nLocale('de_CH')]), [], 
				$n2nContext);	
	}
	
	function testInt() {
		$contorllingPlan = new ControllingPlan($this->httpContext);
		
		$controller = new CommonControllerMock();
		$contorllingPlan->addMain(new ControllerContext(new Path(['int', '2']), new Path(['context']), $controller));
		
		$result = $contorllingPlan->execute();
		$this->assertNotNull($result);
		$this->assertTrue($controller->num === 2);
	}
	
	function testStringInt() {
		$contorllingPlan = new ControllingPlan($this->httpContext);
		
		$controller = new CommonControllerMock();
		$contorllingPlan->addMain(new ControllerContext(new Path(['stringint', '2']), new Path(['context']), $controller));
		
		$result = $contorllingPlan->execute();
		$this->assertNotNull($result);
		$this->assertTrue($controller->num === '2');
	}
	
	
}