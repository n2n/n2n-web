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
namespace n2n\web\http\controller\impl;

use n2n\context\RequestScoped;
use n2n\util\uri\Url;
use n2n\reflection\annotation\AnnoInit;
use n2n\context\annotation\AnnoSessionScoped;
use n2n\context\LookupManager;
use n2n\util\HashUtils;
use n2n\util\ex\IllegalStateException;
use n2n\core\TypeNotFoundException;

class ScrRegistry implements RequestScoped {
	private static function _annos(AnnoInit $ai) {
		$ai->p('sessScrLookupIds', new AnnoSessionScoped());
		$ai->p('appScrLookupIds', new AnnoSessionScoped());	
	}
	
	private $lookupManager;
	private $baseUrl;
	private $sessScrLookupIds = array();
	private $appScrLookupIds = array();
	
	private function _init(LookupManager $lookupManager) {
		$this->lookupManager = $lookupManager;
	}
	
	private function testScrController(string $lookupId) {
		try {
			$scrController = $this->lookupManager->lookup($lookupId);
		} catch (TypeNotFoundException $e) {
			throw new \InvalidArgumentException('Invalid lookup id: ' . $lookupId, 0, $e);
		}
		
		if (!($scrController instanceof ScrController)) {
			throw new \InvalidArgumentException('Invalid ScrController passed.');
		} 
	}
	
	public function setBaseUrl(Url $baseUrl) {
		$this->baseUrl = $baseUrl;
	}
	
	public function getBaseUrl(): Url {
		if ($this->baseUrl === null) {
			throw new IllegalStateException('No base url provided for ScrRegistry');
		}
		return $this->baseUrl;
	}
		
	public function registerApplicationScrController(string $lookupId): Url {
		$this->testScrController($lookupId);
		
		if (!isset($this->appScrLookupIds[$lookupId])) {
			$this->appScrLookupIds[$lookupId] = HashUtils::base36Uniqid();
		}
			
		return $this->getBaseUrl()->pathExt($this->appScrLookupIds[$lookupId]);
	}
	
	public function registerSessionScrController(string $lookupId): Url {
		$this->testScrController($lookupId);
		
		if (!isset($this->sessScrLookupIds[$lookupId])) {
			$this->sessScrLookupIds[$lookupId] = HashUtils::base36Uniqid();
		}
			
		return $this->getBaseUrl()->pathExt($this->sessScrLookupIds[$lookupId]);
	}
	
	public function findScrController(string $key) {
		try {
			return $this->getScrController($key);
		} catch (\n2n\web\http\controller\impl\ScrException $e) {
			return null;
		}
	}
	
	public function getScrController(string $key) {
		foreach ($this->sessScrLookupIds as $sessScrLookupId => $sessKey) {
			if ($key !== $sessKey) continue; 
			
			return $this->lookupScrController($sessScrLookupId);
		}
		
		foreach ($this->appScrLookupIds as $appScrLookupId => $appKey) {
			if ($key !== $appKey) continue;
				
			return $this->lookupScrController($appScrLookupId);
		}
		
		throw new UnknownScrControllerException('No ScrController registered for key: ' . $key);
	}
	
	private function lookupScrController(string $lookupId): ScrController { 
		$scrController = $this->lookupManager->lookup($lookupId);
		
		if ($scrController === null) {
			throw new UnknownScrControllerException('Unknown lookup id: ' . $lookupId);
		}
			
		if (!($scrController instanceof ScrController)) {
			throw new InvalidScrControllerException('Controller does not implement ' . ScrController::class . ': '
					. get_class($scrController));
		}
			
		if (!$scrController->isValid()) {
			throw new InvalidScrControllerException('ScrController is no longer valid: ' . ScrController::class);
		}
		
		return $scrController;
	}
}
