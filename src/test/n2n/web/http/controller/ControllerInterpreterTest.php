<?php
namespace n2n\web\http\controller;

use PHPUnit\Framework\TestCase;
use n2n\util\uri\Path;
use n2n\util\uri\Query;
use n2n\web\http\Request;
use n2n\web\http\Method;
use n2n\web\http\AcceptRange;
use n2n\util\magic\MagicContext;
use n2n\web\http\controller\mock\AttrControllerMock;
use n2n\web\http\controller\mock\LegacyAnnoControllerMock;
use n2n\web\http\attribute\Ext;
use n2n\web\http\attribute\PathPattern;
use n2n\web\http\attribute\Intercept;
use n2n\web\http\attribute\Consums;
use n2n\reflection\ReflectionContext;

class ControllerInterpreterTest extends TestCase {

	private function createInterpreter(string $className, array $cmdPathParts, int $httpMethod,
			string $accept = '*/*', ?MagicContext $magicContext = null): ControllerInterpreter {
		$request = $this->createMock(Request::class);
		if ($magicContext === null) {
			$magicContext = $this->createMock(MagicContext::class);
			$magicContext->method('lookup')->willReturn($this->createMock(Interceptor::class));
		}
		$factory = new ActionInvokerFactory(new Path($cmdPathParts), new Path([]), $request, $httpMethod,
				new Query([]), new Query([]), AcceptRange::createFromStr($accept), $magicContext);
		$factory->setConstantValues([]);
		return new ControllerInterpreter(new \ReflectionClass($className), $factory,
				new InterceptorFactory($magicContext));
	}

	static function controllerClassNames(): array {
		return [[AttrControllerMock::class], [LegacyAnnoControllerMock::class]];
	}

	/** @dataProvider controllerClassNames */
	function testAttributeSet(string $className) {
		$attributeSet = ReflectionContext::getAttributeSet(new \ReflectionClass($className));

		$this->assertEquals(['json'], $attributeSet->getClassAttribute(Ext::class)->getInstance()->getNames());
		$this->assertEquals(['some\ClassInterceptor'],
				$attributeSet->getClassAttribute(Intercept::class)->getInstance()->getInterceptorLookupIds());
		$this->assertEquals('holeradio/param:*',
				$attributeSet->getMethodAttribute('getHoleradio', PathPattern::class)->getInstance()->getPattern());
		$this->assertEquals(['application/json'],
				$attributeSet->getMethodAttribute('postHoleradio', Consums::class)->getInstance()->getMimeTypes());
		$this->assertEquals(['html', 'xml'],
				$attributeSet->getMethodAttribute('doSimple', Ext::class)->getInstance()->getNames());
	}

	/** @dataProvider controllerClassNames */
	function testPatternMethodHttpMethod(string $className) {
		$invokers = $this->createInterpreter($className, ['holeradio', 'foo.json'], Method::GET)
				->interpret(ControllerInterpreter::DETECT_PATTERN_METHODS);
		$this->assertCount(1, $invokers);
		$this->assertEquals('getHoleradio', $invokers[0]->getInvoker()->getMethod()->getName());

		$invokers = $this->createInterpreter($className, ['holeradio', 'foo.json'], Method::POST, 'application/json')
				->interpret(ControllerInterpreter::DETECT_PATTERN_METHODS);
		$this->assertCount(1, $invokers);
		$this->assertEquals('postHoleradio', $invokers[0]->getInvoker()->getMethod()->getName());

		// Consums mismatch
		$invokers = $this->createInterpreter($className, ['holeradio', 'foo.json'], Method::POST, 'text/html')
				->interpret(ControllerInterpreter::DETECT_PATTERN_METHODS);
		$this->assertCount(0, $invokers);

		// class Ext 'json' only
		$invokers = $this->createInterpreter($className, ['holeradio', 'foo.html'], Method::GET)
				->interpret(ControllerInterpreter::DETECT_PATTERN_METHODS);
		$this->assertCount(0, $invokers);
	}

	/** @dataProvider controllerClassNames */
	function testSimpleMethod(string $className) {
		$invokers = $this->createInterpreter($className, ['simple', 'foo.xml'], Method::PUT)
				->interpret(ControllerInterpreter::DETECT_SIMPLE_METHODS);
		$this->assertCount(1, $invokers);

		$invokers = $this->createInterpreter($className, ['simple', 'foo.xml'], Method::GET)
				->interpret(ControllerInterpreter::DETECT_SIMPLE_METHODS);
		$this->assertCount(0, $invokers);

		// method Ext overrides class Ext
		$invokers = $this->createInterpreter($className, ['simple', 'foo.json'], Method::DELETE)
				->interpret(ControllerInterpreter::DETECT_SIMPLE_METHODS);
		$this->assertCount(0, $invokers);
	}

	/** @dataProvider controllerClassNames */
	function testInterceptors(string $className) {
		$interceptor = $this->createMock(Interceptor::class);
		$magicContext = $this->createMock(MagicContext::class);
		$magicContext->expects($this->exactly(2))->method('lookup')
				->willReturnMap([['some\ClassInterceptor', true, null, $interceptor],
						['some\MethodInterceptor', true, null, $interceptor]]);

		$interpreter = $this->createInterpreter($className, ['holeradio', 'foo.json'], Method::GET, '*/*', $magicContext);
		$this->assertCount(1, $interpreter->findControllerInterceptors());
		$invokers = $interpreter->interpret(ControllerInterpreter::DETECT_PATTERN_METHODS);
		$this->assertCount(1, $invokers[0]->getInterceptors());
	}
}
