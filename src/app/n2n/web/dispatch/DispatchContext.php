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
namespace n2n\web\dispatch;

use n2n\web\http\Method;
use n2n\core\container\N2nContext;
use n2n\web\dispatch\model\DispatchModelManager;
use n2n\web\dispatch\map\DispatchJob;
use n2n\web\dispatch\map\CorruptedDispatchException;
use n2n\web\http\Request;
use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\target\build\DispatchTargetCoder;
use n2n\web\dispatch\target\build\DispatchTargetExtractor;
use n2n\context\ThreadScoped;
use n2n\core\config\WebConfig;
use n2n\web\dispatch\model\DispatchModelFactory;
use n2n\core\VarStore;
use n2n\util\crypt\EncryptionDescriptor;
use n2n\core\N2N;
use n2n\io\IoUtils;
use n2n\util\crypt\Cipher;

class DispatchContext implements ThreadScoped {
	const PARAM_DISPATCH_TARGET = '__DISPATCHTARGET';
	const PARAM_PROP_VALUE_PREFIX = 'prop';
	const PARAM_OPTION_PREFIX = 'opts';
	const PARAM_ARRAY_OPTION_PREFIX = 'optd';

	const CRYPT_FOLDER = 'crypt';
	const CRYPT_KEY_FILE_SUFFIX = '.key';
	const CRYPT_IV_FILE_SUFFIX = '.iv';
	
	private $dispatchModelManager;
	private $dispatchTargetCoder;
	private $n2nContext;
	private $analyzed = false;
	private $dispatchJob;
	
	private function _init(WebConfig $webConfig, VarStore $varStore, N2nContext $n2nContext) {
		$this->dispatchModelManager = new DispatchModelManager(new DispatchModelFactory(
				$webConfig->getDispatchPropertyProviderClassNames()));
		$this->dispatchTargetCoder = new DispatchTargetCoder($this->createCipher(
				$webConfig->getDispatchTargetCryptAlgorithm(), $varStore));
		$this->n2nContext = $n2nContext;
		$this->analyzed = !$n2nContext->isHttpContextAvailable();
	}
	

	private function createCipher($algorithm, VarStore $varStore) {
		if ($algorithm === null) return null;
		$algorithm = (string) $algorithm;
		$encryptionDescriptor = new EncryptionDescriptor($algorithm);
	
		$filePathKey = $varStore->requestFileFsPath(VarStore::CATEGORY_SRV, N2N::NS,
				self::CRYPT_FOLDER, $algorithm . self::CRYPT_KEY_FILE_SUFFIX, true, true);
		$filePathIv = $varStore->requestFileFsPath(VarStore::CATEGORY_SRV, N2N::NS,
				self::CRYPT_FOLDER, $algorithm . self::CRYPT_IV_FILE_SUFFIX, true, true);
	
		$key = IoUtils::getContents($filePathKey);
		$iv = IoUtils::getContents($filePathIv);
		if ((strlen($key) != $encryptionDescriptor->getKeySize())
				|| (strlen($iv) != $encryptionDescriptor->getIvSize())) {
			$key = $encryptionDescriptor->generateKey();
			$iv = $encryptionDescriptor->generateIv();
			IoUtils::putContentsSafe($filePathKey, $key);
			IoUtils::putContentsSafe($filePathIv, $iv);
		}
		return new Cipher($encryptionDescriptor, $key, $iv);
	}
	
	/**
	 * @return DispatchModelManager
	 */
	public function getDispatchModelManager() {
		return $this->dispatchModelManager;
	}
	
	/**
	 * @return DispatchTargetCoder
	 */
	public function getDispatchTargetCoder() {
		return $this->dispatchTargetCoder;
	}
	
	/**
	 * @param Dispatchable $dispatchable
	 * @param N2nContext $n2nCotext
	 * @return \n2n\web\dispatch\map\MappingResult
	 */
	public function getOrCreateMappingResult(Dispatchable $dispatchable, N2nContext $n2nCotext) {
		if ($this->dispatchJob !== null 
				&& null !== ($mappingResult = $this->dispatchJob->getMappingResult())) {
			if ($mappingResult->getObject() === $dispatchable) return $mappingResult;
		}
		
		$dispatchModel = $this->dispatchModelManager->getDispatchModel($dispatchable);
		return $dispatchModel->getDispatchItemFactory()->createMappingResult($dispatchable, $n2nCotext);
	}
	
	/**
	 * @param Request $request
	 * @throws CorruptedDispatchException
	 * @throws IllegalStateException
	 */
	public function analyzeRequest() {
		$request = $this->n2nContext->getHttpContext()->getRequest();
		
		$this->analyzed = true;
		$extractor = new DispatchTargetExtractor($this->dispatchTargetCoder);
		$extractor->setUploadDefinitions($request->getUploadDefinitions());
		
		switch ($request->getMethod()) {
			case Method::GET:
			case Method::PUT:
			case Method::DELETE:
				$extractor->setParams($request->getQuery()->toArray());
				break;
			case Method::POST:
				$extractor->setParams($request->getPostQuery()->toArray());
				break;
		}
		
		try {
			$dispatchTarget = $extractor->extractDispatchTarget();
			if ($dispatchTarget !== null) {
				$this->dispatchJob = new DispatchJob($dispatchTarget, $extractor->extractParamInvestigator(), $extractor->getExecutedMethodName());
			}
		} catch (DispatchException $e) {
			throw new CorruptedDispatchException('Corrupted dispatch detected in request.', 0, $e);
		}
	}
	
// 	/**
// 	 * @param array $httpParams
// 	 * @param array $uploadDefinitions
// 	 * @throws CorruptedDispatchException
// 	 * @throws IllegalStateException
// 	 */
// 	private function analyzeParams(array $httpParams, array $uploadDefinitions) {
// 		if ($this->dispatchJob !== null) {
// 			throw new IllegalStateException('Request already analyzed.');
// 		}
		
// 		if (!isset($httpParams[self::PARAM_DISPATCH_TARGET]) 
// 				|| !is_array($httpParams[self::PARAM_DISPATCH_TARGET])) {
// 			return;
// 		}

// 		try {
// 			$dispatchTarget = $this->dispatchTargetCoder->decode($httpParams[self::PARAM_DISPATCH_TARGET]);
// 			$dispatchTarget->applyHttpParams($httpParams, $uploadDefinitions, $methodName);
// 			$this->dispatchJob = new DispatchJob($dispatchTarget, $methodName);
		
// 	}

	private function ensureAnalyzed() {
		if ($this->analyzed) return;
		
		$this->analyzeRequest();
	}
	
	public function hasDispatchJob() {
		$this->ensureAnalyzed();
		
		return $this->dispatchJob !== null;
	}

	/**
	 * @return \n2n\web\dispatch\map\DispatchJob
	 */
	public function getDispatchJob() {
		$this->ensureAnalyzed();
		
		return $this->dispatchJob;
	}
	
	/**
	 * @param Dispatchable $dispatchable
	 * @param string $methodName can be null
	 * @param N2nContext $n2nContext
	 * @return mixed
	 * @throws CorruptedDispatchException
	 */
	public function dispatch(Dispatchable $dispatchable, $methodName, N2nContext $n2nContext) {
		$this->ensureAnalyzed();
		
		if ($this->dispatchJob === null 
				|| !$this->dispatchJob->matches($dispatchable, $methodName)) return null;
				
		if (!$this->dispatchJob->execute($dispatchable, $methodName, $n2nContext)) return false;
		
		if (null !== ($returnValue = $this->dispatchJob->getReturnValue())) {
			return $returnValue;
		}
		
		return true;
	}
}
