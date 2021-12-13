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

	public function testAnnoGet() {
		$controllerInterpreter = $this->prepareControllerInterpreter('get', Method::GET, 'get', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('get', Method::POST, 'get', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoPost() {
		$controllerInterpreter = $this->prepareControllerInterpreter('post', Method::POST, 'post', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('post', Method::GET, 'post', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoDelete() {
		$controllerInterpreter = $this->prepareControllerInterpreter('put', Method::PUT, 'put', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('put', Method::POST, 'put', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoPut() {
		$controllerInterpreter = $this->prepareControllerInterpreter('delete', Method::DELETE, 'delete', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('delete', Method::POST, 'delete', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoGetAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareControllerInterpreter('test', Method::GET, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoPostAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareControllerInterpreter('test', Method::POST, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoDeleteAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareControllerInterpreter('test', Method::DELETE, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoPutAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareControllerInterpreter('test', Method::PUT, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoExt() {
		$controllerInterpreter = $this->prepareControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);

		$controllerInterpreter->interpret();
		$this->assertNotEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoIntercept() {

	}

	public function testAnnoPath() {
		$controllerInterpreter = $this->prepareControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);

		$controllerInterpreter->interpret();
		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('asdf.txt', Method::GET, 'asdf.txt', '{}', []);
		$this->assertEmpty($controllerInterpreter->interpret());
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