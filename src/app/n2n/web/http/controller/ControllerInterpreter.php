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

use n2n\reflection\attribute\AttributeUtils;
use n2n\web\http\attribute\Consums;
use n2n\web\http\attribute\Delete;
use n2n\web\http\attribute\Get;
use n2n\web\http\attribute\Intercept;
use n2n\web\http\attribute\Path;
use n2n\web\http\attribute\Ext;
use n2n\web\http\attribute\Post;
use n2n\web\http\attribute\Put;
use n2n\web\http\path\PathPatternCompiler;
use n2n\reflection\ReflectionContext;
use n2n\web\http\path\PathPatternCompileException;
use n2n\web\http\Method;
use n2n\util\type\TypeUtils;
use n2n\reflection\attribute\AttributeSet;
use n2n\reflection\attribute\MethodAttribute;

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

		$this->rejectPathAttrs($method);
		$this->rejectHttpMethodAttrs($method);

		return $method;
	}

	/**
	 * @param \ReflectionMethod $method
	 */
	private function rejectPathAttrs(\ReflectionMethod $method) {
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());

		$attr = null;
		$methodName = $method->getName();
		if (null !== ($attrPath = $attributeSet->getMethodAttribute($methodName, Path::class))) {
			$attr = $attrPath;
		} else if (null !== ($attrExt = $attributeSet->getMethodAttribute($methodName, Ext::class))) {
			$attr = $attrExt;
		}

		if ($attr === null) return;

		throw $this->createInvalidAttrException($method);
	}

	/**
	 * @param \ReflectionMethod $method
	 */
	private function rejectHttpMethodAttrs(\ReflectionMethod $method) {
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
		$methodName = $method->getName();

		$attr = null;
		if (null !== ($attrGet = $attributeSet->getMethodAttribute($methodName, Get::class))) {
			$attr = $attrGet;
		} else if (null !== ($attrPut = $attributeSet->getMethodAttribute($methodName, Put::class))) {
			$attr = $attrPut;
		} else if (null !== ($attrPost = $attributeSet->getMethodAttribute($methodName, Post::class))) {
			$attr = $attrPost;
		} else if (null !== ($attrDelete = $attributeSet->getMethodAttribute($methodName, Delete::class))) {
			$attr = $attrDelete;
		}

		if ($attr === null) return;

		throw $this->createInvalidAttrException($method);
	}

	/**
	 * @param \ReflectionMethod $method
	 * @return ControllerErrorException
	 */
	private function createInvalidAttrException(\ReflectionMethod $method) {
		return new ControllerErrorException('Invalid attribute for method:'
				. TypeUtils::prettyReflMethName($method),
				$method->getFileName(), $method->getStartLine());
	}

	/**
	 * @param \ReflectionMethod $method
	 * @param mixed $allowedExtensions
	 * @return boolean
	 */
	private function checkSimpleMethod(\ReflectionMethod $method, &$allowedExtensions) {
		$this->checkAccessabilityMethod($method);
		$methodName = $method->getName();

		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());
		if (!$this->checkHttpMethod($methodName, $attributeSet) || !$this->checkAccept($methodName, $attributeSet)) {
			return false;
		}

		if ($attributeSet->hasMethodAttribute($methodName, Path::class)) {
			return false;
		}

		$allowedExtensions = $this->findExtensions($methodName, $attributeSet);

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
			$this->rejectHttpMethodAttrs($method);
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

		$invokerInfo = $this->invokerFactory->createFullMagic($method, new \n2n\util\uri\Path($paramCmdPathParts),
				$allowedExtensions);

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
			$attributeSet = ReflectionContext::getAttributeSet($class);
			foreach ($attributeSet->getMethodAttributesByName(Path::class) as $methodName => $pathAttr) {
				if ($pathAttr->getInstance()->getPattern() === null
						|| !$this->checkHttpMethod($methodName, $attributeSet)
						|| !$this->checkAccept($methodName, $attributeSet)) {
					continue;
				}

				$allowedExtensions = $this->findExtensions($methodName, $attributeSet);
				if (null !== ($invoker = $this->analyzePattern($pathAttr, $class->getMethod($methodName),
								$allowedExtensions))) {
					return $invoker;
				}
			}
		} while (null != ($class = $class->getParentClass()));

		return null;
	}

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

	private function checkAccept(string $methodName, AttributeSet $attributeSet) {
		$attrConsums = $attributeSet->getMethodAttribute($methodName, Consums::class);

		if (null === $attrConsums) return true;

		return null !== $this->invokerFactory->getAcceptRange()->bestMatch($attrConsums->getInstance()->getMimeTypes());
	}

	/**
	 * @param string $methodName
	 * @param AttributeSet $attributeSet
	 * @return string[]|NULL
	 */
	private function findExtensions(string $methodName, AttributeSet $attributeSet) {
		if (null !== ($attrExt = $attributeSet->getMethodAttribute($methodName, Ext::class))) {
			return $attrExt->getInstance()->getNames();
		}

		if (null !== ($attrExt = $attributeSet->getClassAttribute(Ext::class))) {
			return $attrExt->getInstance()->getNames();
		}

		return null;
	}

	/**
	 * @param Path $path
	 * @param \ReflectionMethod $method
	 * @param array|null $allowedExtensions
	 * @return InvokerInfo|null
	 * @throws ControllerErrorException
	 */
	private function analyzePattern(MethodAttribute $pathAttribute, \ReflectionMethod $method, array $allowedExtensions = null) {
		try {
			$pathPattern = $this->pathPatternCompiler->compile($pathAttribute->getInstance()->getPattern());
			if (null !== $allowedExtensions) {
				$pathPattern->setExtensionIncluded(false);
				$pathPattern->setAllowedExtensions($allowedExtensions);
			}

			return $this->invokerFactory->createSemiMagic($method, $pathPattern);
		} catch (PathPatternCompileException $e) {
			throw new ControllerErrorException('Invalid pattern', $method->getFileName(),
					AttributeUtils::extractMethodAttributeLine($pathAttribute->getAttribute(), $method));
		} catch (ControllerErrorException $e) {
			$e->addAdditionalError($pathAttribute->getFile(), $pathAttribute->getLine());
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

	function findControllerInterceptors() {
		$attrIntercept = ReflectionContext::getAttributeSet($this->class);

		if ($attrIntercept === null) return [];

		return $this->interceptorFactory->createByAttr($attrIntercept, $this->class);
	}

	/**
	 * @param \ReflectionMethod $method
	 * @return \n2n\web\http\controller\Interceptor[]
	 */
	private function findInterceptors(\ReflectionMethod|\ReflectionFunctionAbstract $method) {
		$attributeSet = ReflectionContext::getAttributeSet($method->getDeclaringClass());

		$attr = $attributeSet->getMethodAttribute($method->getName(), Intercept::class);

		if ($attr === null) return [];

		return $this->interceptorFactory->createByAttr($attr->getInstance(), $method->getDeclaringClass());
	}
}