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

use n2n\util\StringUtils;

class VarsSession implements Session {
// 	const ID_OVERWRITING_GET_PARAM = '_osid';
	const SESSION_CONTEXT_KEY = 'sessionContext';
	const SESSION_COOKIE_SUFFIX = 'Sess';
	const SESSION_VALIDATED_KEY = 'validated';
	
	private $applicationName;
	/**
	 * 
	 * @param string $applicationName
	 */
	public function __construct(string $applicationName) {
		$this->applicationName = $applicationName;
		
		// @todo find new place for static functions
		
		ini_set('session.use_only_cookies', true);
		
		ini_set('session.entropy_file', '/dev/urandom');
		ini_set('session.entropy_length', '32');
		ini_set('session.hash_bits_per_character', 6);
		
		ini_set('session.cookie_httponly', true);

		// @todo find a way to use this call only for ssl requests
// 		ini_set('session.cookie_secure', true);
		
		ini_set('session.name', $this->applicationName . self::SESSION_COOKIE_SUFFIX);
				
// 		if (isset($_GET[self::ID_OVERWRITING_GET_PARAM])) {
// 			session_id($_GET[self::ID_OVERWRITING_GET_PARAM]);
// 		}

		session_cache_limiter(null);
		HttpUtils::sessionStart();
		
		if (!isset($_SESSION[$this->applicationName])) {
			$_SESSION[$this->applicationName] = array();
		}
		
		if (!isset($_SESSION[$this->applicationName][self::SESSION_VALIDATED_KEY])) {
			session_regenerate_id(true);
		
			$_SESSION[$this->applicationName] = array();
			$_SESSION[$this->applicationName][self::SESSION_VALIDATED_KEY] = 1;
		}
		
		if (!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY])) {
			$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY] = array();
		}
	}
	/**
	 * 
	 * @return string
	 */
	public function getId(): string {
		return session_id();
	}
	
	public function has(string $module, string $key): bool {
		return isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])
				&& array_key_exists($key, $_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module]);
	}
	/**
	 * 
	 * @param mixed $module
	 * @param string $key
	 * @param string $value
	 */
	public function set(string $module, string $key, $value) {
		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])) {
			$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module] = array();
		}

		$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][(string) $key] = $value;
	}
	/**
	 * 
	 * @param mixed $module
	 * @param string $key
	 * @return string
	 */
	public function get(string $module, string $key) {
		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])
				|| !isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key])) {
			return null;
		}

		return $_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key];
	}
	/**
	 * 
	 * @param mixed $module
	 * @param string $key
	 */
	public function remove(string $module, string $key) {
		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])) {
			return;
		}
	
		unset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][(string) $key]);
	}
	/**
	 * 
	 * @param mixed $module
	 * @param string $key
	 * @param mixed $obj
	 */
	public function serialize(string $module, string $key, $obj) {
		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])) {
			$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module] = array();
		}

		$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][(string) $key] = serialize($obj);
	}
	/**
	 * 
	 * @param mixed $module
	 * @param string $key
	 * @return mixed
	 */
	public function unserialize(string $module, string $key) {
		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])
				|| !isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key])) {
			return null;
		}

		return StringUtils::unserialize($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key]);
	}
}


	







// }
