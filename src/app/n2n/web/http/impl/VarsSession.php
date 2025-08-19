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
namespace n2n\web\http\impl;

use n2n\util\io\fs\FsPath;
use n2n\util\StringUtils;
use n2n\util\ex\IllegalStateException;
use n2n\web\http\HttpUtils;
use n2n\web\http\Session;
use n2n\cache\CacheStore;
use n2n\cache\impl\fs\FileCacheStore;
use n2n\util\DateUtils;

class VarsSession implements Session {
// 	const ID_OVERWRITING_GET_PARAM = '_osid';
	const SESSION_CONTEXT_KEY = 'sessionContext';
	const SESSION_COOKIE_SUFFIX = 'Sess';
	const SESSION_VALIDATED_KEY = 'validated';

	private bool $started = false;
	private ?CacheStore $saveCacheStore = null;

	/**
	 *
	 * @param string $applicationName
	 * @param bool $sameSite
	 * @param FsPath|null $phpSessionDirFsPath
	 * @param bool|null $secure
	 */
	public function __construct(private string $applicationName, private ?bool $sameSite = true,
			private ?FsPath $phpSessionDirFsPath = null, private ?bool $secure = true) {
	}

	function setSaveDirFsPath(?FsPath $saveDirFsPath): static {
		$this->ensureNotStarted();
		$this->phpSessionDirFsPath = $saveDirFsPath;
		return $this;
	}

	function getPhpSessionDirFsPath(): ?FsPath {
		return $this->phpSessionDirFsPath;
	}

	function setSaveCacheStore(?CacheStore $saveCacheStore): static {
		$this->ensureNotStarted();
		$this->saveCacheStore = $saveCacheStore;
		return $this;
	}

	function getSaveCacheStore(): ?CacheStore {
		return $this->saveCacheStore;
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
		ini_set('session.use_strict_mode', 1);
		
		ini_set('session.name', $this->applicationName . self::SESSION_COOKIE_SUFFIX);
				
// 		if (isset($_GET[self::ID_OVERWRITING_GET_PARAM])) {
// 			session_id($_GET[self::ID_OVERWRITING_GET_PARAM]);
// 		}

        session_cache_limiter(null);

        if ($this->phpSessionDirFsPath !== null) {
            session_save_path((string) $this->phpSessionDirFsPath);
        }

		if ($this->saveCacheStore !== null) {
			session_set_save_handler(new CacheStoreSessionHandler($this->saveCacheStore), true);
		}

		$cookieParams = ['HttpOnly' => true];

		if ($this->secure !== null) {
			$cookieParams['Secure'] = $this->secure;
		}

		if ($this->sameSite === true) {
			$cookieParams['SameSite'] = 'Strict';
		} elseif ($this->sameSite === false) {
			$cookieParams['SameSite'] = 'None';
			$cookieParams['Secure'] = true;
		}

		session_set_cookie_params($cookieParams);

		HttpUtils::sessionStart();

//		if ($this->dirFsPath !== null) {
//			session_gc();
//		}
		
		if (!isset($_SESSION[$this->applicationName])) {
			$_SESSION[$this->applicationName] = array();
		}
		
		if (!isset($_SESSION[$this->applicationName][self::SESSION_VALIDATED_KEY])) {
			// arg not true, because a second Set-Cookie header would be sent.
			session_regenerate_id();
		
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
	public function isSameSite(): ?bool {
		return $this->sameSite;
	}

	/**
	 * @param bool $sameSite
	 * @return VarsSession
	 */
	public function setSameSite(?bool $sameSite): static {
		$this->ensureNotStarted();
		$this->sameSite = $sameSite;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSecure(): ?bool {
		return $this->sameSite;
	}

	/**
	 * @param bool $secure
	 * @return VarsSession
	 */
	public function setSecure(?bool $secure): static {
		$this->ensureNotStarted();
		$this->secure = $secure;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		$this->ensureStarted();

		return session_id();
	}
	
	public function has(string $namespace, string $key): bool {
		$this->ensureStarted();

		return isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace])
				&& array_key_exists($key, $_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace]);
	}
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 * @param string $value
	 */
	public function set(string $namespace, string $key, $value): void {
		$this->ensureStarted();

		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace])) {
			$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace] = array();
		}

		$_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace][(string) $key] = $value;
	}
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 * @return string
	 */
	public function get(string $namespace, string $key): mixed {
		$this->ensureStarted();

		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace])
				|| !isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace][$key])) {
			return null;
		}

		return $_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace][$key];
	}
	/**
	 * 
	 * @param mixed $namespace
	 * @param string $key
	 */
	public function remove(string $namespace, string $key): void {
		$this->ensureStarted();

		if(!isset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace])) {
			return;
		}
	
		unset($_SESSION[$this->applicationName][self::SESSION_CONTEXT_KEY][(string) $namespace][(string) $key]);
	}

	function close(): void {
		session_write_close();
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


class CacheStoreSessionHandler implements \SessionHandlerInterface {

	function __construct(private CacheStore $cacheStore) {
	}

	public function close(): bool {
		return true;
	}

	public function destroy(string $id): bool {
		$this->cacheStore->remove($id, []);
		return true;
	}

	public function gc(int $maxLifetime): int|false {
		$this->cacheStore->garbageCollect(DateUtils::dateInterval(s: $maxLifetime));
		return 1;
	}

	public function open(string $path, string $name): bool {
		return true;
	}

	public function read(string $id): string|false {
		return $this->cacheStore->get($id, [])?->getData() ?? '';
	}

	public function write(string $id, string $data): bool {
		$this->cacheStore->store($id, [], $data);
		return true;
	}
}