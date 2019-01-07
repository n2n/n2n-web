<?php
namespace n2n\web\http\controller\impl;

use n2n\web\http\controller\Interceptor;
use n2n\reflection\ReflectionUtils;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\magic\MagicContext;
use n2n\web\http\controller\ControllerErrorException;
use n2n\context\Lookupable;

abstract class InterceptorAdapter implements Interceptor, Lookupable {
    use ControllingUtilsTrait;
    
    private $aborted;
    
    /**
     * {@inheritDoc}
     * @see \n2n\web\http\controller\Interceptor::invoke()
     */
    final function invoke(ControllingUtils $controllingUtils): bool {
        $this->controllingUtils = $controllingUtils;
        
        InterceptorInvoker::do($this, $controllingUtils->getN2nContext());
        
        return !$this->aborted;
    }
    
    /**
     * Further execution of the intercepted method should be aborted.
     *
     * @param bool $abort
     */
    protected final function abort(bool $abort = true) {
        $this->aborted = $abort;
    }
}

class InterceptorInvoker {
	const METHOD = 'check';
	
	static function do(Interceptor $interceptor, MagicContext $magicContext) {
		$class = new \ReflectionClass($interceptor);
		$methods = ReflectionUtils::extractMethodHierarchy($class, self::METHOD);
		
		if (empty($methods)) {
			throw new ControllerErrorException('Interpretor must implemnt a method ' . self::METHOD . '(): ' 
					. $class->getName(), $class->getFileName());
		}
		
		foreach ($methods as $method) {
			$invoker = new MagicMethodInvoker($magicContext);
			$invoker->invoke($interceptor, $method);
		}
	}
}