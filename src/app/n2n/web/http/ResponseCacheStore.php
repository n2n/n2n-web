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

use n2n\util\uri\Path;
use n2n\web\http\cache\ResponseCacheId;

/**
 * @deprecated use {@link \n2n\web\http\cache\ResponseCacheStore}
 */
class ResponseCacheStore {

	function __construct(private cache\ResponseCacheStore $decoratedStore) {
	}


	function close(): void {
		$this->decoratedStore->close();
	}

	function isClosed(): bool {
		return $this->decoratedStore->isClosed();
	}


	public function remove(int $method, string $hostName, Path $path, ?array $queryParams, bool $shared): void {
		$this->decoratedStore->remove(new ResponseCacheId($method, $hostName, $path, $queryParams), $shared);
	}


	public function removeByCharacteristics(array $characteristicNeedles, bool $shared): void {
		$this->decoratedStore->removeByCharacteristics($characteristicNeedles, $shared);
	}

	public function clear(bool $shared = null): void {
		$this->decoratedStore->clear();
	}
}
