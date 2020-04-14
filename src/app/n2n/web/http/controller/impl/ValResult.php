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
namespace n2n\web\http\controller\impl;

use n2n\web\http\controller\ControllerContext;
use n2n\web\http\controller\ControllingPlanException;
use n2n\reflection\TypeExpressionResolver;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\IllegalStateException;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\http\controller\ControllingPlan;
use n2n\web\http\payload\Payload;
use n2n\web\http\NoHttpRefererGivenException;
use n2n\web\http\nav\UrlComposer;
use n2n\web\http\payload\impl\Redirect;
use n2n\web\dispatch\DispatchContext;
use n2n\web\dispatch\Dispatchable;
use n2n\web\http\ResponseCacheControl;
use n2n\web\http\HttpCacheControl;
use n2n\web\ui\ViewFactory;
use n2n\util\type\CastUtils;
use n2n\web\http\controller\Controller;
use n2n\core\container\N2nContext;
use n2n\web\http\Response;
use n2n\web\http\Request;
use n2n\web\http\HttpContext;
use n2n\web\http\controller\InvokerInfo;
use n2n\web\ui\view\View;
use n2n\web\http\nav\Murl;
use n2n\web\http\BadRequestException;
use n2n\core\container\Transaction;
use n2n\web\http\payload\impl\JsonPayload;
use n2n\web\http\payload\impl\HtmlPayload;
use n2n\web\http\payload\impl\HtmlUiPayload;
use n2n\io\managed\File;
use n2n\web\http\payload\impl\FilePayload;
use n2n\io\fs\FsPath;
use n2n\web\http\payload\impl\FsPathPayload;
use n2n\web\ui\UiComponent;
use n2n\util\type\ArgUtils;
use n2n\web\http\controller\Interceptor;
use n2n\web\http\controller\InterceptorFactory;
use n2n\web\http\nav\UrlBuilder;
use n2n\util\uri\Linkable;
use n2n\util\uri\UnavailableUrlException;
use n2n\web\http\payload\impl\XmlPayload;
use n2n\validation\build\ValidationResult;
use n2n\validation\build\ErrorMap;

class ValResult implements ValidationResult {
	private $origValidationResult;
	private $cu;
	
	function __construct(ValidationResult $origValidationResult, ControllingUtils $cu) {
		$this->origValidationResult = $origValidationResult;
		$this->cu = $cu;
	}
	
	function hasErrors(): bool {
		return $this->origValidationResult->hasErrors();
	}
	
	function getErrorMap(): ErrorMap {
		return $this->origValidationResult->getErrorMap();	
	}
	
	/**
	 * Sends a default error report as json if de ValidationResult contains any errors. 
	 * @return boolean true if the ValidationResult contains and a error report has been sent.
	 */
	function sendErrJson() {
		if (!$this->hasErrors()) {
			return false;
		}
		
		$this->cu->sendJson([
			'status' => 'ERR',
			'errorMap' => $this->getErrorMap()
		]);
		
		return true;
	}
}
