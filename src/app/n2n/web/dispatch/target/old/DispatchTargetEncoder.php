// <?php
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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
// namespace n2n\dispatch\target;

// use n2n\util\crypt\Cipher;

// class DispatchTargetEncoder {
// 	/**
// 	 * @var Cipher
// 	 */
// 	private $cipher;
	
// 	private $encodedDispatchTarget;
// 	private $dispatchTarget;

// 	public function __construct(DispatchTarget $dispatchTarget) {
// 		$this->dispatchTarget = $dispatchTarget;
// 	}
// 	/**
// 	 * @return \n2n\dispatch\target\Cipher
// 	 */
// 	public function getCipher() {
// 		return $this->cipher;
// 	}
// 	/**
// 	 * @param Cipher $cipher
// 	 */
// 	public function setCipher(Cipher $cipher = null) {
// 		$this->cipher = $cipher;
// 	}
	
// 	public function encode() {
// 		return base64_encode($this->encrypt(gzcompress(json_encode(
// 				$this->createArrayForDispatchTarget($this->dispatchTarget)))));
// 	} 
	
// 	private function encrypt($target) {
// 		if (null === $this->cipher) return $target;
// 		return $this->getCipher()->encrypt($target);
// 	}
	
// 	private function createArrayForDispatchTarget(DispatchTarget $target) {
// 		$dispatchTargetArray = array(
// 				DispatchTarget::PARAM_CLASS_NAME => $target->getClassName(),
// 				DispatchTarget::PARAM_PROPS => self::createArrayForTargetItem($target->getProps()),
// 				DispatchTarget::PARAM_METHOD_NAMES => $target->getMethodNames());
// 		return $dispatchTargetArray;
// 	}
	
// 	private function createArrayForTargetItem(TargetItem $item) {
// 		$itemArray = array(
// 				DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES => $item->getCustomOptionNames(),
// 				DispatchTargetCoder::PARAM_OPTIONS => $item->getOptions());
// 		if ($item instanceof PropertyItem) {
// 			return $itemArray;
// 		}
// 		if ($item instanceof ObjectItem || $item instanceof ObjectArrayItem ||
// 				$item instanceof ArrayItem) {
// 			$fieldsArray = array();
// 			foreach ($item->getFields() as $index => $field) {
// 				$fieldsArray[get_class($field) . DispatchTargetCoder::OBJECT_TYPE_NAME_SEPERATOR . $index] 
// 						= $this->createArrayForTargetItem($field);
// 			}
// 			$itemArray[DispatchTargetCoder::PARAM_FIELDS] = $fieldsArray;
// 			return $itemArray;
// 		}
// 	}
// }
