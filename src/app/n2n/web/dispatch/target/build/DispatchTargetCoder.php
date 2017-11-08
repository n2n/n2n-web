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
namespace n2n\web\dispatch\target\build;

use n2n\util\crypt\Cipher;
use n2n\util\StringUtils;
use n2n\util\GzuncompressFailedException;
use n2n\util\JsonDecodeFailedException;
use n2n\util\crypt\CryptRuntimeException;

class DispatchTargetCoder {
	
// 	const OBJECT_TYPE_NAME_SEPERATOR = '.';
	
	
	private $cipher;
	
	/**
	 * @param Cipher $cipher
	 */
	public function __construct(Cipher $cipher = null) {
		$this->cipher = $cipher;
	}
	
	/**
	 * @return Cipher
	 */
	public function getCipher() {
		return $this->cipher;
	}
	
	/**
	 * @param Cipher $cipher
	 */
	public function setCipher(Cipher $cipher = null) {
		$this->cipher = $cipher;
	}
	
	
	public function encode(array $props) {
		return base64_encode($this->encrypt(gzcompress(json_encode($props))));
	}
	
	private function encrypt($target) {
		if (null === $this->cipher) return $target;
		return $this->getCipher()->encrypt($target);
	}

	private function decrypt($target) {
		if (null === $this->cipher) return $target;
	
		return $this->cipher->decrypt($target);
	}
	
	/**
	 * @param string $code
	 * @return array
	 */
	public function decode($code) {
		try {
			return StringUtils::jsonDecode(StringUtils::gzuncompress(
					$this->decrypt(base64_decode($code))), true);
		} catch (GzuncompressFailedException $e) {
			throw $this->createDispatchTargetDecodingException($e);
		} catch (JsonDecodeFailedException $e) {
			throw $this->createDispatchTargetDecodingException($e);
		} catch (CryptRuntimeException $e) {
			throw $this->createDispatchTargetDecodingException($e);
		}
	}
	
	/**
	 * Creates a simple DispatchTargetDecodingException
	 * @param \Exception $e
	 * @return DispatchTargetDecodingException
	 */
	private function createDispatchTargetDecodingException(\Exception $e = null) {
		return new DispatchTargetDecodingException('Dispatch target could not be decoded.', 0, $e);
	}
	
// 	/**
// 	 * @param DispatchTarget $dispatchTarget
// 	 * @return string
// 	 */
// 	public function encode(DispatchTarget $dispatchTarget) {
// 		$encoder = new DispatchTargetEncoder($dispatchTarget);
// 		$encoder->setCipher($this->cipher);
// 		return $encoder->encode();
// 	}
// 	/**
// 	 * @param array $encodedStrings
// 	 * @return DispatchTarget
// 	 * @throws DispatchTargetDecodingException
// 	 */
// 	public function decode(array $encodedStrings) {
// 		$decoder = new DispatchTargetDecoder($encodedStrings);
// 		$decoder->setCipher($this->cipher);
// 		$decoder->decode();
		
// 		$dispatchTarget = new DispatchTarget($decoder->getClassName());
// 		$dispatchTarget->setProps($decoder->getProps());
// 		$dispatchTarget->setMethodNames($decoder->getMethodNames());
// 		return $dispatchTarget;
// 	}
}
