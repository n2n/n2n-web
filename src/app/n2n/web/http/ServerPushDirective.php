<?php
namespace n2n\web\http;

use n2n\util\type\ArgUtils;

class ServerPushDirective {
	const AS_AUDIO = 'audio';
	const AS_DOCUMENT = 'document';
	const AS_EMBED = 'embed';
	const AS_FETCH = 'fetch';
	const AS_FONT = 'font';
	const AS_IMAGE = 'image';
	const AS_OBJECT = 'object';
	const AS_SCRIPT = 'script';
	const AS_STYLE = 'style';
	const AS_TRCK = 'track';
	const AS_WORKER = 'worker';
	const AS_VIDEO = 'video';
	
	private $header;
	
	/**
	 * @param string $linkUrl
	 * @param string $as
	 */
	function __construct(string $linkUrl, string $as) {
		if (false !== strpos('<', $linkUrl) || false !== strpos('>', $linkUrl)) {
			throw new \InvalidArgumentException('Url cannot contain any of the following characters: <>');
		}
		ArgUtils::valEnum($as, self::getAses());
		$this->header = new Header('Link: <' . $linkUrl . '>; rel=preload; as=' . $as, false);
	}
		
	/**
	 * @return \n2n\web\http\Header
	 */
	function toHeader() {
		return $this->header;
	}
	
	/**
	 * @string[]
	 */
	static function getAses() {
		return array(self::AS_AUDIO, self::AS_DOCUMENT, self::AS_EMBED, self::AS_FETCH, self::AS_FONT, self::AS_IMAGE, 
				self::AS_OBJECT, self::AS_SCRIPT, self::AS_STYLE, self::AS_TRCK, self::AS_WORKER, self::AS_VIDEO);
	}
}

