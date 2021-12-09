<?php
namespace n2n\web\http\controller;

use n2n\web\http\mock\ControllerInterpreterTestMock;
use PHPUnit\Framework\TestCase;
use n2n\web\http\SimpleRequest;
use n2n\util\uri\Url;
use n2n\util\magic\SimpleMagicContext;
use n2n\web\http\Method;
use n2n\web\http\AcceptRange;
use n2n\web\http\AcceptMimeType;

class ControllerInterpreterTest extends TestCase {
	private ControllerInterpreter $controllerInterpreter;

	protected function setUp(): void {

	}

	public function testAnnoConsums() {
		$controllerInterpreter = $this->prepareControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[new AcceptMimeType('text', 'json')]);


		$invokerInfo = current($controllerInterpreter->interpret());
		$this->assertTrue($invokerInfo instanceof InvokerInfo);
	}

	public function testAnnoConsumsWrongMime() {
		$controllerInterpreter = $this->prepareControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[]);

		$this->assertEmpty($controllerInterpreter->interpret());
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

	private function prepareControllerInterpreter(string $urlPathPart, int $method, string $cmdPathPart, string $body, array $acceptMimeTypes) {
		$request = new SimpleRequest(Url::create([$urlPathPart]));
		$request->setMethod($method);
		$request->setCmdUrl(Url::create([$cmdPathPart]));
		$request->setBody($body);

		$invokerFactory = new ActionInvokerFactory(
				$request->getCmdPath(), $request->getCmdContextPath(), $request,
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				new AcceptRange($acceptMimeTypes));

		$invokerFactory->setConstantValues([]);
		$interceptor = new InterceptorFactory(new SimpleMagicContext());
		return new ControllerInterpreter(new \ReflectionClass(
				ControllerInterpreterTestMock::class), $invokerFactory, $interceptor);
	}
}