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

class Method {
	const GET = 1;
	const POST = 2;
	const PUT = 4;
	const DELETE = 8;
	const PATCH = 16;
	const OPTIONS = 32;
	
	const HEAD = 64;
	const TRACE = 128;
	
	public static function createFromString($str, bool $includeMeta = false) {
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
			case 'PATCH':
			case 'patch':
			case self::PATCH:
				return self::PATCH;
			case 'DELETE':
			case 'delete':
			case self::DELETE:
				return self::DELETE;
			case 'OPTIONS':
			case 'options':
			case self::OPTIONS:
				return self::OPTIONS;
			case 'HEAD':
			case 'head':
			case self::HEAD:
				if ($includeMeta) return self::HEAD;
			case 'TRACE':
			case 'trace':
			case self::TRACE:
				if ($includeMeta) return self::TRACE;
			default:
				throw new \InvalidArgumentException('Unknown http method str: ' . $str);
		}
	}
	
	public static function is($str, int $method) {
		return mb_strtoupper($str) == self::toString($method);
	}
	
	public static function toString(int $method) {
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
		if ($method & self::PATCH) {
			$strs[] = 'PATCH';
		}
		if ($method & self::DELETE) {
			$strs[] = 'DELETE';
		}
		if ($method & self::OPTIONS) {
			$strs[] = 'OPTIONS';
		}
		if ($method & self::HEAD) {
			$strs[] = 'HEAD';
		}
		if ($method & self::TRACE) {
			$strs[] = 'TRACE';
		}
		return implode(', ', $strs);
	}
	
	/**
	 * @return string[]
	 */
	static function getAll() {
		return [ self::GET, self::POST, self::PUT, self::DELETE, self::PATCH, self::OPTIONS, self::HEAD, self::TRACE ];
	}
}