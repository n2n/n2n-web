<?php
namespace lib\n2n\web\http;

class AcceptMimeType {
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
	
	public static function createFromExression(string $expr) {
		$parts = explode(';', $expr);
		
		$mineTypeParts = explode('/', trim($parts[0]), 2);
		if (count($mineTypeParts) != 2) {
			throw new \InvalidArgumentException('Invalid accept mime type format: ' . $expr);
		}
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

