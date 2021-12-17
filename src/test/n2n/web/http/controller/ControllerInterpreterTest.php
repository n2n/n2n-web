<?php
namespace n2n\web\http\controller;

use n2n\web\http\mock\LegacyControllerInterpreterTestMock;
use PHPUnit\Framework\TestCase;
use n2n\web\http\SimpleRequest;
use n2n\util\uri\Url;
use n2n\util\magic\SimpleMagicContext;
use n2n\web\http\Method;
use n2n\web\http\AcceptRange;
use n2n\web\http\AcceptMimeType;
use n2n\web\http\mock\ControllerInterpreterTestMock;
use n2n\web\http\mock\InterceptorMock;

class ControllerInterpreterTest extends TestCase {

	public function testAnnoConsums() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[new AcceptMimeType('text', 'json')]);


		$invokerInfo = current($controllerInterpreter->interpret());
		$this->assertTrue($invokerInfo instanceof InvokerInfo);
	}

	public function testAnnoConsumsWrongMime() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[]);

		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoGet() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('get', Method::GET, 'get', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('get', Method::POST, 'get', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoPost() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('post', Method::POST, 'post', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('post', Method::GET, 'post', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoPut() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('put', Method::PUT, 'put', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('put', Method::POST, 'put', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoDelete() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('delete', Method::DELETE, 'delete', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('delete', Method::POST, 'delete', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoGetAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('test', Method::GET, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoPostAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('test', Method::POST, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoDeleteAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('test', Method::DELETE, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoPutAttributeNotAllowed() {
		$this->expectException(ControllerErrorException::class);
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('test', Method::PUT, 'test', '{}',
				[]);

		$controllerInterpreter->interpret();
	}

	public function testAnnoExt() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);

		$controllerInterpreter->interpret();
		$this->assertNotEmpty($controllerInterpreter->interpret());
	}

	public function testAnnoIntercept() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('intercept', Method::GET, 'intercept', '{}',
				[]);

		$interpretation = $controllerInterpreter->interpret();
		$this->assertNotEmpty(current($interpretation)->getInterceptors());
	}

	public function testAnnoPath() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);

		$controllerInterpreter->interpret();
		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('asdf.txt', Method::GET, 'asdf.txt', '{}', []);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testConsums() {
		$controllerInterpreter = $this->prepareControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[new AcceptMimeType('text', 'json')]);


		$invokerInfo = current($controllerInterpreter->interpret());
		$this->assertTrue($invokerInfo instanceof InvokerInfo);
	}

	public function testConsumsWrongMime() {
		$controllerInterpreter = $this->prepareControllerInterpreter('consumsJson', Method::POST, 'consumsjson', '{}',
				[]);

		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testDelete() {
		$controllerInterpreter = $this->prepareControllerInterpreter('delete', Method::DELETE, 'delete', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('delete', Method::POST, 'delete', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testExt() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);
		$this->assertNotEmpty($controllerInterpreter->interpret());
	}

	public function testGet() {
		$controllerInterpreter = $this->prepareControllerInterpreter('get', Method::GET, 'get', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('get', Method::POST, 'get', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testIntercept() {
		$controllerInterpreter = $this->prepareControllerInterpreter('intercept', Method::GET, 'intercept', '{}',
				[]);

		$interpretation = $controllerInterpreter->interpret();
		$this->assertNotEmpty(current($interpretation)->getInterceptors());
	}

	public function testPath() {
		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('1.txt', Method::GET, '1.txt', '{}',
				[]);

		$controllerInterpreter->interpret();
		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareLegacyControllerInterpreter('asdf.txt', Method::GET, 'asdf.txt', '{}', []);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testPost() {
		$controllerInterpreter = $this->prepareControllerInterpreter('post', Method::POST, 'post', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('post', Method::GET, 'post', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	public function testPut() {
		$controllerInterpreter = $this->prepareControllerInterpreter('put', Method::PUT, 'put', '{}',
				[]);

		$this->assertNotEmpty($controllerInterpreter->interpret());

		$controllerInterpreter = $this->prepareControllerInterpreter('put', Method::POST, 'put', '{}',
				[]);
		$this->assertEmpty($controllerInterpreter->interpret());
	}

	private function prepareLegacyControllerInterpreter(string $urlPathPart, int $method, string $cmdPathPart, string $body, array $acceptMimeTypes) {
		$request = new SimpleRequest(Url::create([$urlPathPart]));
		$request->setMethod($method);
		$request->setCmdUrl(Url::create([$cmdPathPart]));
		$request->setBody($body);

		$invokerFactory = new ActionInvokerFactory(
				$request->getCmdPath(), $request->getCmdContextPath(), $request,
				$request->getMethod(), $request->getQuery(), $request->getPostQuery(),
				new AcceptRange($acceptMimeTypes));

		$invokerFactory->setConstantValues([]);

		$simpleMagicContextMock = $stub = $this->createPartialMock(SimpleMagicContext::class, array('lookup'));
		$stub->expects($this->any())
				->method('lookup')
				->willReturn(new InterceptorMock());

		$interceptor = new InterceptorFactory($simpleMagicContextMock);
		return new ControllerInterpreter(new \ReflectionClass(
				LegacyControllerInterpreterTestMock::class), $invokerFactory, $interceptor);
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

		$simpleMagicContextMock = $stub = $this->createPartialMock(SimpleMagicContext::class, array('lookup'));
		$stub->expects($this->any())
				->method('lookup')
				->willReturn(new InterceptorMock());

		$interceptor = new InterceptorFactory($simpleMagicContextMock);
		return new ControllerInterpreter(new \ReflectionClass(
				ControllerInterpreterTestMock::class), $invokerFactory, $interceptor);
	}
}