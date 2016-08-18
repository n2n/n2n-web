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

use n2n\web\ui\UiException;
use n2n\io\ob\OutputBuffer;
use n2n\core\N2N;
use n2n\web\ui\view\View;
use n2n\web\ui\view\ViewCacheReader;
use n2n\web\ui\view\ViewCacheWriter;
use n2n\reflection\ArgUtils;
use n2n\web\ui\ViewStuffFailedException;
use n2n\web\http\Response;
use n2n\web\dispatch\ui\FormHtmlBuilder;
use n2n\web\ui\view\ViewCacheControl;
use n2n\core\module\Module;

class HtmlView extends View {
	private $htmlProperties = null;
	private $htmlBuilder;
	private $formHtmlBuilder;
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\ui\view\View::getContentType()
	 */
	public function getContentType() {
		return 'text/html; charset=' . N2N::CHARSET;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\ui\view\View::compile($contentBuffer)
	 */
	protected function compile(OutputBuffer $contentBuffer) {
		if ($this->htmlProperties === null) {
			$this->htmlProperties = new HtmlProperties();
			
			$contentView = $this->getContentView();
			if ($contentView instanceof HtmlView) {
				$this->htmlProperties->setContentHtmlProperties($contentView->getHtmlProperties());
			}
		}
		
		$this->htmlBuilder = new HtmlBuilder($this, $contentBuffer);
		$this->formHtmlBuilder = new FormHtmlBuilder($this, $contentBuffer);
		
		$attrs = array('view' => $this, 'html' => $this->htmlBuilder, 'formHtml' => $this->formHtmlBuilder);
		if ($this->getN2nContext()->isHttpContextAvailable()) {
			$httpContext = $this->getHttpContext();
			$attrs['httpContext'] = $httpContext;
			$attrs['request'] = $httpContext->getRequest();
			$attrs['response'] = $httpContext->getResponse();
		}
		
		$htmlProperties = $this->htmlProperties;
		parent::bufferContents($attrs,
				function (OutputBuffer $contentBuffer) use ($htmlProperties) {
					$htmlProperties->out($contentBuffer);
				});
				
		$this->htmlBuilder = null;
		$this->formHtmlBuilder = null;
	} 
	
	protected function createImportView(string $viewNameExpression, $params = null, 
			ViewCacheControl $viewCacheControl = null, Module $module = null) {
		$view = parent::createImportView($viewNameExpression, $params, $viewCacheControl, $module);
		if ($view instanceof HtmlView) {
			$view->setHtmlProperties($this->htmlProperties);
		}
		return $view;
	}
	
	public function out($uiComponent) {
		// @todo think
		if ($uiComponent instanceof HtmlView) {
			if (!$uiComponent->isInitialized()) {
				$uiComponent->setHtmlProperties($this->htmlProperties);
			} else if ($this->htmlProperties->getContentHtmlProperties() !== ($htmlProperties = $uiComponent->getHtmlProperties())){
				$this->htmlProperties->merge($htmlProperties);
			} 
		}
		
		parent::out($uiComponent);
	}
	
	public function setHtmlProperties(HtmlProperties $htmlProperties) {
		$this->htmlProperties = $htmlProperties;
	}
	/**
	 * @return HtmlProperties
	 */
	public function getHtmlProperties() {
		return $this->htmlProperties;
	}
	/**
	 * @return \n2n\web\ui\view\impl\html\HtmlBuilder
	 */
	public function getHtmlBuilder() {
		return $this->htmlBuilder;
	}
	/**
	 * @return \n2n\web\dispatch\ui\FormHtmlBuilder
	 */
	public function getFormHtmlBuilder() {
		return $this->formHtmlBuilder;
	}
	
// 	public function readCachedContents(ViewCacheReader $cacheReader) {
// 		parent::readCachedContents($cacheReader);
// 		$this->htmlProperties = $cacheReader->readAttributesObject();
// 	}
	
// 	public function writeCachedContents(ViewCacheWriter $cacheWriter) {
// 		parent::writeCachedContents($cacheWriter);
// 		$cacheWriter->writeAttributesObject($this->htmlProperties);
// 	}
	
	public function initializeFromCache($data) {
		ArgUtils::assertTrue(is_array($data) && isset($data['contents'])
				&& isset($data['htmlProperties']) && isset($data['htmlProperties']) 
				&& $data['htmlProperties'] instanceof HtmlProperties);

		$this->htmlProperties = $data['htmlProperties'];
		parent::initializeFromCache($data['contents']);
	}
	
	public function toCacheData() {
		return array(
				'contents' => parent::toCacheData(),				
				'htmlProperties' => $this->htmlProperties);
	}
	
	public function prepareForResponse(Response $response) {
		parent::prepareForResponse($response);

// 		try {
// 			$this->htmlProperties->validateForResponse();
// 		} catch (ViewStuffFailedException $e) {
// 			throw new ViewStuffFailedException('Could not send view to response: ' . $this->toKownResponseString(), 0, $e);
// 		}
	}
	
	/**
	 * @param HtmlView $view
	 * @return \n2n\web\ui\view\impl\html\HtmlBuilder
	 */
	public static function html(HtmlView $view) {
		return $view->getHtmlBuilder();
	}
	
	/**
	 * @param HtmlView $view
	 * @return \n2n\web\dispatch\ui\FormHtmlBuilder
	 */
	public static function formHtml(HtmlView $view): FormHtmlBuilder {
		return $view->getFormHtmlBuilder();
	}
}

class NoHttpControllerContextAssignetException extends UiException {
		
}

/**
 * hack to provide autocompletion in views
 */
return;
$html = new \n2n\web\ui\view\impl\html\HtmlBuilder();
$formHtml = new \n2n\web\dispatch\ui\FormHtmlBuilder();
