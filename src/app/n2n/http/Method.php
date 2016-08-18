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
namespace n2n\http;

class Method {
	const GET = 1;
	const POST = 2;
	const PUT = 4;
	const DELETE = 8;
	const HEAD = 16;
	
	public static function createFromString($str) {
		switch ($str) {
			case 'GET':
			case 'get':
			case self::GET:
				return self::GET;
			case 'POST':
			case 'post':
			case self::POST:
				return self::POST;
			case 'PUT':
			case 'put':
			case self::PUT:
				return self::PUT;
			case 'DELETE':
			case 'delete':
			case self::DELETE:
				return self::DELETE;
			case 'HEAD':
			case 'head':
			case self::HEAD:
				return self::HEAD;
			default:
				throw new \InvalidArgumentException('Unknown http method str: ' . $str);
		}
	}
	
	public static function toString($method) {
		$strs = array();
		if ($method & self::GET) {
			$strs[] = 'GET';
		}
		if ($method & self::POST) {
			$strs[] = 'POST';
		}
		if ($method & self::PUT) {
			$strs[] = 'PUT';
		}
		if ($method & self::DELETE) {
			$strs[] = 'DELETE';
		}
		if ($method & self::HEAD) {
			$strs[] = 'HEAD';
		}
		return implode(', ', $strs);
	}
}
