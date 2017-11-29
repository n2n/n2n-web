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

class Murl {

	/**
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function context() {
		return new ContextUrlComposer(false);
	}
	
	/**
	 * @param mixed $controllerContext
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function controller($controllerContext = null) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->controller($controllerContext);
		return $murlBuilder;
	}

	/**
	 * @param mixed $pathExts
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function pathExt(...$pathExts) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->pathExt(...$pathExts);
		return $murlBuilder;
	}

	/**
	 * @param array $query
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function queryExt(array $query) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->queryExt($query);
		return $murlBuilder;
	}

	/**
	 * @param string $fragment
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function fragment($fragment) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->fragment($fragment);
		return $murlBuilder;
	}

	/**
	 * @param bool $ssl
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function ssl($ssl) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->ssl($ssl);
		return $murlBuilder;
	}

	/**
	 * @param mixed $subsystem
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public static function subsystem($subsystem) {
		$murlBuilder = new ContextUrlComposer(true);
		$murlBuilder->subsystem($subsystem);
		return $murlBuilder;
	}
}
