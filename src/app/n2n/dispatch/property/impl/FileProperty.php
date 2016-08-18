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
namespace n2n\dispatch\property\impl;

use n2n\dispatch\map\bind\BindingDefinition;
use n2n\core\container\N2nContext;
use n2n\dispatch\map\CorruptedDispatchException;
use n2n\dispatch\target\PropertyItem;
use n2n\io\managed\impl\TmpFileManager;
use n2n\reflection\ArgUtils;
use n2n\dispatch\map\PropertyPathPart;
use n2n\http\UploadDefinition;
use n2n\l10n\MessageCode;
use n2n\N2N;
use n2n\io\managed\impl\FileFactory;
use n2n\dispatch\map\MappingResult;
use n2n\dispatch\target\build\ParamInvestigator;
use n2n\dispatch\target\ObjectItem;
use n2n\dispatch\target\DispatchTargetException;
use n2n\io\UploadedFileExceedsMaxSizeException;
use n2n\io\IncompleteFileUploadException;

class FileProperty extends ManagedPropertyAdapter {
	const OPTION_TMP_FILE = 'tmpFile';
	const OPTION_KEEP_FILE = 'keepFile';

	const MAX_SIZE_ERROR_CODE = 'n2n.dispatch.property.impl.FileProperty.maxSize';
	const INCOMPLETE_ERROR_CODE = 'n2n.dispatch.property.impl.FileProperty.incomplete';
	
	public function __construct($accessProxy, $array, $useArrayObject = null) {
		CommonManagedPropertyProvider::restrictConstraints($accessProxy, 'n2n\io\managed\File', $array, 
				$useArrayObject);
		parent::__construct($accessProxy, $array);
		$this->useArrayObject = $useArrayObject;
	}
	
	public function dispatch(ObjectItem $objectItem, BindingDefinition $bindingDefinition, 
			ParamInvestigator $paramInvestigator, N2nContext $n2nContext) {
		
		$mappingResult = $bindingDefinition->getMappingResult();
		$tmpFileManager = $n2nContext->getLookupManager()->lookup('n2n\io\managed\impl\TmpFileManager');
		ArgUtils::assertTrue($tmpFileManager instanceof TmpFileManager);
		$session = $n2nContext->getHttpContext()->getSession();
	
		if (!$this->isArray()) {
			$propertyItem = null;
			try {
				$propertyItem = $objectItem->createPropertyItem($this->getName());
			} catch (DispatchTargetException $e) {
				throw new CorruptedDispatchException('No PropertyItem.', 0, $e);
			}
			
			$uploadDefinition = $paramInvestigator->findUploadDefinition($propertyItem->getPropertyPath());
					
			$file = null;
			if (null !== ($file = $this->determineFile(new PropertyPathPart($this->getName()), 
					$uploadDefinition, $mappingResult))) {
				$tmpFileManager->add($file, $session);
			} else if ($paramInvestigator->findAttr($propertyItem->getPropertyPath(), self::OPTION_KEEP_FILE)) {
				if (null !== ($qualifiedName = $propertyItem->getAttr(self::OPTION_TMP_FILE))) {
					$file = $tmpFileManager->getSessionFile($qualifiedName, $session);
				} else {
					$file = $this->readValue($mappingResult->getObject());
				}
			}		
				
			$mappingResult->__set($this->getName(), $file);
			return;
		}
	
		
		
		$arrayItem = null;
		try {
			$arrayItem = $objectItem->createArrayItem($this->getName());
		} catch (DispatchTargetException $e) {
			throw new CorruptedDispatchException('No ArrayItem.', 0, $e);
		}
		
		$mapValue = $this->createEmptyValue();
		foreach ($arrayItem->getPropertyItems() as $key => $propertyItem) {
			$uploadDefinition = $paramInvestigator->findUploadDefinition($propertyItem->getPropertyPath());
			
			$file = $this->determineFile($propertyItem->getPropertyPath()->getLast(), 
					$uploadDefinition, $mappingResult);
			if ($file !== null) {
				$tmpFileManager->add($file, $session);
			} else if ($paramInvestigator->findAttr($propertyItem->getPropertyPath(), self::OPTION_KEEP_FILE)
					&& null !== ($qualifiedName = $propertyItem->getOption(self::OPTION_TMP_FILE))) {
				$file = $tmpFileManager->getSessionFile($qualifiedName);
			}
			
			if ($file !== null) $mapValue[$key] = $file;			
		}
		$mappingResult->__set($this->getName(), $mapValue);
	}
	
	private function determineFile(PropertyPathPart $pathPart, $uploadDefinition, 
			MappingResult $mappingResult) {
		if ($uploadDefinition === null) return null;
		CorruptedDispatchException::assertTrue($uploadDefinition instanceof UploadDefinition);
		
		try {
			return FileFactory::createFromUploadDefinition($uploadDefinition);
		} catch (UploadedFileExceedsMaxSizeException $e) {
			$mappingResult->getBindingErrors()->addError($pathPart, 
					new MessageCode(self::MAX_SIZE_ERROR_CODE, array('maxSize' => $e->getMaxSize(), 
									'field' => $mappingResult->getLabel($pathPart),
									'file_name' => $uploadDefinition->getName(), 
									'size' => $uploadDefinition->getSize()), 
							null, N2N::NS));
		} catch (IncompleteFileUploadException $e) {
			$mappingResult->getBindingErrors()->addError($pathPart, 
					new MessageCode(self::INCOMPLETE_ERROR_CODE, array( 
									'field' => $mappingResult->getLabel($pathPart),
									'file_name' => $uploadDefinition->getName()), 
							null, N2N::NS));
		}
	}
}
