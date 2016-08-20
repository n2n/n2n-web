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
namespace n2n\web\dispatch\ui;

use n2n\reflection\ReflectionUtils;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\Raw;
use n2n\web\dispatch\target\DispatchTarget;
use n2n\web\dispatch\Dispatchable;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\ArgUtils;
use n2n\io\ob\OutputBuffer;
use n2n\web\http\Method;
use n2n\web\dispatch\map\MappingPathResolver;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\map\MappingResult;
use n2n\web\dispatch\target\build\DispatchTargetEncoder;
use n2n\web\dispatch\DispatchContext;

class Form {
	const ENCTYPE_MULTIPART = 'multipart/form-data';
	const ENCTYPE_URLENCODED = 'application/x-www-form-urlencoded';
	const ENCTYPE_TEXT = 'text/plain';
	
	private $view;
	private $mappingPathResover;
	private $dispatchTarget;
	private $dispatchTargetEncoder;
	private $enctype;
	private $method;
	
	private $openBreakPointKey;
	private $openOutputBuffer;
	private $action;
	private $attrs;
	
	private $basePropertyPaths = array();
	/**
	 * 
	 * @param Dispatchable $dispatchableObject
	 * @param string $method
	 * @param string $enctype
	 * @param bool $unbounded
	 * @throws \InvalidArgumentException
	 */
	public function __construct(HtmlView $view, MappingResult $baseMappingResult, $method, $enctype = null) {
		$this->view = $view;
		$this->mappingPathResover = new MappingPathResolver($this, $baseMappingResult);
		$this->dispatchTarget = new DispatchTarget(get_class($this->mappingPathResover
				->getBaseMappingResult()->getObject()));
		$this->dispatchTargetEncoder = new DispatchTargetEncoder(
				$view->getN2nContext()->lookup(DispatchContext::class)->getDispatchTargetCoder());
		$this->method = Method::createFromString($method);
		if ($enctype !== null) $this->setEnctype($enctype);
	}
	
	/**
	 * @return MappingPathResolver
	 */
	public function getMappingPathResolver(): MappingPathResolver {
		return $this->mappingPathResover;
	}
	
	/**
	 * @return boolean
	 */
	public function isPseudo(): bool {
		return $this->dispatchTargetEncoder->getPseudoBasePropertyPath() !== null;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getEnctype() {
		return $this->enctype;
	}
	
	public function setEnctype($enctype) {
		ArgUtils::valEnum($enctype, array(self::ENCTYPE_MULTIPART, 
				self::ENCTYPE_URLENCODED, self::ENCTYPE_TEXT));
		if ($this->enctype !== null && $this->enctype != $enctype) {
			throw new IllegalStateException('TBD');
		}
		
		$this->enctype = $enctype;
	}
	/**
	 * 
	 * @return DispatchTarget
	 */
	public function getDispatchTarget() {
		return $this->dispatchTarget;
	}
	/**
	 * @return \n2n\web\dispatch\target\build\DispatchTargetEncoder
	 */
	public function getDispatchTargetEncoder(): DispatchTargetEncoder {
		return $this->dispatchTargetEncoder;
	}
	/**
	 * 
	 * @return \n2n\impl\web\ui\view\html\HtmlView
	 */
	public function getView() {
		return $this->view;
	}
	/**
	 * 
	 * @return Raw
	 */
	public function printOpenTag(OutputBuffer $outputBuffer, $action, array $attrs = null) {
		$this->openBreakPointKey = $outputBuffer->breakPoint();
		$this->openOutputBuffer = $outputBuffer;
		$this->action = $action;
		$this->attrs = $attrs;
	}
	/**
	 * 
	 * @return Raw
	 */
	public function printCloseTag() {
		if ($this->openBreakPointKey === null) {
			throw new IllegalStateException('No available open tag.');
		}
		
		$this->openOutputBuffer->insertOnBreakPoint($this->openBreakPointKey, 
				'<form' . HtmlElement::buildAttrsHtml(HtmlUtils::mergeAttrs(array(
						'action' => $this->action, 'method' => mb_strtolower(Method::toString($this->method)), 
						'enctype' => $this->enctype), (array) $this->attrs)) . '>');
		
		$this->openOutputBuffer->append('</form>');
	}
	
	private $labeledIds = array();
	
	const ID_PART_SEPARATOR = '-';
	const ID_FORM_MARK_PART = 'form';
	
	public function createElementId(PropertyPath $propertyPath) {
		if (!isset($this->labeledIds[$propertyPath->__toString()])) {
			$this->labeledIds[$propertyPath->__toString()] = $this->buildId($propertyPath);
		}
		return $this->labeledIds[$propertyPath->__toString()];
	}
	
	private function buildId(PropertyPath $propertyPath) {
		$propertyPath = $this->dispatchTargetEncoder->buildRealPropertyPath($propertyPath);
		
		$idPrefix = ReflectionUtils::encodeNamespace($this->view->getModuleNamespace()) 
				. self::ID_PART_SEPARATOR . self::ID_FORM_MARK_PART;
		
		$idSuffix = '';
		foreach ($propertyPath as $propertyPathPart) {
			$idSuffix .= self::ID_PART_SEPARATOR . $propertyPathPart->getPropertyName();
			if ($propertyPathPart->isArray()) {
				$idSuffix .= self::ID_PART_SEPARATOR . $propertyPathPart->getArrayKey();
			}
		}

		$id = $idPrefix . $idSuffix;
		for ($i = 2; !$this->view->getHtmlProperties()->registerId($id); $i++) {
			$id = $idPrefix . self::ID_PART_SEPARATOR . $i;
		}
		
		return $id;
	}
	
	public function requestElementId(PropertyPath $propertyPath) {
		$id = null;
		$key = $propertyPath->__toString();
		if (isset($this->labeledIds[$key])) {
			$id = $this->labeledIds[$key];
			unset($this->labeledIds[$key]);
		}
		return $id;
	}
	
	public function enhanceElementAttrs(array $attrs, PropertyPath $propertyPath) {
		if (null !== ($id = $this->requestElementId($propertyPath))) {
			$attrs['id'] = $id;
		}
		return $attrs;
	} 

	public function getBasePropertyPaths(): array {
		return $this->basePropertyPaths;
	}
	
	public function setBasePropertyPaths(array $basePropertyPaths) {
		ArgUtils::valArray($basePropertyPaths, PropertyPath::class);
		$this->basePropertyPaths = $basePropertyPaths;
	}
}
