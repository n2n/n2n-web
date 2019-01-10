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
namespace n2n\web\http\nav;

use n2n\web\http\controller\ControllerContext;
use n2n\util\uri\Url;
use n2n\util\StringUtils;
use n2n\core\container\N2nContext;
use n2n\util\uri\Linkable;
use n2n\util\uri\UnavailableUrlException;
use n2n\util\type\TypeUtils;

class UrlBuilder {

	/**
	 * @param mixed $arg
	 * @param N2nContext $n2nContext
	 * @param ControllerContext $controllerContext
	 * @param string $suggestedLabel
	 * @throws UnavailableUrlException
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function buildUrlStr($arg, N2nContext $n2nContext = null,
			ControllerContext $controllerContext = null, string &$suggestedLabel = null): string {
		if ($arg === null) {
			throw new UnavailableUrlException(false);
		}

		if (is_scalar($arg)) {
			return (string) $arg;
		}

		if ($arg instanceof UrlComposer && $n2nContext !== null) {
			return (string) $arg->toUrl($n2nContext, $controllerContext, $suggestedLabel);
		}

		if ($arg instanceof Linkable) {
			return (string) $arg->toUrl();
		}

		try {
			return StringUtils::strOf($arg);
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Invalid url expression: ' . TypeUtils::prettyValue($arg), 
					0, $e);
		}
	}

	/**
	 * @param string|UrlComposer|Linkable $arg
	 * @param N2nContext $n2nContext
	 * @param ControllerContext $controllerContext
	 * @param string $suggestedLabel
	 * @throws UnavailableUrlException
	 * @return Url
	 */
	public static function buildUrl($arg, N2nContext $n2nContext = null,
			ControllerContext $controllerContext = null, string &$suggestedLabel = null): Url {
		if ($arg === null) {
			throw new UnavailableUrlException(false);
		}

		if ($arg instanceof UrlComposer && $n2nContext !== null) {
			return $arg->toUrl($n2nContext, $controllerContext, $suggestedLabel);
		}

		if ($arg instanceof Linkable) {
			return $arg->toUrl($suggestedLabel);
		}


		return Url::create($arg);
		
// 		throw new \InvalidArgumentException('Invalid url expression: ' . TypeUtils::prettyValue($arg));
	}
}
