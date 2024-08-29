<?php

namespace n2n\web\http\impl;

use n2n\cache\CacheStore;
use n2n\util\io\fs\FsPath;

class Sessions {

	static function vars(string $applicationName, FsPath $saveDirFsPath = null, CacheStore $saveCacheStore = null): VarsSession {
		$session = new VarsSession($applicationName);
		$session->setSaveDirFsPath($saveDirFsPath);
		$session->setSaveCacheStore($saveCacheStore);
		return $session;
	}

	static function simple(): SimpleSession {
		return new SimpleSession();
	}
}