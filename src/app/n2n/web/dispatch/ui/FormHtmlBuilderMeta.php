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

use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\web\ui\view\impl\html\HtmlView;
use n2n\l10n\MessageTranslator;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\web\dispatch\property\impl\ObjectProperty;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\dispatch\map\PropertyTypeMissmatchException;
use n2n\web\ui\view\ViewErrorException;
use n2n\reflection\magic\CanNotFillParameterException;
use n2n\web\dispatch\map\InvalidPropertyExpressionException;

class FormHtmlBuilderMeta {
	private $view;
	
	public function __construct(HtmlView $view) {
		$this->view = $view;
	}
	
	public function propPath($propertyExpression = null): PropertyPath {
		return $this->createPropertyPath($propertyExpression);
	}
	
	public function realPropPath($propertyExpression = null): PropertyPath {
		return $this->createRealPropertyPath($propertyExpression);
	}
	/**
	 * @param string $propertyExpression
	 * @return \n2n\web\dispatch\map\PropertyPath
	 */
	public function createPropertyPath($propertyExpression = null, bool $emptyAllowed = false): PropertyPath {
		if ($propertyExpression instanceof PropertyPath) {
			return $propertyExpression;
		}
		
		$basePropertyPaths = $this->getForm()->getBasePropertyPaths();
		if (0 != sizeof($basePropertyPaths)) {
			if ($propertyExpression === null) {
				return end($basePropertyPaths);
			}

			return end($basePropertyPaths)->ext($propertyExpression);
		}
		
		$propertyPath = PropertyPath::createFromPropertyExpression($propertyExpression);
		
		if (!$emptyAllowed && $propertyPath->isEmpty()) {
			throw new InvalidPropertyExpressionException('Property expression is empty.');
		}

		return $propertyPath;
	}
	
	public function createRealPropertyPath($propertyExpression, bool $emptyAllowed = false) {
		$propertyPath = $this->createPropertyPath($propertyExpression, $emptyAllowed);
		
		$form = $this->view->getHtmlProperties()->getForm();
		if ($form === null) {
			throw new IllegalFormStateException('Form not open.');
		}
		
		$pseudoPropertyPath = $form->getDispatchTargetEncoder()->getPseudoBasePropertyPath();
		if ($pseudoPropertyPath !== null) {
			return $pseudoPropertyPath->ext($propertyPath);
		}
		
		return $propertyPath;
	}
	
	public function pushBasePropertyPath(PropertyPath $basePath) {
		$form = $this->getForm();
		$basePropertyPaths = $form->getBasePropertyPaths();
		$basePropertyPaths[] = $basePath;
		$form->setBasePropertyPaths($basePropertyPaths);
	}
	
	public function popBasePropertyPath() {
		$form = $this->getForm();
		$basePropertyPaths = $form->getBasePropertyPaths();
		$basePath = array_pop($basePropertyPaths);
		$form->setBasePropertyPaths($basePropertyPaths);
		if (!$basePath) return null;
		return $basePath;
	}
	
	public function isFormOpen() {
		return $this->view->getHtmlProperties()->getForm() !== null;
	}
	/**
	 * @return Form
	 */
	public function getForm() {
		if (null !== ($form = $this->view->getHtmlProperties()->getForm())) {
			return $form;
		}
		
		throw new IllegalFormStateException('No Form open.');
	}
	
	public function hasErrors($propertyExpression = null, $recursive = true) {
		$propertyPath = $this->createPropertyPath($propertyExpression, true);
		
		$resolver = $this->getForm()->getMappingPathResolver();
		if ($propertyPath->isEmpty()) {
			return $resolver->getBaseMappingResult()->testErrors(null, $recursive);
		}
		
		$result = $resolver->analyze($propertyPath, null, null);
		return $result->getMappingResult()->testErrors($result->getLastPathPart(), $recursive);
	}
	
	public function getMessages($propertyExpression = null, bool $recursive = true, bool $translate = true, 
			int $max = null, bool $markAsProcessed = true, bool $unprocessedOnly = true) {
		$propertyPath = $this->createPropertyPath($propertyExpression, true);
		
		$resolver = $this->getForm()->getMappingPathResolver();
		
		if ($propertyPath->isEmpty()) {
			$messages = $resolver->getBaseMappingResult()->filterErrorMessages();
		} else {
			$result = $resolver->analyze($propertyPath, null, null);
			$messages = $result->getMappingResult()->filterErrorMessages($result->getLastPathPart(), $recursive);
		}
		
		if ($unprocessedOnly) {
			foreach ($messages as $key => $message) {
				if ($message->isProcessed()) unset($messages[$key]);
			}
		}
		
		if ($max !== null && $max > count($messages)) {
			$messages = array_slice($messages, 0, $max);
		}
		
		if ($markAsProcessed) {
			foreach ($messages as $key => $message) {
				$message->setProcessed(true);
			}
		}
		
		if (!$translate) return $messages;
		
		$n2nContext = $this->view->getN2nContext();
		$messageTranslator = new MessageTranslator($this->view->getModuleNamespace(),
				$n2nContext->getN2nLocale());
		return $messageTranslator->translateAll($messages);
	}
	
	public function getMapValue($propertyExpression = null) {
		$propertyPath = $this->createPropertyPath($propertyExpression, true);
		$resolver = $this->getForm()->getMappingPathResolver();
		
		if ($propertyPath->isEmpty()) {
			return $resolver->getBaseMappingResult(); 
		}
		
		$result = $resolver->analyze($propertyPath, null, null);
		return $result->getMapValue();
	}
	
	public function getArrayKey($propertyExpression = null) {
		return $this->createPropertyPath($propertyExpression)->getLast()->getArrayKey();
	}
	
	public function getResolvedArrayKey($propertyExpression = null) {
		$propertyPath = $this->createPropertyPath($propertyExpression);
		
		$lastPathPart = $propertyPath->getLast();
		if ($lastPathPart->isArrayKeyResolved()) {
			return $lastPathPart->getResolvedArrayKey();
		}
		
		$this->getForm()->getMappingPathResolver()->analyze($propertyPath, null, true);
		return $propertyPath->getResolvedArrayKey();
	}

	public function arrayProps($arrayPropertyExpression, \Closure $closure, $min = null, 
			$max = null, $emptyBrackets = false) {
		$resolver = $this->getForm()->getMappingPathResolver();
		
		$propertyPath = $this->createPropertyPath($arrayPropertyExpression);
		$result = $resolver->analyze($propertyPath, null, true);
	
		$closureInvoker = new MagicMethodInvoker($this->view->getN2nContext());
		$closureInvoker->setMethod(new \ReflectionFunction($closure));
		
		$mapValues = $result->getMapValue();
		
		if ($max === null) $max = count($mapValues);
		if ($min === null) $min = 0;
		
		$i = 0;
		foreach ($mapValues as $key => $mapValue) {
			if (++$i > $max) return;
	
			$newPropertyPath = null;
			if (!$emptyBrackets) {
				$newPropertyPath = $propertyPath->fieldExt($key);
			} else {
				$newPropertyPath = $propertyPath->fieldExt(null);
				$newPropertyPath->getLast()->resolveArrayKey($key);
			}
			
			$this->callPropClosure($closureInvoker, $newPropertyPath);
		}
	
		if ($min === null) return;
		
		for (; $i < $min; $i++) {
			$this->callPropClosure($closureInvoker, $propertyPath->fieldExt(null));
		}
	}
	
	public function objectProps($objectPropertyExpression, \Closure $closure) {
		$propertyPath = $this->createPropertyPath($objectPropertyExpression);
		$resolver = $this->getForm()->getMappingPathResolver();
		$mappingResult = null;
		if ($propertyPath->isEmpty()) {
			$mappingResult = $resolver->getBaseMappingResult();
		} else {
			$mappingResult = $resolver->analyze($propertyPath, 
					array('n2n\web\dispatch\property\impl\ObjectProperty'), false)->getMapValue();
		}

		if ($mappingResult === null) {
			// @todo think; throw exception or not. Maybe add a parameter $lenient
			return;
		}

		$closureInvoker = new MagicMethodInvoker($this->view->getN2nContext());
		$closureInvoker->setMethod(new \ReflectionFunction($closure));
		foreach ($mappingResult->getDispatchModel()->getProperties() as $property) {
			if ($propertyPath->isEmpty()) {
				$this->callPropClosure($closureInvoker, new PropertyPath(array(
						new PropertyPathPart($property->getName()))));
			} else {
				$this->callPropClosure($closureInvoker, $propertyPath->ext($property->getName()));
			}
		}
	}
	
	private function callPropClosure(MagicMethodInvoker $closureInvoker,
			PropertyPath $propertyPath) {
		$this->pushBasePropertyPath($propertyPath);
	
		try {
			$closureInvoker->invoke();
		} catch (CanNotFillParameterException $e) {
			$func = $closureInvoker->getMethod();
			throw new ViewErrorException('Invalid closure signature.', 
					$func->getFileName(), $func->getStartLine(), null, null, $e);
		}
	
		$this->popBasePropertyPath();
	}
	
	
	/**
	 * @param PropertyPath $propertyPath
	 * @throws PropertyTypeMissmatchException
	 * @return \n2n\web\dispatch\mag\Mag
	 */
	public function lookupOption(PropertyPath $propertyPath) {
		$form = $this->getForm();

		$result = $form->getMappingPathResolver()->analyze($propertyPath, null, null);
		$dispatchable = $result->getMappingResult()->getObject();
		$propertyName = $result->getManagedProperty()->getName();
		
		if (!($dispatchable instanceof MagDispatchable)
				|| $propertyPath->getLast()->isArray()) {
			throw new PropertyTypeMissmatchException(get_class($dispatchable) . ' - '
					. $propertyPath->getLast()->__toString() . ' is no Option. (Path: ' 
					. $propertyPath->__toString() . ')');
		}
		
		return $dispatchable->getMagCollection()->getMagByPropertyName(
				$propertyPath->getLast()->getPropertyName());		
	}
}
