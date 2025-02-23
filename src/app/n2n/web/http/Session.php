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
namespace n2n\web\http;

use n2n\context\config\LookupSession;

interface Session extends LookupSession {

	/**
	 * @param string $namespace
	 * @param string $key
	 * @return bool
	 */
	public function has(string $namespace, string $key): bool;
	
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 * @param mixed $value
	 */
	public function set(string $namespace, string $key, mixed $value): void;
	
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $namespace, string $key): mixed;
	
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 */
	public function remove(string $namespace, string $key): void;

	function close(): void;
	
//	/**
//	 *
//	 * @param mixed $module
//	 * @param string $key
//	 * @param mixed $obj
//	 * @deprecated
//	 */
//	public function serialize(string $module, string $key, $obj);
//
//	/**
//	 *
//	 * @param mixed $module
//	 * @param string $key
//	 * @return mixed
//	 * @deprecated
//	 */
//	public function unserialize(string $module, string $key);
}

