<?php
namespace n2n\web\http\controller;

use n2n\web\http\mock\ControllerInterpreterTestMock;
use PHPUnit\Framework\TestCase;
use n2n\web\http\SimpleRequest;
use n2n\util\uri\Url;
use n2n\web\http\Response;
use n2n\core\container\N2nContext;
use n2n\web\http\HttpContext;
use n2n\web\http\SimpleSession;
use n2n\web\http\Supersystem;
use n2n\l10n\N2nLocale;
use n2n\util\magic\SimpleMagicContext;

class ControllerInterpreterTest extends TestCase {
	private ControllerInterpreter $controllerInterpreter;

	protected function setUp(): void {
		$request = new SimpleRequest(Url::create(['context']));
		$response = new Response($request);

		$n2nContext = $this->getMockBuilder(N2nContext::class)->getMock();

		$httpContext = new HttpContext($request, $response, new SimpleSession(), Url::create(['assets']),
				new Supersystem([new N2nLocale('de_CH')]), [], $n2nContext);

		$invokerFactory = new ActionInvokerFactory(
				$request->getCmdPath(), $request->getCmdContextPath(), $request,
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				$request->getAcceptRange(), $n2nContext);

		$interceptor = new InterceptorFactory(new SimpleMagicContext());
		$this->controllerInterpreter = new ControllerInterpreter(new \ReflectionClass(
				ControllerInterpreterTestMock::class), $invokerFactory, $interceptor);
	}

	public function testAnnoConsums() {
		$invokerInfo = $this->controllerInterpreter->interpretCustom('getDoConsumsJson');
		$this->assertTrue($invokerInfo instanceof InvokerInfo);
		$this->assertTrue(false);
	}

	public function testAnnoDelete() {

	}

	public function testAnnoExt() {

	}

	public function testAnnoGet() {

	}

	public function testAnnoIntercept() {

	}

	public function testAnnoPath() {

	}

	public function testAnnoPost() {

	}

	public function testAnnoPut() {

	}

	public function testConsums() {

	}

	public function testDelete() {

	}

	public function testExt() {

	}

	public function testGet() {

	}

	public function testIntercept() {

	}

	public function testPath() {

	}

	public function testPost() {

	}

	public function testPut() {

	}
}