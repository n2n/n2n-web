<?php

namespace n2n\web\http\controller;

use n2n\util\magic\MagicContext;
use n2n\web\http\annotation\AnnoIntercept;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\web\http\attribute\Intercept;

class InterceptorFactory {
	private $magicContext;

	function __construct(MagicContext $magicContext) {
		$this->magicContext = $magicContext;
	}

	/**
	 * @param AnnoIntercept $annoIntercept
	 * @throws ControllerErrorException
	 * @return Interceptor[]
	 */
	function createByAnno(AnnoIntercept $annoIntercept)  {
		$interceptors = array();
		foreach ($annoIntercept->getInterceptorLookupIds() as $interceptorLookupId) {
			try {
				$interceptors[] = $this->createByLookupId($interceptorLookupId);
			} catch (MagicObjectUnavailableException|InvalidInterceptorException $e) {
				throw new ControllerErrorException('Invalid interceptor annotated: ' . $interceptorLookupId,
						$annoIntercept->getFileName(), $annoIntercept->getLine());
			}
		}
		return $interceptors;
	}

	function createByAttr(Intercept $attrIntercept, \ReflectionClass $class) {
		$interceptors = array();
		foreach ($attrIntercept->getInterceptorLookupIds() as $interceptorLookupId) {
			$interceptor = null;
			try {
				$interceptors[] = $this->createByLookupId($interceptorLookupId);
			} catch (MagicObjectUnavailableException|InvalidInterceptorException $e) {
				throw new ControllerErrorException('Invalid interceptor annotated: ' . $interceptorLookupId,
						$class->getFileName(), $class->getStartLine());
			}
		}
		return $interceptors;
	}

	/**
	 * @param string $lookupId
	 * @return Interceptor
	 */
	function createByLookupId(string $lookupId) {
		$interceptor = $this->magicContext->lookup($lookupId);
		$this->valInterceptor($interceptor);
		return $interceptor;
	}

	private function valInterceptor(Interceptor $interceptor) {
		if ($interceptor instanceof Interceptor) return;

		throw new InvalidInterceptorException(get_class($interceptor)
				. ' can not be used as an Interceptor because it must implement '
				. Interceptor::class);
	}
}
