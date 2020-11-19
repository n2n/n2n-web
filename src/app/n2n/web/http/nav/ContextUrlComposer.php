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

use n2n\core\container\N2nContext;
use n2n\web\http\controller\ControllerContext;
use n2n\util\uri\Url;
use n2n\util\uri\Path;
use n2n\web\http\HttpContextNotAvailableException;
use n2n\util\uri\UnavailableUrlException;
use n2n\web\http\UnknownControllerContextException;

class ContextUrlComposer implements UrlComposer {
	private $toController;
	private $controllerContext;
	private $pathExts = array();
	private $queryExt;
	private $fragment;
	private $ssl;
	private $subsystem;
	private $absolute = false;

	public function __construct($toController) {
		$this->toController = (boolean) $toController;
	}

	/**
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function context() {
		$this->toController = false;
		$this->controllerContext = null;
		return $this;
	}

	/**
	 * @param mixed $controllerContext
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function controller($controllerContext = null) {
		$this->toController = true;
		$this->controllerContext = $controllerContext;
		return $this;
	}

	/**
	 * @param mixed ..$pathExts
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function pathExt(...$pathPartExts) {
		$this->pathExts[] = $pathPartExts;
		return $this;
	}
	
	/**
	 * @param mixed ...$pathExts
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function pathExtEnc(...$pathExts) {
		$this->pathExts = array_merge($this->pathExts, $pathExts);
		return $this;
	}

	/**
	 * @param mixed $query
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function queryExt($queryExt) {
		$this->queryExt = $queryExt;
		return $this;
	}

	/**
	 * @param string $fragment
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function fragment(string $fragment = null) {
		$this->fragment = $fragment;
		return $this;
	}

	/**
	 * @param bool $ssl
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function ssl(bool $ssl = null) {
		$this->ssl = $ssl;
		return $this;
	}

	/**
	 * @param mixed $subsystem
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function subsystem($subsystem) {
		$this->subsystem = $subsystem;
		return $this;
	}
	
	/**
	 * @param bool $absolute
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function absolute(bool $absolute = true) {
		$this->absolute = $absolute;
		return $this;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\nav\UrlComposer::toUrl()
	 */
	public function toUrl(N2nContext $n2nContext, ControllerContext $controllerContext = null, 
			string &$suggestedLabel = null): Url {
		$path = null;
		if ($this->controllerContext === null) {
			if ($this->toController) {
				if ($controllerContext === null) {
					throw new UnavailableUrlException(true, 'No ControllerContext known.');
				}
				$path = $controllerContext->getCmdContextPath();
			} else {
				$path = new Path(array());
			}
		} else if ($this->controllerContext instanceof ControllerContext) {
			$path = $this->controllerContext->getCmdContextPath();
		} else if ($controllerContext !== null) {
			try {
				$path = $controllerContext->getControllingPlan()->getByName($this->controllerContext)
						->getCmdContextPath();
			} catch (UnknownControllerContextException $e) {
				throw new UnavailableUrlException(null, null, $e);
			}
		} else {
			throw new UnavailableUrlException('No ControllerContext available.');
		}

		try {
			return $n2nContext->getHttpContext()->buildContextUrl($this->ssl, $this->subsystem, $this->absolute)
					->extR($path->ext($this->pathExts), $this->queryExt, $this->fragment);
		} catch (HttpContextNotAvailableException $e) {
			throw new UnavailableUrlException(null, null, $e);
		}
	}
}
