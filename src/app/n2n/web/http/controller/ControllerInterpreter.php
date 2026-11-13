<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\http\controller;

use n2n\util\uri\Path;
use n2n\web\http\path\PathPatternCompiler;
use n2n\reflection\ReflectionContext;
use n2n\web\http\path\PathPatternCompileException;
use n2n\web\http\Method;
use n2n\reflection\attribute\AttributeSet;
use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\attribute\Attribute;
use n2n\web\http\attribute\PathPattern;
use n2n\web\http\attribute\Ext;
use n2n\web\http\attribute\Get;
use n2n\web\http\attribute\Put;
use n2n\web\http\attribute\Post;
use n2n\web\http\attribute\Delete;
use n2n\web\http\attribute\Consums;
use n2n\web\http\attribute\Intercept;
use n2n\util\magic\MagicContext;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\util\type\TypeUtils;

class ControllerInterpreter {
	const DETECT_INDEX_METHOD = 1;
	const DETECT_NOT_FOUND_METHOD = 2;
	const DETECT_SIMPLE_METHODS = 4;
	const DETECT_PATTERN_METHODS = 8;
	const DETECT_PREPARE_METHOD = 16;
	const DETECT_ALL = 31;

	const PREPARE_METHOD_NAME = 'prepare';
	const MAGIC_METHOD_PREFIX = 'do';
	const MAGIC_GET_METHOD_PREFIX = 'getDo';
	const MAGIC_PUT_METHOD_PREFIX = 'putDo';
	const MAGIC_DELETE_METHOD_PREFIX = 'deleteDo';
	const MAGIC_POST_METHOD_PREFIX = 'postDo';
	const MAGIC_OPTIONS_METHOD_PREFIX = 'optionsDo';
	const INDEX_METHOD_NAME = 'index';
	const NOT_FOUND_METHOD_NAME = 'notFound';
	
	private $class;
	private $invokerFactory;
	private $interceptorFactory;
	private $pathPatternCompiler;
	
	/**
	 * @param \ReflectionClass $class
	 * @param int $detect
	 */
	public function __construct(\ReflectionClass $class, ActionInvokerFactory $invokerFactory, 
			InterceptorFactory $interceptorFactory) {
		$this->class = $class;
		$this->invokerFactory = $invokerFactory;
		$this->interceptorFactory = $interceptorFactory;
		$this->pathPatternCompiler = new PathPatternCompiler();
	}
	/**
	 * @param int $detectOptions
	 * @return InvokerInfo[]
	 */
	public function interpret(int $detectOptions = self::DETECT_ALL) {
		$invokers = array();
		
		if ($detectOptions & self::DETECT_PREPARE_METHOD
				&& null !== ($invoker = $this->findPrepareMethod())) {
			$invokers[] = $invoker;
		}
		
		if ($detectOptions & self::DETECT_SIMPLE_METHODS
				&& null !== ($invoker = $this->findSimpleMethod())) {
			$invokers[] = $invoker;
		} else if ($detectOptions & self::DETECT_PATTERN_METHODS
				&& null !== ($invoker = $this->findPatternMethod())) {
			$invokers[] = $invoker;
		} else if ($detectOptions & self::DETECT_INDEX_METHOD
				&& null !== ($invoker = $this->findIndexMethod())) {
			$invokers[] = $invoker;
		} else if ($detectOptions & self::DETECT_NOT_FOUND_METHOD
				&& null !== ($invoker = $this->findNotFoundMethod())) {
			$invokers[] = $invoker;
		}
		
		foreach ($invokers as $invoker) {
			$invoker->setInterceptors($this->findInterceptors($invoker->getInvoker()->getMethod()));
		}
		
		return $invokers;
	}

	/**
	 * @return InvokerInfo
	 */
	public function interpretCustom($methodName) {
		$method = $this->getMethod($methodName);

		$invokerInfo = $this->invokerFactory->createFullMagic($method, $this->invokerFactory->getCmdPath());
		if ($invokerInfo === null) return null;
	
		return $invokerInfo;
	}
	
	/**
	 * @param \ReflectionMethod $method
	 * @throws ControllerError
	 */
	private function checkAccessabilityMethod(\ReflectionMethod $method) {
		if ($method->isPublic()) return;
		
		throw new ControllerError('Method must be public: '
						. $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()',
				$method->getFileName(), $method->getStartLine());
	}
	
	/**
	 * @param string $methodName
	 * @return NULL|\ReflectionMethod
	 */
	private function getMethod(string $methodName) {
		if (!$this->class->hasMethod($methodName)) return null;
		
		$method = $this->class->getMethod($methodName);
		$this->checkAccessabilityMethod($method);
		
		$this->rejectPathAnnos($method);
		$this->rejectHttpMethodAnnos($method);
		
		return $method;
	}
	
	/**
	 * @param \ReflectionMethod $method
	 */
	private function rejectPathAnnos(\ReflectionMethod $method) {
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
		$methodName = $method->getName();
		
		$attr = $attributeSet->getMethodAttribute($methodName, PathPattern::class)
				?? $attributeSet->getMethodAttribute($methodName, Ext::class);
		
		if ($attr === null) return;
		
		throw $this->createInvalidAnnoException($attr);
	}
	
	/**
	 * @param \ReflectionMethod $method
	 */
	private function rejectHttpMethodAnnos(\ReflectionMethod $method) {
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
		$methodName = $method->getName();
		
		$attr = $attributeSet->getMethodAttribute($methodName, Get::class)
				?? $attributeSet->getMethodAttribute($methodName, Put::class)
				?? $attributeSet->getMethodAttribute($methodName, Post::class)
				?? $attributeSet->getMethodAttribute($methodName, Delete::class);
		
		if ($attr === null) return;
		
		throw $this->createInvalidAnnoException($attr);
	}
	
	/**
	 * @param MethodAttribute $attribute
	 * @return ControllerError
	 */
	private function createInvalidAnnoException(MethodAttribute $attribute) {
		return new ControllerError('Invalid attribute for method: '
						. TypeUtils::prettyReflMethName($attribute->getMethod()),
				$attribute->getFile(), $attribute->getLine());
	}
	
	/**
	 * @param \ReflectionMethod $method
	 * @param mixed $allowedExtensions
	 * @return boolean
	 */
	private function checkSimpleMethod(\ReflectionMethod $method, &$allowedExtensions) {
		$this->checkAccessabilityMethod($method);
		
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
		
		if (!$this->checkHttpMethod($method->getName(), $attributeSet)
				|| !$this->checkAccept($method->getName(), $attributeSet)) return false;
		
		if ($attributeSet->hasMethodAttribute($method->getName(), PathPattern::class)) {
			return false;
		}
		
		$allowedExtensions = $this->findExtensions($method->getName(), $attributeSet);
		
		return true;
	}
	/**
	 * @return InvokerInfo
	 */
	private function findPrepareMethod() {
		if (null !== ($method = $this->getMethod(self::PREPARE_METHOD_NAME))) {
			return $this->invokerFactory->createNonMagic($method);
		}
		return null;
	}
	/**
	 * @return InvokerInfo
	 */
	private function findIndexMethod() {
		if (!$this->class->hasMethod(self::INDEX_METHOD_NAME)) return null;
		
		$method = $this->class->getMethod(self::INDEX_METHOD_NAME);
		$allowedExtensions = null;
		if (!$this->checkSimpleMethod($method, $allowedExtensions)) return null;
		
		return $this->invokerFactory->createFullMagic($method, $this->invokerFactory->getCmdPath(),
				$allowedExtensions);
	}
	
	/**
	 * @param string $nameBase
	 * @return \ReflectionMethod|NULL
	 */
	private function findDoMethod(string $nameBase) {
		$methodName = null;
		switch ($this->invokerFactory->getHttpMethod()) {
			case Method::GET:
				$methodName = self::MAGIC_GET_METHOD_PREFIX . $nameBase;
				break;
			case Method::PUT:
				$methodName = self::MAGIC_PUT_METHOD_PREFIX . $nameBase;
				break;
			case Method::DELETE:
				$methodName = self::MAGIC_DELETE_METHOD_PREFIX . $nameBase;
				break;
			case Method::POST:
				$methodName = self::MAGIC_POST_METHOD_PREFIX . $nameBase;
				break;
			case Method::OPTIONS:
				$methodName = self::MAGIC_OPTIONS_METHOD_PREFIX . $nameBase;
				break;
		}
		
		if ($methodName !== null && $this->class->hasMethod($methodName)) {
			$method = $this->class->getMethod($methodName);
			$this->rejectHttpMethodAnnos($method);
			return $method;
		}

		if ($this->class->hasMethod(self::MAGIC_METHOD_PREFIX . $nameBase)) {
			return $this->class->getMethod(self::MAGIC_METHOD_PREFIX . $nameBase);
		}
		
		return null;
	}
	
	/**
	 * @return InvokerInfo
	 */
	private function findSimpleMethod() {
		$cmdPath = $this->invokerFactory->getCmdPath();
		if ($cmdPath->isEmpty()) return null;
		
		$cmdPathParts = $cmdPath->getPathParts();
		
		$paramCmdPathParts = $cmdPathParts;
		$firstPathPart = (string) array_shift($paramCmdPathParts);
		if (preg_match('/[A-Z]/', $firstPathPart)) return null;
		
		$method = $this->findDoMethod($firstPathPart);
		if ($method === null) return null;

		$allowedExtensions = null;
		if (!$this->checkSimpleMethod($method, $allowedExtensions)) return null;
		
		$invokerInfo = $this->invokerFactory->createFullMagic($method, new Path($paramCmdPathParts), $allowedExtensions);
		if ($invokerInfo === null) return null;
		
		$invokerInfo->setNumSinglePathParts($invokerInfo->getNumSinglePathParts() + 1);
		return $invokerInfo;
	}
	
	/**
	 * @return InvokerInfo
	 */
	private function findPatternMethod(): ?InvokerInfo {
		$class = $this->class;
		do {
			$attributeSet = ReflectionContext::getAttributeSet($class);
			
			foreach ($attributeSet->getMethodAttributesByName(PathPattern::class) as $pathPatternAttr) {
				$methodName = $pathPatternAttr->getMethod()->getName();
				
				if (!$this->checkHttpMethod($methodName, $attributeSet)
						|| !$this->checkAccept($methodName, $attributeSet)) {
					continue;
				}
				
				if (null !== ($invoker = $this->analyzePattern($pathPatternAttr, $this->findExtensions($methodName, $attributeSet)))) {
					return $invoker;
				}
			}
		} while (false !== ($class = $class->getParentClass()));
		
		return null;
	}
	
	/**
	 * @param string $methodName
	 * @param AttributeSet $attributeSet
	 * @return boolean
	 */
	private function checkHttpMethod(string $methodName, AttributeSet $attributeSet) {
		$httpMethod = $this->invokerFactory->getHttpMethod();
		$allAllowed = true;
		
		if ($attributeSet->hasMethodAttribute($methodName, Get::class)) {
			if ($httpMethod == Method::GET) return true;
			$allAllowed = false;
		}
		
		if ($attributeSet->hasMethodAttribute($methodName, Put::class)) {
			if ($httpMethod == Method::PUT) return true;
			$allAllowed = false;
		}
		
		if ($attributeSet->hasMethodAttribute($methodName, Post::class)) {
			if ($httpMethod == Method::POST) return true;
			$allAllowed = false;
		}
		
		if ($attributeSet->hasMethodAttribute($methodName, Delete::class)) {
			if ($httpMethod == Method::DELETE) return true;
			$allAllowed = false;
		}
		
		return $allAllowed;
	}
	

	/**
	 * @param string $methodName
	 * @param AttributeSet $attributeSet
	 * @return boolean
	 */
	private function checkAccept($methodName, AttributeSet $attributeSet) {
		$consumsAttr = $attributeSet->getMethodAttribute($methodName, Consums::class);
		
		if (null === $consumsAttr) return true;
		
		return null !== $this->invokerFactory->getAcceptRange()->bestMatch($consumsAttr->getInstance()->getMimeTypes());
	}
	
	/**
	 * @param string $methodName
	 * @param AttributeSet $attributeSet
	 * @return string[]|NULL
	 */
	private function findExtensions($methodName, AttributeSet $attributeSet) {
		if (null !== ($extAttr = $attributeSet->getMethodAttribute($methodName, Ext::class))) {
			return $extAttr->getInstance()->getNames();
		}
		
		if (null !== ($extAttr = $attributeSet->getClassAttribute(Ext::class))) {
			return $extAttr->getInstance()->getNames();
		}
		
		return null;
	}

	/**
	 * @param MethodAttribute $pathPatternAttr
	 * @param array|null $allowedExtensions
	 * @return InvokerInfo
	 * @throws ControllerError
	 */
	private function analyzePattern(MethodAttribute $pathPatternAttr, ?array $allowedExtensions = null) {
		$path = $pathPatternAttr->getInstance();
		assert($path instanceof PathPattern);
		
		try {
			$pathPattern = $this->pathPatternCompiler->compile($path->getPattern());
			if (null != $allowedExtensions) {
				$pathPattern->setExtensionIncluded(false);
				$pathPattern->setAllowedExtensions($allowedExtensions);
			}
			
			return $this->invokerFactory->createSemiMagic($pathPatternAttr->getMethod(), $pathPattern);
		} catch (PathPatternCompileException $e) {
			throw new ControllerError('Invalid pattern annotated',
					$pathPatternAttr->getFile(), $pathPatternAttr->getLine(), previous: $e);
		} catch (ControllerError $e) {
			$e->addAdditionalError($pathPatternAttr->getFile(), $pathPatternAttr->getLine());
			throw $e;
		}
	}
	
	/**
	 * @return InvokerInfo|null
	 */
	private function findNotFoundMethod() {
		if (null !== ($method = $this->getMethod(self::NOT_FOUND_METHOD_NAME))) {
			return $this->invokerFactory->createNonMagic($method);
		}
		return null;
	}
	
	/**
	 * @return Interceptor[] 
	 */
	function findControllerInterceptors() {
		$attributeSet = ReflectionContext::getAttributeSet($this->class);
		
		$interceptAttr = $attributeSet->getClassAttribute(Intercept::class);
		
		if ($interceptAttr === null) return [];
		
		return $this->interceptorFactory->createByAttribute($interceptAttr);
	}
	
	/**
	 * @param \ReflectionMethod $method
	 * @return Interceptor[]
	 */
	private function findInterceptors(\ReflectionFunctionAbstract $method) {
	    $attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
	    
	    $interceptAttr = $attributeSet->getMethodAttribute($method->getName(), Intercept::class);
	    
	    if ($interceptAttr === null) return [];
	    
	    return $this->interceptorFactory->createByAttribute($interceptAttr);
	}
}

class InterceptorFactory {
    private $magicContext;
    
    function __construct(MagicContext $magicContext) {
        $this->magicContext = $magicContext;
    }
    
    /**
     * @param Attribute $interceptAttr attribute with an instance of {@link Intercept}
     * @return Interceptor[]
     * @throws ControllerError
     */
    function createByAttribute(Attribute $interceptAttr)  {
        $intercept = $interceptAttr->getInstance();
        assert($intercept instanceof Intercept);
        
        $interceptors = array();
        foreach ($intercept->getInterceptorLookupIds() as $interceptorLookupId) {
            $interceptor = null;
            try {
                $interceptors[] = $this->createByLookupId($interceptorLookupId);
            } catch (MagicObjectUnavailableException|InvalidInterceptorException $e) {
                throw new ControllerError('Invalid interceptor annotated: ' . $interceptorLookupId,
                        $interceptAttr->getFile(), $interceptAttr->getLine());
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
