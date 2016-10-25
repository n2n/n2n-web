<?php
namespace n2n\web\http;

class AcceptRange {
	private $acceptMimeTypes;
	protected $acceptStr;
	
	public function __construct(array $acceptMimeTypes) {
		$this->acceptMimeTypes = $acceptMimeTypes;
	}
	
	public function getAcceptMimeTypes() {
		if ($this->acceptStr === null) {
			return $this->acceptMimeTypes;
		}
		
		foreach (explode(';', $this->acceptStr) as $acceptMimeType) {
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
			if ($bestQuality > $acceptMimeType->getRealMimeType()
					|| !$acceptMimeType->matches($mimeType)) {
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

