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
use n2n\web\dispatch\map\AnalyzerResult;
use n2n\web\ui\Raw;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\web\dispatch\property\SimpleProperty;
use n2n\web\dispatch\target\TargetItem;
use n2n\web\dispatch\map\InvalidPropertyExpressionException;
use n2n\reflection\ArgUtils;
use n2n\util\col\ArrayUtils;
use n2n\io\managed\File;
use n2n\impl\web\dispatch\property\FileProperty;
use n2n\core\N2N;
use n2n\io\managed\impl\TmpFileManager;
use n2n\web\dispatch\map\PropertyTypeMissmatchException;
use n2n\impl\web\dispatch\property\ObjectProperty;
use n2n\reflection\ReflectionUtils;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\target\PropertyPathMissmatchException;

class FormUiComponentFactory {
	const HTML_ID_PREFIX = 'n2n-';
	const DELETE_LINK_DEFAULT_CODE = 'n2n.dispatch.ui.InputFileLabel.delete_link_label';
	
	private $form;
	private $resolver;
	
	private $dte;
	
	public function __construct(Form $form) {
		$this->form = $form;
		$this->resolver = $this->form->getMappingPathResolver();
		
		$this->dte = $form->getDispatchTargetEncoder();
	}
	
	/**
	 * @param PropertyPath $propertyPath
	 * @param bool $arrayRequird
	 * @return \n2n\web\dispatch\map\AnalyzerResult
	 */
	private function analyzeSimpleProperty(PropertyPath $propertyPath, $arrayRequird) {
		return $this->resolver->analyze($propertyPath, array('n2n\web\dispatch\property\SimpleProperty'), 
				$arrayRequird);
	}
	
	/**
	 * @param TargetItem $targetItem
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	private function buildTargetItemHidden(TargetItem $targetItem) {
		$dtAttrs = $this->form->getDispatchTargetEncoder()->encodeTargetItem($targetItem);
		$dtAttrs['type'] = 'hidden';
		return new HtmlElement('input', $dtAttrs);
	}
	
	/**
	 * @param PropertyPath $propertyPath
	 * @param array $attrs
	 * @param string $type
	 * @param string $secret
	 * @param string $fixedValue
	 * @param string $tagName
	 * @param string $elementContents
	 * @return \n2n\web\ui\Raw
	 */
	public function createInput(PropertyPath $propertyPath, array $attrs = null, string $type = null, 
			$secret = false, $fixedValue = null, $tagName = 'input', $elementContents = null) {
		$result = $this->analyzeSimpleProperty($propertyPath, false);
		
		$inputValue = null;
		if (!$secret) {
			$inputValue = $this->buildInputValue($fixedValue, $result);
		}
	
		if ($type === null) $type = 'text';
		
		$elemAttrs = $this->form->enhanceElementAttrs(array('type' => $type, 
				'name' => $this->dte->buildValueParamName($propertyPath, true), 
				'value' => $inputValue), $propertyPath);
	
		return new HtmlElement($tagName, HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), $elementContents);
	}
	
	/**
	 * @param mixed $fixedValue
	 * @param AnalyzerResult $result
	 * @return scalar
	 */
	private function buildInputValue($fixedValue, AnalyzerResult $result) {
		if ($fixedValue !== null) {
			return $fixedValue;
		} 
		 
		if ($result->hasInvalidRawValue()) {
			return $result->getInvalidRawValue();
		}
		
		return $this->convertValueToScalar($result->getManagedProperty(), $result->getMapValue());
	}
	
	public function convertValueToScalar(SimpleProperty $simpleProperty, $mapValue) {
		$value = null;
		try {
			$value = $simpleProperty->convertMapValueToScalar($mapValue, $this->resolver->getN2nContext());
		} catch (\InvalidArgumentException $e) {
			// @todo better excpetion
			throw $e;
		}
		
		ArgUtils::valTypeReturn($value, 'scalar', $simpleProperty, 'convertMapValueToScalar', true);
		return $value;
	}
	
	public function createTextarea(PropertyPath $propertyPath, array $attrs = null) {
		$result = $this->analyzeSimpleProperty($propertyPath, false);
		
		$elemAttrs = $this->form->enhanceElementAttrs(
				array('name' => $this->dte->buildValueParamName($propertyPath, true)), $propertyPath);
	
		return new HtmlElement('textarea', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), 
				(string) $this->buildInputValue(null, $result));
	}
	
	public function createInputCheckbox(PropertyPath $propertyPath, $value, array $attrs = null, UiComponent $label = null) {
		
		
		$raw = new Raw($this->createTestElement('checkbox', $propertyPath, $value, $attrs));
		$raw->append($label);
		
		$targetItem = null;
		if ($propertyPath->getLast()->isArray()) {
			$targetItem = $this->form->getDispatchTarget()->registerArray($propertyPath->fieldReduced());
		} else {
			$targetItem = $this->form->getDispatchTarget()->registerProperty($propertyPath);
		}
		
		$raw->append($this->buildTargetItemHidden($targetItem));
		return $raw;
	}
	
	public function createInputRadio(PropertyPath $propertyPath, $value, array $attrs = null, bool $enhanceAttrs = true) {
		if ($propertyPath->getLast()->isArray() && null === $propertyPath->getLast()->getArrayKey()) {
			throw new InvalidPropertyExpressionException('Property path with empty brakets are '
					. 'disallowed for radio buttons: ' . $propertyPath->__toString());
		}
		
		return $this->createTestElement('radio', $propertyPath, $value, $attrs, $enhanceAttrs);
	}
	
	private function createTestElement($type, PropertyPath $propertyPath, $value, array $attrs = null, bool $enhanceAttrs = true) {
		$result = $this->analyzeSimpleProperty($propertyPath, false);
		
		$lastPathPart = $propertyPath->getLast();
		$paramName = $this->dte->buildValueParamName($propertyPath, true);
		
		$elemAttrs = array('type' => $type, 'name' => $paramName,
				'value' => $this->convertValueToScalar($result->getManagedProperty(), $value));
		if ($result->testMapValue($value)) {
			$elemAttrs['checked'] = 'checked';
		}
		
		if ($enhanceAttrs) {
			$elemAttrs = $this->form->enhanceElementAttrs($elemAttrs, $propertyPath);
		}
		
		return new HtmlElement('input', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs));
	}
	

	public function createSelect(PropertyPath $propertyPath, array $choicesMap, array $attrs = null, $multiple = false) {
		$result = $this->analyzeSimpleProperty($propertyPath, false);
	
		$elemAttrs = $this->form->enhanceElementAttrs(array('name' => $this->dte->buildValueParamName($propertyPath, true), 
				'multiple' => ($multiple ? 'multiple' : null)), $propertyPath);
	
		$selectElement = new HtmlElement('select', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), '');
		
		$sof = new SelectOptionFactory($result, $multiple, $this);
		$sof->applyChoicesMap($choicesMap, $selectElement, $multiple);
		
		return $selectElement;
	}
	
	/**
	 *
	 * @param Form $form
	 * @param PropertyPath $propertyPath
	 * @param array $attrs
	 * @throws UiException
	 */
	public function createInputFile(PropertyPath $propertyPath, array $attrs = null) {
		$result = $this->resolver->analyze($propertyPath, array('n2n\impl\web\dispatch\property\FileProperty'), false);
		$propertyItem = $this->form->getDispatchTarget()->registerProperty($propertyPath);

		$elemAttrs = $this->form->enhanceElementAttrs(array('type' => 'file', 
				'name' => $this->dte->buildValueParamName($propertyPath, false)), $propertyPath);
		$this->form->setEnctype(Form::ENCTYPE_MULTIPART);
	
		return new HtmlElement('input', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs));
	}
	
	/**
	 * @param PropertyPath $propertyPath
	 * @param array $attrs
	 * @param string $deleteLinkLabel
	 * @return n2n\web\ui\Raw
	 */
	public function createInputFileLabel(PropertyPath $propertyPath, array $attrs = null, $deleteLinkLabel = null) {
		$result = $this->resolver->analyze($propertyPath, array('n2n\impl\web\dispatch\property\FileProperty'), false);
		$propertyItem = $this->form->getDispatchTarget()->registerProperty($propertyPath);
			
		$mapValue = $result->getMapValue();
	
		if (!($mapValue instanceof File) || !$mapValue->isValid()) return null;
			
		$dispatchTarget = $this->form->getDispatchTarget();
		
		$view = $this->form->getView();
		$tmpFileManager = $view->lookup(TmpFileManager::getClass());

		if ($tmpFileManager->containsSessionFile($mapValue, $view->getHttpContext()->getSession())) {
			$propertyItem->setAttr(FileProperty::OPTION_TMP_FILE, $mapValue->getFileSource()->getQualifiedName());
		}
	
		if ($deleteLinkLabel === null) {
			$deleteLinkLabel = $this->form->getView()->getL10nText(self::DELETE_LINK_DEFAULT_CODE,
					null, null, 'n2n\impl\web\dispatch');
		}
		
		if (!isset($attrs['id'])) {
			$attrs['id'] = HtmlUtils::buildUniqueId(self::HTML_ID_PREFIX);
		}
		
		$keepFileOptionName = $this->dte->buildAttrParamName($propertyPath, FileProperty::OPTION_KEEP_FILE);
		
		$htmlId = $attrs['id'];
		$raw = new Raw();
		$raw->append('<span' . HtmlElement::buildAttrsHtml($attrs) . '>');
		$raw->append('<span>' . htmlspecialchars($mapValue->getOriginalName()) . ' (' . round($mapValue->getFileSource()->getSize() / 1024) . ' KB)</span> ');
		$raw->append('<input type="hidden" name="' . htmlspecialchars($keepFileOptionName) . '" value="1" />');
		$raw->append('<a href="#" onclick="(function() { var elem = document.getElementById(\'' . htmlspecialchars(addslashes($htmlId))
				. '\'); elem.parentNode.removeChild(elem); })(); return false;">' . HtmlUtils::contentsToHtml($deleteLinkLabel) . '</a>');
		$raw->append('</span>');
		$raw->append($this->buildTargetItemHidden($propertyItem));
		return $raw;
	}
	
	public function createInputSubmit($methodName, $value = null, array $attrs = null) {
		$elemAttrs = array('type' => 'submit');
		
		if ($value !== null) {
			$elemAttrs['value'] = $value;
		}
		
		$dispatchModel = $this->form->getMappingPathResolver()->getBaseMappingResult()->getDispatchModel();
		$dispatchModel->getMethodByName($methodName);
		
		$elemAttrs['name'] = $this->dte->buildMethodParamName($methodName);
		
		return new HtmlElement('input', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs));
	}
	
	public function createButtonSubmit($methodName, $label, array $attrs = null) {
		$elemAttrs = array('type' => 'submit', 'value' => 1);
	
		$dispatchModel = $this->form->getMappingPathResolver()->getBaseMappingResult()->getDispatchModel();
		$dispatchModel->getMethodByName($methodName);
		
		$elemAttrs['name'] = $this->dte->buildMethodParamName($methodName);
	
		return new HtmlElement('button', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), $label);
	}
	
	public function createHiddenSubmit($methodName, array $attrs = null) {
		$elemAttrs = array('type' => 'hidden', 'value' => 1);
		
		$dispatchModel = $this->form->getMappingPathResolver()->getBaseMappingResult()->getDispatchModel();
		$dispatchModel->getMethodByName($methodName);
		
		$elemAttrs['name'] = $this->dte->buildMethodParamName($methodName);
		
		return new HtmlElement('button', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs));
	}
	
// 	public function createOptionalObjectActivator(PropertyPath $propertyPath) {
// 		$targetItem = null;
		
// 		if ($propertyPath->isEmpty()) {
// 			$targetItem = $this->form->getDispatchTarget()->getObjectItem();
// 		} else {
// 			$result = $this->analyzeOptionalObjectActivator($propertyPath);
// 			if ($result->getManagedProperty()->isArray()) {
// 				$targetItem = $this->form->getDispatchTarget()->registerObjectArray($propertyPath);
// 			} else {
// 				$targetItem = $this->form->getDispatchTarget()->registerObject($propertyPath);
// 			}
// 		}
		
// 		// 		$objectItem->setAttr(ObjectProperty::OPTION_OPTIONAL_OBJECT, 'check');
// 		$targetItem->setAttr(ObjectProperty::OPTION_OBJECT_ACTIVATED, 'check');
// 		return $this->buildTargetItemHidden($targetItem);		
// 	}
	
	public function createOptionalObjectCheckbox(PropertyPath $propertyPath, array $attrs = null, 
			$label = null, array $labelAttrs = null) {
// 		$this->valOptionalObjectItem($propertyPath);
				
		$objectItem = null;
		$checked = null;
				
		if ($propertyPath->isEmpty()) {
			$objectItem = $this->form->getDispatchTarget()->getObjectItem();
			$checked = true;
		} else {
			$result = $this->analyzeOptionalObject($propertyPath);
			$objectItem = $this->form->getDispatchTarget()->registerObject($propertyPath);
			$checked = !$result->getMapValue()->getAttrs();
		}
		
		$attrs = HtmlUtils::mergeAttrs(array('type' => 'checkbox',
				'checked' => ($checked ? 'checked' : null),
				'value' => true, 'name' => $this->dte->buildAttrParamName($propertyPath, 
						ObjectProperty::OPTION_OBJECT_ENABLED)), (array) $attrs);

		$raw = null;
		if ($label === null) {
			$raw = new Raw(new HtmlElement('input', $attrs));
		} else { 
			$labelAttrs = (array) $labelAttrs;
			if (!isset($attrs['id'])) {
				$attrs['id'] = HtmlUtils::buildUniqueId('n2n-');
			}
			$labelAttrs['for'] = $attrs['id'];
			$raw = new Raw(new HtmlElement('input', $attrs) . new HtmlElement('label', $labelAttrs, $label));
		}
		
// 		$objectItem->setAttr(ObjectProperty::OPTION_OPTIONAL_OBJECT, 'check');
		if ($this->isOptionalObjectItemNeeded($propertyPath)) {
			$raw->append($this->buildTargetItemHidden($objectItem));
		}
		return $raw;
	} 
	
	private function isOptionalObjectItemNeeded(PropertyPath $propertyPath) {
		if (!$propertyPath->isEmpty()) {
			return $propertyPath->getLast()->isArray();
		}
		
		$pseudoBasePropertyPath = $this->form->getDispatchTargetEncoder()->getPseudoBasePropertyPath();
		return $pseudoBasePropertyPath !== null && $pseudoBasePropertyPath->getLast()->isArray();
	}
	
	public function createEnabledOptionalObjectHidden(PropertyPath $propertyPath) {
// 		$this->valOptionalObjectItem($propertyPath);
		
		if ($propertyPath->isEmpty()) {
			$objectItem = $this->form->getDispatchTarget()->getObjectItem();
		} else {
			$result = $this->analyzeOptionalObject($propertyPath);
			$objectItem = $this->form->getDispatchTarget()->registerObject($propertyPath);
		}
		
		$raw = new Raw(new HtmlElement('input', array('type' => 'hidden',
				'value' => true, 'name' => $this->dte->buildAttrParamName($propertyPath,
						ObjectProperty::OPTION_OBJECT_ENABLED))));
		
		if ($this->isOptionalObjectItemNeeded($propertyPath)) {
			$raw->append($this->buildTargetItemHidden($objectItem));
		}
		return $raw;
	}
	
	private function analyzeOptionalObject(PropertyPath $propertyPath) {
		return $this->resolver->analyze($propertyPath, array(ObjectProperty::class));
	}
// 	return $this->getDispatchTform->getDispatchTargetEncoder()->registerExternalAttr($propertyPath,
// 			ObjectProperty::OPTION_OBJECT_ENABLED);
	
	private function analyzeOptionalObjectActivator(PropertyPath $propertyPath) {
		$result = $this->resolver->analyze($propertyPath, array(ObjectProperty::class));
		
		if ($propertyPath->getLast()->isArray()) {
			throw new PropertyTypeMissmatchException();
		}
		
		if (null === $result->getManagedProperty()->getCreator()) {
			$objectProperty = $result->getManagedProperty();
				
			throw new PropertyTypeMissmatchException('ObjectProperty '  
					. ReflectionUtils::prettyPropName(get_class($result->getMappingResult()->getObject()),
							$objectProperty->getName()) . ' not ' . ($objectProperty->isArray() ? 'dynamic' : 'optional')
					. '. PropertyPath: ' . $propertyPath);
		}
		
		return $result;
	}
	
// 	private function valOptionalObjectItem(PropertyPath $propertyPath) {
// 		$dt = $this->form->getDispatchTarget();
		
// 		if (!$propertyPath->isEmpty() && $propertyPath->getLast()->isArray()) {
// 			if ($dt->registerObjectArray($propertyPath->fieldReduced())
// 					->getAttr(ObjectProperty::OPTION_OBJECT_ACTIVATED) !== null) {
// 				return;
// 			}
			
// 			throw new PropertyPathMissmatchException('Dynamic object array not activated: ' . $propertyPath);
// 		} 
		
// 		$objectItem = null;
// 		if ($propertyPath->isEmpty()) {
// 			$objectItem = $dt->getObjectItem();
// 			if (null !== $this->form->getDispatchTargetEncoder()->getPseudoBasePropertyPath()) {
// 				return;
// 			}
// 		} else {
// 			$objectItem = $dt->registerObject($propertyPath);
// 		}
		
// 		if ($objectItem->getAttr(ObjectProperty::OPTION_OBJECT_ACTIVATED) !== null) {
// 			return;
// 		}
		
// 		throw new PropertyPathMissmatchException('Optional object not activated: ' . $propertyPath);
// 	}
	
	/**
	 * @param PropertyPath $propertyPath
	 * @param unknown $label
	 * @param array $attrs
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 * @throws AttributeNameIsReservedException
	 */
	public function createLabel(PropertyPath $propertyPath, $label, array $attrs = null) {
		$result = $this->resolver->analyze($propertyPath);
	
		if ($label === null) {
			$label = $result->getLabel();
		}
	
		$elemAttrs = array('for' => $this->form->createElementId($propertyPath));
	
		return new HtmlElement('label', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), $label);
	}
}

class SelectOptionFactory {
	private $result;
	private $multiple;
	private $fucf;
	private $selectedScalarValue;
	
	public function __construct(AnalyzerResult $result, $multiple, FormUiComponentFactory $fucf) {
		$this->result = $result;
		$this->multiple = $multiple;
		$this->fucf = $fucf;
		$this->selectedScalarValue = $this->buildSelectedScalarValues();
	}
	
	private function buildSelectedScalarValues() {
		$managedProperty = $this->result->getManagedProperty();
		
		if (!$this->multiple) {
			return (string)  $this->fucf->convertValueToScalar($managedProperty, $this->result->getMapValue());
		}
		
		$selectedScalarValue = array();
		foreach ($this->result->getMapValue() as $key => $mapValue) {
			$selectedScalarValue[$key] = (string) $this->fucf->convertValueToScalar($managedProperty, $mapValue);
		}
		return $selectedScalarValue;
	}	
	
	public function applyChoicesMap(array $choicesMap, HtmlElement $contextElement, $multiple) {
		foreach ($choicesMap as $key => $value) {
			if ($value instanceof SelectChoice) {
				$contextElement->appendNl(new HtmlElement('option', $this->completeOptionAttrs(
						$value->getValue(), $value->getAttrs()), $value->getLabel()));
				continue;
			}
	
			if ($value instanceof SelectChoiceGroup) {
				$element = new HtmlElement('optiongroup', $value->getAttrs());
				$value->applyOptions($value->getOptions(), $element);
				$contextElement->appendNl($element);
				continue;
			}
	
			if (is_array($value)) {
				$element = new HtmlElement('optiongroup', array('label' => $key));
				$this->applyChoicesMap($value, $element);
				$contextElement->appendNl($element);
				continue;
			}
			
			$contextElement->appendNl(new HtmlElement('option', $this->completeOptionAttrs(
					$key, array()), $value));
		}
	}
	
	private function completeOptionAttrs($mapValue, array $attrs) {
		$scalarValue = (string) $this->fucf->convertValueToScalar($this->result->getManagedProperty(), $mapValue);
		
		$elemAttrs = array('value' => $scalarValue);
		if ($this->multiple) {
			if (ArrayUtils::inArrayLike($scalarValue, $this->selectedScalarValue)) {
				$elemAttrs['selected'] = 'selected';
			}
		} else {
			if ($scalarValue === $this->selectedScalarValue) {
				$elemAttrs['selected'] = 'selected';
			}
		}
	
		return HtmlUtils::mergeAttrs($elemAttrs, $attrs);
	}
}
