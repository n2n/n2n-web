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
use n2n\web\http\annotation\AnnoPath;
use n2n\reflection\annotation\MethodAnnotation;
use n2n\reflection\annotation\AnnotationSet;
use n2n\web\http\annotation\AnnoConsums;
use n2n\web\http\annotation\AnnoIntercept;
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
	const MAGIC_METHOD_PERFIX = 'do';
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
	 * @throws ControllerErrorException
	 */
	private function checkAccessabilityMethod(\ReflectionMethod $method) {
		if ($method->isPublic()) return;
		
		throw new ControllerErrorException('Method must be public: ' 
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
		$annotationSet = ReflectionContext::getAnnotationSet($method->getDeclaringClass());
		$methodName = $method->getName();
		
		$anno = null;
		if (null !== ($annoPath = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoPath'))) {
			$anno = $annoPath;
		} else if (null !== ($annoExt = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoExt'))) {
			$anno = $annoExt;
		}
		
		if ($anno === null) return;
		
		throw $this->createInvalidAnnoException($anno);				
	}
	
	/**
	 * @param \ReflectionMethod $method
	 */
	private function rejectHttpMethodAnnos(\ReflectionMethod $method) {
		$annotationSet = ReflectionContext::getAnnotationSet($method->getDeclaringClass());
		$methodName = $method->getName();
		
		$anno = null;
		if (null !== ($annoGet = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoGet'))) {
			$anno = $annoGet;
		} else if (null !== ($annoPut = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoPut'))) {
			$anno = $annoPut;
		} else if (null !== ($annoPost = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoPost'))) {
			$anno = $annoPost;
		} else if (null !== ($annoDelete = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoDelete'))) {
			$anno = $annoDelete;
		}
		
		if ($anno === null) return;
		
		throw $this->createInvalidAnnoException($anno);
	}
	
	/**
	 * @param MethodAnnotation $annotation
	 * @throws ControllerErrorException
	 */
	private function createInvalidAnnoException(MethodAnnotation $annotation) {
		return new ControllerErrorException('Invalid annotation for method:'
						. TypeUtils::prettyReflMethName($annotation->getAnnotatedMethod()),
				$annotation->getFileName(), $annotation->getLine());
	}
	
	/**
	 * @param \ReflectionMethod $method
	 * @param mixed $allowedExtensions
	 * @return boolean
	 */
	private function checkSimpleMethod(\ReflectionMethod $method, &$allowedExtensions) {
		$this->checkAccessabilityMethod($method);
		
		$annotationSet = ReflectionContext::getAnnotationSet($method->getDeclaringClass());
		if ($annotationSet->isEmpty()) return true; 
		
		if (!$this->checkHttpMethod($method->getName(), $annotationSet)
				|| !$this->checkAccept($method->getName(), $annotationSet)) return false;
		
		if (null !== $annotationSet->getMethodAnnotation($method->getName(), 
				'n2n\web\http\annotation\AnnoPath')) {
			return false;
		}
		
		$allowedExtensions = $this->findExtensions($method->getName(), $annotationSet);
		
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
		
		if ($this->class->hasMethod($methodName)) {
			$method = $this->class->getMethod($methodName);
			$this->rejectHttpMethodAnnos($method);
			return $method;
		}

		if ($this->class->hasMethod(self::MAGIC_METHOD_PERFIX . $nameBase)) {
			return $this->class->getMethod(self::MAGIC_METHOD_PERFIX . $nameBase);
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
	private function findPatternMethod() {
		$class = $this->class;
		do {
			$annotationSet = ReflectionContext::getAnnotationSet($class);
			
			foreach ($annotationSet->getMethodAnnotationsByName('n2n\web\http\annotation\AnnoPath') as $annoPath) {
				$methodName = $annoPath->getAnnotatedMethod()->getName();
				
				if ($annoPath->getPattern() === null 
						|| !$this->checkHttpMethod($methodName, $annotationSet)
						|| !$this->checkAccept($methodName, $annotationSet)) {
					continue;
				}
				
				if (null !== ($invoker = $this->analyzePattern($annoPath, $this->findExtensions($methodName, $annotationSet)))) {
					return $invoker;
				}
			}
		} while (null != ($class = $class->getParentClass()));
	}
	
	/**
	 * @param string $methodName
	 * @param AnnotationSet $annotationSet
	 * @return boolean
	 */
	private function checkHttpMethod(string $methodName, AnnotationSet $annotationSet) {
		$httpMethod = $this->invokerFactory->getHttpMethod();
		$allAllowed = true;
		
		if (null !== $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoGet')) {
			if ($httpMethod == Method::GET) return true;
			$allAllowed = false;
		}
		
		if (null !== $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoPut')) {
			if ($httpMethod == Method::PUT) return true;
			$allAllowed = false;
		}
		
		if (null !== $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoPost')) {
			if ($httpMethod == Method::POST) return true;
			$allAllowed = false;
		}
		
		if (null !== $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoDelete')) {
			if ($httpMethod == Method::DELETE) return true;
			$allAllowed = false;
		}
		
		return $allAllowed;
	}
	

	/**
	 * @param string $methodName
	 * @param AnnotationSet $annotationSet
	 * @return boolean
	 */
	private function checkAccept($methodName, AnnotationSet $annotationSet) {
		$annoConsums = $annotationSet->getMethodAnnotation($methodName, AnnoConsums::class);
		
		if (null === $annoConsums) return true;
		
		return null !== $this->invokerFactory->getAcceptRange()->bestMatch($annoConsums->getMimeTypes());
	}
	
	/**
	 * @param string $methodName
	 * @param AnnotationSet $annotationSet
	 * @return string[]|NULL
	 */
	private function findExtensions($methodName, AnnotationSet $annotationSet) {
		if (null !== ($annoExt = $annotationSet->getMethodAnnotation($methodName, 'n2n\web\http\annotation\AnnoExt'))) {
			return $annoExt->getNames();
		}
		
		if (null !== ($annoExt = $annotationSet->getClassAnnotation('n2n\web\http\annotation\AnnoExt'))) {
			return $annoExt->getNames();
		}
		
		return null;
	}
	
	/**
	 * @param AnnoPath $annoPath
	 * @throws ControllerErrorException
	 * @return InvokerInfo
	 */
	private function analyzePattern(AnnoPath $annoPath, array $allowedExtensions = null) {
		try {
			$pathPattern = $this->pathPatternCompiler->compile($annoPath->getPattern());
			if (null != $allowedExtensions) {
				$pathPattern->setExtensionIncluded(false);
				$pathPattern->setAllowedExtensions($allowedExtensions);
			}
			
			return $this->invokerFactory->createSemiMagic($annoPath->getAnnotatedMethod(), $pathPattern);
		} catch (PathPatternCompileException $e) {
			throw new ControllerErrorException('Invalid pattern annotated', 
					$annoPath->getFileName(), $annoPath->getLine());
		} catch (ControllerErrorException $e) {
			$e->addAdditionalError($annoPath->getFileName(), $annoPath->getLine());
			throw $e;
		}
	}
	
	/**
	 * @return InvokerInfo
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
		$annotationSet = ReflectionContext::getAnnotationSet($this->class);
		
		$annoIntercept = $annotationSet->getClassAnnotation(AnnoIntercept::class);
		
		if ($annoIntercept === null) return [];
		
		return $this->interceptorFactory->createByAnno($annoIntercept);
	}
	
	/**
	 * @param \ReflectionMethod $method
	 * @return \n2n\web\http\controller\Interceptor[]
	 */
	private function findInterceptors(\ReflectionMethod $method) {
	    $annotationSet = ReflectionContext::getAnnotationSet($method->getDeclaringClass());
	    
	    $annoIntercept = $annotationSet->getMethodAnnotation($method->getName(), AnnoIntercept::class);
	    
	    if ($annoIntercept === null) return [];
	    
	    return $this->interceptorFactory->createByAnno($annoIntercept);
	}
}

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
            $interceptor = null;
            try {
                $interceptors[] = $this->createByLookupId($interceptorLookupId);
            } catch (MagicObjectUnavailableException|InvalidInterceptorException $e) {
                throw new ControllerErrorException('Invalid interceptor annotated: ' . $interceptorLookupId,
                        $annoIntercept->getFileName(), $annoIntercept->getLine());
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
