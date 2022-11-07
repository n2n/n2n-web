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

use n2n\util\io\fs\FsPath;
use n2n\util\StringUtils;
use n2n\util\type\ArgUtils;
use n2n\util\ex\IllegalStateException;

class VarsSession implements Session {
// 	const ID_OVERWRITING_GET_PARAM = '_osid';
	const SESSION_CONTEXT_KEY = 'sessionContext';
	const SESSION_COOKIE_SUFFIX = 'Sess';
	const SESSION_VALIDATED_KEY = 'validated';

	private bool $started = false;

	/**
	 *
	 * @param string $applicationName
	 * @param bool $sameSite
	 * @param FsPath|null $dirFsPath
	 */
	public function __construct(private string $applicationName, private bool $sameSite = true,
			private ?FsPath $dirFsPath = null) {
	}

	function ensureNotStarted(): void {
		IllegalStateException::assertTrue(!$this->started, 'Session already started');
	}

	function ensureStarted(): void {
		if ($this->started) {
			return;
		}

		$this->started = true;

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

        if ($this->dirFsPath !== null) {
            session_save_path((string) $this->dirFsPath);
        }

		if (!$this->sameSite) {
			session_set_cookie_params(['samesite' => 'None', 'Secure' => true, 'HttpOnly' => true]);
		}

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
	 * @return bool
	 */
	public function isSameSite(): bool {
		return $this->sameSite;
	}

	/**
	 * @param bool $sameSite
	 * @return VarsSession
	 */
	public function setSameSite(bool $sameSite): static {
		$this->ensureNotStarted();
		$this->sameSite = $sameSite;
		return $this;
	}

	/**
	 * @return FsPath
	 */
	public function getDirFsPath(): FsPath {
		return $this->dirFsPath;
	}

	/**
	 * @param FsPath $dirFsPath
	 * @return VarsSession
	 */
	public function setDirFsPath(FsPath $dirFsPath): static {
		$this->ensureNotStarted();
		$this->dirFsPath = $dirFsPath;
		return $this;
	}



	/**
	 * 
	 * @return string
	 */
	public function getId(): string {
		$this->ensureStarted();

		return session_id();
	}
	
	public function has(string $module, string $key): bool {
		$this->ensureStarted();

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
		$this->ensureStarted();

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
		$this->ensureStarted();

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
		$this->ensureStarted();

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
		$this->ensureStarted();

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
		$this->ensureStarted();

		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module])
				|| !isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key])) {
			return null;
		}

		return StringUtils::unserialize($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $module][$key]);
	}
}
