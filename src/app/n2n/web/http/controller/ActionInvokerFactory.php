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

use n2n\util\uri\Query;
use n2n\web\http\path\PathPattern;
use n2n\web\http\path\PathPatternComposeException;
use n2n\reflection\ReflectionUtils;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\uri\Path;
use n2n\util\magic\MagicContext;
use n2n\web\http\Method;
use n2n\web\http\AcceptRange;
use n2n\web\http\Request;

class ActionInvokerFactory {
	const PARAM_CMD_CONTEXT_PATH = 'cmdContextPath';
	const PARAM_CMD_CONTEXT_PATH_PARTS = 'cmdContextPathParts';
	const PARAM_CMD_PATH = 'cmdPath';
	const PARAM_CMD_PATH_PARTS = 'cmdPathParts';
	const PARAM_EXTENSION = 'extension';
	
	private $cmdPath;
	private $cmdContextPath;
	private $request;
	private $httpMethod;
	private $query;
	private $postQuery;
	private $acceptRange;
	private $magicContext;
	private $constantValues;
		
	public function __construct(Path $cmdPath, Path $cmdContextPath, Request $request, $httpMethod, 
			Query $query, Query $postQuery, AcceptRange $acceptRange, MagicContext $magicContext = null) {
		$this->cmdPath = $cmdPath;
		$this->cmdContextPath = $cmdContextPath;
		$this->request = $request;
		$this->httpMethod = $httpMethod;
		$this->query = $query;
		$this->postQuery = $postQuery;
		$this->acceptRange = $acceptRange;
		$this->magicContext = $magicContext;
	}
	
	public function setConstantValues(array $constantValues) {
		$this->constantValues = $constantValues;
	} 
	
	public function getConstantValues() {
		return $this->constantValues;
	}
	
	public function getCmdPath() {
		return $this->cmdPath;
	}
	
	public function getHttpMethod() {
		return $this->httpMethod;
	}
	
	public function getAcceptRange() {
		return $this->acceptRange;
	}
	
	private function checkForCmdParam($paramName) {
		if (array_key_exists($paramName, $this->constantValues)) {
			return $this->constantValues[$paramName];
		}		
		
		switch ($paramName) {
			case self::PARAM_CMD_CONTEXT_PATH:
				return $this->cmdContextPath;
			case self::PARAM_CMD_CONTEXT_PATH_PARTS:
				return $this->cmdContextPath->toArray();
			case self::PARAM_CMD_PATH:
				return $this->cmdPath;
			case self::PARAM_CMD_PATH_PARTS:
				return $this->cmdPath->toArray();
			default:
				return null;
		}
	}
	
	private function checkForQueryParam($paramName, $paramClass, &$value) {
		$value = null;
		switch ($paramClass->getName()) {
			case 'n2n\web\http\controller\ParamQuery':
				if ($this->query->contains($paramName)) {
					$value = new ParamQuery($this->query->get($paramName));
				}
				return true;
			case 'n2n\web\http\controller\ParamGet':
				if ($this->httpMethod == Method::GET && $this->query->contains($paramName)) {
					$value = new ParamGet($this->query->get($paramName));
				}
				return true;
			case 'n2n\web\http\controller\ParamPost':
				if ($this->httpMethod == Method::POST && $this->postQuery->contains($paramName)) {
					$value = new ParamPost($this->postQuery->get($paramName));
				}
				return true;
			case 'n2n\web\http\controller\ParamPut':
				if ($this->httpMethod == Method::PUT && $this->query->contains($paramName)) {
					$value = new ParamPut($this->query->get($paramName));
				}
				return true;
			case 'n2n\web\http\controller\ParamPatch':
				if ($this->httpMethod == Method::PATCH && $this->query->contains($paramName)) {
					$value = new ParamPut($this->query->get($paramName));
				}
				return true;
			case 'n2n\web\http\controller\ParamDelete':
				if ($this->httpMethod == Method::DELETE && $this->query->contains($paramName)) {
					$value = new ParamDelete($this->query->get($paramName));
				}
				return true;
			case ParamBody::class:
				$value = new ParamBody($this->request->getBody());
				return true;
			default: 
				return false;
		}
	}
	
	public function createFullMagic(\ReflectionMethod $method, Path $cmdParamPath, array $allowedExtensions = null) {
		$pathPattern = new PathPattern();
		
		if ($allowedExtensions !== null) {
			$pathPattern->setAllowedExtensions($allowedExtensions);
			$pathPattern->setExtensionIncluded(false);
		}

		$paramValues = array();
		$pathObjParamNames = array();
		$extParamName = null;
		$numSinglePathParts = 0;
		$queryParams = array();
		foreach ($method->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			if ($paramName == self::PARAM_EXTENSION) {
				$extParamName = $paramName;
				$pathPattern->setExtensionIncluded(false);
				continue;
			}
			
			if (null !== ($paramValue = $this->checkForCmdParam($paramName))) {
				$paramValues[$paramName] = $paramValue;
				continue;
			}
			
			if (null !== ($paramClass = ReflectionUtils::extractParameterClass($parameter))) {
				$value = null;
				if ($this->checkForQueryParam($paramName, $paramClass, $value)) {
					if ($value === null && !$parameter->allowsNull()) {
						return null;
					}
					$paramValues[$paramName] = $value;
					$queryParams[$paramName] = $paramValue;
					continue;
				}

				if ($paramClass->getName() === 'n2n\web\http\controller\ParamPath') {
					$pathObjParamNames[$paramName] = $paramName;
				} else {
					continue;
				}
			}
			
			$isArray = ReflectionUtils::isArrayParameter($parameter);
			
			if (!$isArray) $numSinglePathParts++;
		
			try {
				$pathPattern->addWhitechar(!$parameter->isDefaultValueAvailable(), $isArray, $paramName);
			} catch (PathPatternComposeException $e) {
				throw new ControllerErrorException('Invalid definition of param: ' . $paramName, 
						$method->getFileName(), $method->getStartLine(), null, null, $e);
			}
		}
	
		$matchResult = $pathPattern->matchesPath($cmdParamPath);
		if ($matchResult === null) return null;
			
		$invoker = new MagicMethodInvoker($this->magicContext);
		$invoker->setMethod($method);
		
		if  ($extParamName !== null) {
			$invoker->setParamValue($extParamName, $matchResult->getExtension());
		}
		
		foreach ($paramValues as $paramName => $value) {
			$invoker->setParamValue($paramName, $value);
		}
		
		foreach ($matchResult->getParamValues() as $paramName => $value) {
			if (isset($pathObjParamNames[$paramName])) {
				$invoker->setParamValue($paramName, new ParamPath($value));
			} else {
				$invoker->setParamValue($paramName, $value);
			}
		}
		
		return new InvokerInfo($invoker, $numSinglePathParts, $queryParams);
	}

	public function createSemiMagic(\ReflectionMethod $method, PathPattern $pathPattern) {
		$matchResult = $pathPattern->matchesPath($this->cmdPath);
		if ($matchResult === null) return null;
		
		$paramValues = $matchResult->getParamValues(); 
		$extension = $matchResult->getExtension();
		$queryParams = array();
		
		$invoker = new MagicMethodInvoker($this->magicContext);
		$invoker->setMethod($method);
		
		foreach ($method->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			if (array_key_exists($paramName, $paramValues)) {
				$invoker->setParamValue($paramName, $paramValues[$paramName]);
				continue;
			}
		
			if (null !== ($paramValue = $this->checkForCmdParam($paramName))) {
				$invoker->setParamValue($paramName, $paramValue);
				$queryParams[$paramName] = $paramValue;
				continue;
			}
				
			if (null !== ($paramClass = ReflectionUtils::extractParameterClass($parameter))) {
				$value = null;
				if ($this->checkForQueryParam($paramName, $paramClass, $value)) {
					if ($value === null && !$parameter->allowsNull()) {
						return null;
					}
					$invoker->setParamValue($paramName, $value);
					$queryParams[$paramName] = $paramValue;
					continue;
				}
			} else if ($paramName == self::PARAM_EXTENSION) {
				$invoker->setParamValue(self::PARAM_EXTENSION, $extension);
				continue;
			}
		}
		
		$numSinglePathParts = $pathPattern->size();
		if ($pathPattern->hasMultiple()) $numSinglePathParts--;
		return new InvokerInfo($invoker, $numSinglePathParts, $queryParams);
	}
	
	public function createNonMagic(\ReflectionMethod $method) {
		$invoker = new MagicMethodInvoker($this->magicContext);
		$invoker->setMethod($method);
		
		foreach ($method->getParameters() as $parameter) {
			$paramName = $parameter->getName();
			
			if (null !== ($paramValue = $this->checkForCmdParam($paramName))) {
				$invoker->setParamValue($paramName, $paramValue);
			}
		}
		
		return new InvokerInfo($invoker, 0, array());
	}
}

class InvokerInfo {
	private $invoker;
	private $numSinglePathParts;
	private $queryParams;
	private $interceptors = array();
	
	public function __construct(MagicMethodInvoker $invoker, $numSinglePathParts, array $queryParams) {
		$this->invoker = $invoker;
		$this->numSinglePathParts = $numSinglePathParts;
		$this->queryParams = $queryParams;
	}
	
	public function getInvoker() {
		return $this->invoker;
	}
	
	public function setNumSinglePathParts($numSinglePathParts) {
		$this->numSinglePathParts = $numSinglePathParts;
	}
	
	public function getNumSinglePathParts() {
		return $this->numSinglePathParts;
	}
	
	public function getQueryParams() {
		return $this->queryParams;
	}
	
	/**
	 * @param Interceptor[] $interceptors
	 */
	public function setInterceptors(array $interceptors) {
		$this->interceptors = $interceptors;
	}
	
	/**
	 * @return Interceptor[]
	 */
	public function getInterceptors() {
		return $this->interceptors;
	}
}
