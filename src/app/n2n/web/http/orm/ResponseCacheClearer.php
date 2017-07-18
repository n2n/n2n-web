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
namespace n2n\web\http\orm;

use n2n\reflection\ObjectAdapter;
use n2n\web\http\ResponseCacheStore;

class ResponseCacheClearer extends ObjectAdapter {
	private $responseCacheControl;
	
	private function _init(ResponseCacheStore $responseCacheStore = null) {
		$this->responseCacheControl = $responseCacheStore;
	}
	
	public function _postPersist() {
		if ($this->responseCacheControl === null) return;
		
		$this->responseCacheControl->clear();
	}
	
	public function _postUpdate() {
		if ($this->responseCacheControl === null) return;
		
		$this->responseCacheControl->clear();
	}
	
	public function _postRemove() {
		if ($this->responseCacheControl === null) return;
		
		$this->responseCacheControl->clear();
	}
}
