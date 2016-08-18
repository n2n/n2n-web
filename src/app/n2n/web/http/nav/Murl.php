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
namespace n2n\http\nav;

use n2n\http\controller\ControllerContext;
use n2n\util\uri\Url;
use n2n\util\uri\Path;
use n2n\core\container\N2nContext;

class Murl {

	/**
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function context() {
		return new MurlComposer(false);
	}
	
	/**
	 * @param mixed $controllerContext
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function controller($controllerContext = null) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->controller($controllerContext);
		return $murlBuilder;
	}

	/**
	 * @param mixed $pathExts
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function pathExt(...$pathExts) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->pathExt(...$pathExts);
		return $murlBuilder;
	}

	/**
	 * @param array $query
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function queryExt(array $query) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->queryExt($query);
		return $murlBuilder;
	}

	/**
	 * @param string $fragment
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function fragment($fragment) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->fragment($fragment);
		return $murlBuilder;
	}

	/**
	 * @param bool $ssl
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function ssl($ssl) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->ssl($ssl);
		return $murlBuilder;
	}

	/**
	 * @param mixed $subsystem
	 * @return \n2n\http\nav\MurlComposer
	 */
	public static function subsystem($subsystem) {
		$murlBuilder = new MurlComposer(true);
		$murlBuilder->subsystem($subsystem);
		return $murlBuilder;
	}
}

class MurlComposer implements Murlable {
	private $toController;
	private $controllerContext;
	private $pathExts = array();
	private $queryExt;
	private $fragment;
	private $ssl;
	private $subsystem;

	public function __construct($toController) {
		$this->toController = (boolean) $toController;
	}

	/**
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function context() {
		$this->toController = false;
		$this->controllerContext = null;
		return $this;
	}

	/**
	 * @param mixed $controllerContext
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function controller($controllerContext = null) {
		$this->toController = true;
		$this->controllerContext = $controllerContext;
		return $this;
	}

	/**
	 * @param mixed ..$pathExts
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function pathExt(...$pathPartExts) {
		$this->pathExts[] = $pathPartExts;
		return $this;
	}
	
	/**
	 * @param mixed ...$pathExts
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function pathExtEnc(...$pathExts) {
		$this->pathExts = array_merge($this->pathExts, $pathExts);
		return $this;
	}

	/**
	 * @param mixed $query
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function queryExt($queryExt) {
		$this->queryExt = $queryExt;
		return $this;
	}

	/**
	 * @param string $fragment
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function fragment(string $fragment = null) {
		$this->fragment = $fragment;
		return $this;
	}

	/**
	 * @param bool $ssl
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function ssl(bool $ssl = null) {
		$this->ssl = $ssl;
		return $this;
	}

	/**
	 * @param mixed $subsystem
	 * @return \n2n\http\nav\MurlComposer
	 */
	public function subsystem($subsystem) {
		$this->subsystem = $subsystem;
		return $this;
	}
	/**
	 * @param View $view
	 * @return Url
	 */
	public function toUrl(N2nContext $n2nContext, ControllerContext $controllerContext = null, 
			string &$suggestedLabel = null): Url {
		$path = null;
		if ($this->controllerContext === null) {
			if ($this->toController) {
				if ($controllerContext === null) {
					throw new UnavailableMurlException(true, 'No ControllerContext known.');
				}
				$path = $controllerContext->getCmdContextPath();
			} else {
				$path = new Path(array());
			}
		} else if ($this->controllerContext instanceof ControllerContext) {
			$path = $this->controllerContext->getCmdContextPath();
		} else {
			$path = $controllerContext->getControllingPlan()->getByName($this->controllerContext)
					->getCmdContextPath();
		}

		try {
			return $n2nContext->getHttpContext()->buildContextUrl($this->ssl, $this->subsystem)
					->extR($path->ext($this->pathExts), $this->queryExt, $this->fragment);
		} catch (\n2n\http\HttpContextNotAvailableException $e) {
			throw new UnavailableMurlException(null, null, $e);
		}
	}
}
