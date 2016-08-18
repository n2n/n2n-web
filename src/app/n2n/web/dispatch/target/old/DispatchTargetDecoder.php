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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\dispatch\target;

use n2n\util\crypt\Cipher;
use n2n\util\JsonDecodeFailedException;
use n2n\util\GzuncompressFailedException;
use n2n\util\StringUtils;
use n2n\util\crypt\CryptRuntimeException;

class DispatchTargetDecoder {
	const FIELD_DEFINITION_CLASS_NAME = 'fieldClassName';
	const FIELD_DEFINITION_FIELD_NAME = 'fieldName';
	
	
	private $cipher;
	private $className;
	private $props;
	private $methodNames = array();
	private $encodedDispatchTargets = array();

	public function __construct(array $encodedDispatchTargets) {
		// @todo umdingseln
		$this->encodedDispatchTargets = $encodedDispatchTargets;
	}
	/**
	* @return \n2n\web\dispatch\target\Cipher
	*/
	public function getCipher() {
		return $this->cipher;
	}
	
	public function setCipher(Cipher $cipher = null) {
		$this->cipher = $cipher;
	}
	
	public function getClassName() {
		return $this->className;
	}
	
	public function getProps() {		
		return $this->props;
	}
	
	public function getMethodNames() {
		return $this->methodNames;	
	}
	
	private function decrypt($target) {
		if (null === $this->cipher) return $target;
		
		return $this->cipher->decrypt($target);
	}
	/**
	 * @param string $target
	 * @throws DispatchTargetDecodingException
	 */
	public function decode() {
		$this->className = null;
		$this->methodNames = array();
		$this->props = new ObjectItem();
		
		foreach ($this->encodedDispatchTargets as $target) {
			try {
				$this->parseJsonObjects(StringUtils::jsonDecode(
						StringUtils::gzuncompress(
								$this->decrypt(base64_decode($target))), true));
				
			} catch (GzuncompressFailedException $e) {
				throw $this->createDispatchTargetDecodingException($e);
			} catch (JsonDecodeFailedException $e) {
				throw $this->createDispatchTargetDecodingException($e);
			} catch (CryptRuntimeException $e) {
				throw $this->createDispatchTargetDecodingException($e);
			}
		}
	}
	/**
	 * Parse the decryptet Dispatchtarget and set the Instance Variables
	 * @param object $target
	 * @throws DispatchTargetDecodingException
	 */
	private function parseJsonObjects($target) {
 		if (!(is_array($target) && isset($target[DispatchTarget::PARAM_CLASS_NAME]) 
 				&& isset($target[DispatchTarget::PARAM_PROPS]) 
 				&& is_array($target[DispatchTarget::PARAM_PROPS]) 
 				&& isset($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_FIELDS]) 
 				&& is_array($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_FIELDS])
 				&& isset($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES]) 
 				&& is_array($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES])
 				&& isset($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_OPTIONS]) 
 				&& is_array($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_OPTIONS])
 				&& isset($target[DispatchTarget::PARAM_METHOD_NAMES]) && is_array($target[DispatchTarget::PARAM_METHOD_NAMES]))) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
 		}
 		if (null === $this->className) {
			$this->className = $target[DispatchTarget::PARAM_CLASS_NAME];
 		} else if ($this->className !== $target[DispatchTarget::PARAM_CLASS_NAME]) {
 			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
 		}
 		
		$this->createFieldsFor($this->props, $target[DispatchTarget::PARAM_PROPS]);
		
		foreach ($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES] as $name) {
			$this->props->registerCustomOption($name);
		}
		
		foreach ($target[DispatchTarget::PARAM_PROPS][DispatchTargetCoder::PARAM_OPTIONS] as $name => $value) {
			$this->props->setOption($name, $value);
		}
		
		$this->methodNames = array_merge($this->methodNames, $target[DispatchTarget::PARAM_METHOD_NAMES]);
	}
	/**
	 * Recursive function to create a field in the fieldlist of the given parentTargetItem
	 * The Created fields' fieldlist is once again filled with the given fieldattributes
	 * 
	 * @param TargetItem $parentTargetItem
	 * @param string $fieldClassName
	 * @param string $fieldName
	 * @param object $fieldAttributes
	 * @throws DispatchTargetDecodingException
	 * @return n2n\web\dispatch\target\TargetItem
	 */
	private function createTargetItemField(TargetItem $parentTargetItem, $fieldClassName, $fieldName, $fieldAttributes) {
		if (!(is_array($fieldAttributes) 
				&& isset($fieldAttributes[DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES]) 
				&& is_array($fieldAttributes[DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES])
				&& isset($fieldAttributes[DispatchTargetCoder::PARAM_OPTIONS]) 
				&& is_array($fieldAttributes[DispatchTargetCoder::PARAM_OPTIONS]))) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		if ($parentTargetItem instanceof ObjectItem) {
			$field = $this->createObjectItemField($parentTargetItem, $fieldClassName, $fieldName, $fieldAttributes);
		} elseif ($parentTargetItem instanceof ObjectArrayItem) {
			$field = $this->createObjectArrayItemField($parentTargetItem, $fieldClassName, $fieldName, $fieldAttributes);
		} elseif ($parentTargetItem instanceof ArrayItem) {
			$field = $this->createArrayItemField($parentTargetItem, $fieldClassName, $fieldName);
		} else {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		
		foreach ($fieldAttributes[DispatchTargetCoder::PARAM_REGISTERED_OPTION_NAMES] as $fieldName) {
			$field->registerCustomOption($fieldName);
		}
		
		foreach ($fieldAttributes[DispatchTargetCoder::PARAM_OPTIONS] as $fieldName => $value) {
			$field->setOption($fieldName, $value);
		}
		return $field;
	}
	/**
	 * Create a field in the fieldlist of the given parentObjectItem
	 * The Created fields' fieldlist is once again filled with the given fieldattributes
	 * @param ObjectItem $parentObjectItem
	 * @param string $fieldClassName
	 * @param string $fieldName
	 * @param object $fieldAttributes
	 * @throws DispatchTargetDecodingException
	 * @return n2n\web\dispatch\target\PropertyItem
	 */
	private function createObjectItemField(ObjectItem $parentObjectItem, $fieldClassName, $fieldName, $fieldAttributes) {
		$field = null;
		switch ($fieldClassName) {
			case (self::CLASS_NAME_OBJECT_ITEM):
				$field = $parentObjectItem->createEiField($fieldName);
				break;		
			case (self::CLASS_NAME_OBJECT_ARRAY_ITEM):
				$field = $parentObjectItem->createObjectArrayField($fieldName);
				break;
			case (self::CLASS_NAME_ARRAY_ITEM):
				$field = $parentObjectItem->createArrayField($fieldName);
				break;
			case (self::CLASS_NAME_PROPERTY_ITEM):
				$field = $parentObjectItem->createPropertyField($fieldName);
				break;
			default:
				 throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		
		if (isset($fieldAttributes[DispatchTargetCoder::PARAM_FIELDS])) {
			$this->createFieldsFor($field, $fieldAttributes);
		}
		return $field;
	}
	/**
	 * Create a field in the fieldlist of the given parentObjectArrayItem
	 * The Created fields' fieldlist is once again filled with the given fieldattributes
	 * @param ObjectArrayItem $parentObjectArrayItem
	 * @param string $fieldClassName
	 * @param string $fieldName
	 * @param object $fieldAttributes
	 * @throws DispatchTargetDecodingException
	 * @return n2n\web\dispatch\target\ObjectItem
	 */
	private function createObjectArrayItemField(ObjectArrayItem $parentObjectArrayItem, $fieldClassName, 
			$fieldName, $fieldAttributes) {
		if ($fieldClassName != self::CLASS_NAME_OBJECT_ITEM) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		$field = $parentObjectArrayItem->createEiField($fieldName);
		$this->createFieldsFor($field, $fieldAttributes);
		return $field;
	}
	/**
	 * Create a field in the fieldlist of the given parentArrayItem
	 * The Created fields' fieldlist is once again filled with the given fieldattributes
	 * @param ArrayItem $parentArrayItem
	 * @param string $fieldClassName
	 * @param string $fieldName
	 * @throws DispatchTargetDecodingException
	 * @return n2n\web\dispatch\target\PropertyItem
	 */
	private function createArrayItemField(ArrayItem $parentArrayItem, $fieldClassName, $fieldName) {
		if ($fieldClassName != self::CLASS_NAME_PROPERTY_ITEM) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		return $parentArrayItem->getField($fieldName);
	}
	/**
	 * Create the fields for a given TargetItem filled with the fields defined in the fieldAttributes
	 * @param TargetItem $field
	 * @param object $fieldAttributes
	 */
	private function createFieldsFor(TargetItem $field, $fieldAttributes) {
		if (!(is_array($fieldAttributes) 
				&& isset($fieldAttributes[DispatchTargetCoder::PARAM_FIELDS]) 
				&& is_array($fieldAttributes[DispatchTargetCoder::PARAM_FIELDS]))) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		foreach ($fieldAttributes[DispatchTargetCoder::PARAM_FIELDS] as $fieldDefinitionString => $attributes) {
			$fieldDefinition = $this->extractFieldDefinitionFromString($fieldDefinitionString);
			$this->createTargetItemField($field, $fieldDefinition[self::FIELD_DEFINITION_CLASS_NAME], 
					$fieldDefinition[self::FIELD_DEFINITION_FIELD_NAME], $attributes);
		}
	}
	/**
	 * Extract the Class name und the name
	 * @param string $fieldDefinitionString
	 * @return array
	 */
	private function extractFieldDefinitionFromString($fieldDefinitionString) {
		$fieldDefinitionArray = explode(DispatchTargetCoder::OBJECT_TYPE_NAME_SEPERATOR, $fieldDefinitionString, 2);
		if ((sizeof($fieldDefinitionArray) != 2)
				|| !(isset($fieldDefinitionArray[0]))
				|| !(isset($fieldDefinitionArray[1]))) {
			throw $this->createDispatchTargetDecodingExceptionTargetItemNotValid();
		}
		return array(self::FIELD_DEFINITION_CLASS_NAME => $fieldDefinitionArray[0], 
				self::FIELD_DEFINITION_FIELD_NAME => $fieldDefinitionArray[1]);
	}
	/**
	 * Creates a simple DispatchTargetDecodingException
	 * @param \Exception $e
	 * @return \n2n\web\dispatch\target\DispatchTargetDecodingException
	 */
	private function createDispatchTargetDecodingException(\Exception $e) {
		return new DispatchTargetDecodingException('Dispatch target could not be decoded. Reason: ' 
				. $e->getMessage(), 0, $e);
	}
	/**
	 * Creates a DispatchTargetDecodingException for invalid targetItem fieldtypes
	 * @return \n2n\web\dispatch\target\DispatchTargetDecodingException
	 */
	private function createDispatchTargetDecodingExceptionTargetItemNotValid() {
		return new DispatchTargetDecodingException('Target item field type not valid.');
	}
	
}
