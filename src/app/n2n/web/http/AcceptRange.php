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

class AcceptRange {
	private $acceptMimeTypes;
	protected $acceptStr;
	
	public function __construct(array $acceptMimeTypes) {
		$this->acceptMimeTypes = $acceptMimeTypes;
	}
	
	/**
	 * @return AcceptMimeType[]  
	 */
	public function getAcceptMimeTypes() {
		if ($this->acceptStr === null) {
			return $this->acceptMimeTypes;
		}
		
		foreach (explode(',', $this->acceptStr) as $acceptMimeType) {
			try {
				$this->acceptMimeTypes[] = AcceptMimeType::createFromExression($acceptMimeType);
			} catch (\InvalidArgumentException $e) {
				continue;
			}
		}
		
		return $this->acceptMimeTypes;
	}
	
	public function bestMatch(array $mimeTypes, &$bestQuality = null) {
		$bestMimeType = null;
		$bestQuality = 0;
		
		foreach ($this->getAcceptMimeTypes() as $acceptMimeType) {
			if ($bestQuality > $acceptMimeType->getRealQuality()) {
				continue;
			}
				
			foreach ($mimeTypes as $mimeType) {
				if (!$acceptMimeType->matches($mimeType)) continue;
		
				$bestMimeType = $mimeType;
				$bestQuality = $acceptMimeType->getRealQuality();
		
				if ($bestQuality == 1) {
					return $bestMimeType;
				}
		
				break;
			}
		}
		
		return $bestMimeType;
	}
	
	/**
	 * @param string $mimeType
	 * @return float
	 */
	public function matchQuality(string $mimeType) {
		$bestQuality = 0;

		foreach ($this->getAcceptMimeTypes() as $acceptMimeType) {
			if ($bestQuality >= $acceptMimeType->getRealQuality() || !$acceptMimeType->matches($mimeType)) {
				continue;
			}
		
			$bestMimeType = $mimeType;
			$bestQuality = $acceptMimeType->getRealQuality();
			
			if ($bestQuality == 1) {
				return $bestQuality;
			}
		}
		
		return $bestQuality;
	}
	
	/**
	 * @param string $acceptStr
	 * @return \n2n\web\http\AcceptRange
	 */
	public static function createFromStr(string $acceptStr) {
		$acceptRange = new AcceptRange(array());
		$acceptRange->acceptStr = $acceptStr;
		return $acceptRange;
	}
	
}

