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

class AcceptMimeType {
	const TYPE_SEPARATOR = '/';
	const WILDCARD = '*';
	
	private $type;
	private $subtype;
	private $quality;
	private $params;
	
	public function __construct(string $type = null, string $subtype = null, 
			float $quality = null, array $params = array()) {
		if ($quality !== null && ($quality < 0 || $quality > 1)) {
			throw new \InvalidArgumentException('Invalid quality: ' . $quality);
		}
				
		$this->type = $type;
		$this->subtype = $subtype;
		$this->quality = $quality;
		$this->params = $params;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getSubtype() {
		return $this->subtype;
	}
	
	public function getQuality() {
		return $this->quality;
	}
	
	public function getRealQuality() {
		if ($this->quality === null) return 1;
		return $this->quality;
	}
	
	public function getParams() {
		return $this->params;
	}
	
	public function matches($mimeType) { 
		$mimeTypeParts = self::parseMimeTypeParts($mimeType);
		
		if ($mimeTypeParts[0] != self::WILDCARD && $this->type !== null
				&& $mimeTypeParts[0] != $this->type) {
			return false;
		}

		if ($mimeTypeParts[1] != self::WILDCARD && $this->subtype !== null
				&& $mimeTypeParts[1] != $this->subtype) {
			return false;
		}
		
		return true;
	}
	
	private static function parseMimeTypeParts($expr) {
		$mineTypeParts = explode('/', trim($expr), 2);
		if (count($mineTypeParts) != 2) {
			throw new \InvalidArgumentException('Invalid mime type format: ' . $expr);
		}
		return $mineTypeParts;
	}	
	
	public static function createFromExression(string $expr) {
		$parts = explode(';', $expr);

		$mineTypeParts = self::parseMimeTypeParts(array_shift($parts));
		$type = ($mineTypeParts[0] === self::WILDCARD ? null : $mineTypeParts[0]);
		$subType = ($mineTypeParts[1] === self::WILDCARD ? null : $mineTypeParts[1]);
		
		$quality = null;
		$params = array();
		foreach ($parts as $part) {
			$part = trim($part);
			$paramParts = explode('=', $part, 2);
			if (count($paramParts) != 2) {
				throw new \InvalidArgumentException('Invalid part in accept mime type: ' . $expr);
			}
			
			$key = $paramParts[0];
			if ($key == 'q') {
				$quality = (float) $paramParts[1];
				continue;
			}
			
			$params[$key] = $paramParts[1];
		}
		
		try {
			return new AcceptMimeType($type, $subType, $quality, $params);
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException('Invalid accept mime type format: ' . $expr, 0, $e);
		}
	}
}

