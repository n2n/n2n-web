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
namespace n2n\web\ui\view\impl\html;

use n2n\web\ui\UiUtils;
use n2n\web\dispatch\DispatchException;
use n2n\web\ui\UiException;
use n2n\web\dispatch\PropertyExpressionResolver;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\ManagedPropertyType;

class InputTextarea extends HtmlElement {
	/**
	 * 
	 * @param Form $form
	 * @param PropertyPath $propertyPath
	 * @param array $attrs
	 * @throws UiException
	 */
	public function __construct(Form $form, PropertyPath $propertyPath, array $attrs = null) {
		$resolver = null;
		try {
			$resolver = new PropertyExpressionResolver($form->getMappingResult(), $propertyPath);
			$resolver->setConstraint(ManagedPropertyType::TYPE_SCALAR, false);
			
			$propertyType = $resolver->getPropertyType();
			$value = null;
			if ($resolver->hasInvalidRawValue()) { 
				$value = $resolver->getInvalidRawValue();	
			} else {
				$value = $propertyType->createFormValueConverter($form->getView()->getRequest()->getN2nLocale())->convert($resolver->getValue());
			}
			$propName = $form->getDispatchTarget()->registerProperty($propertyPath);
		} catch (DispatchException $e) {
			throw UiUtils::createCouldNotRenderUiComponentException($e);
		}
		
		$elemAttrs = $form->enhanceElementAttrs(array('name' => $propName), $propertyPath);
		
		parent::__construct('textarea', HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs), (string) $value);
	}
}
