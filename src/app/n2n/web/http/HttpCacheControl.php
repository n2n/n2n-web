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

class HttpCacheControl {
	const DIRECTIVE_PUBLIC = 'public';
	const DIRECTIVE_PRIVATE = 'private';
	const DIRECTIVE_NO_CACHE = 'no-cache';
	const DIRECTIVE_NO_STORE = 'no-store';
	const DIRECTIVE_MUST_REVALIDATE = 'must-revalidate';
	const DIRECTIVE_PROXY_REVALIDATE = 'proxy-revalidate';
	
	private $maxAge;
	private $directives;
	
	/**
	 * 
	 * @param \DateInterval $maxAge
	 * @param array $directives
	 */
	public function __construct(\DateInterval $maxAge = null, array $directives = null) {
		$this->maxAge = $maxAge;
		$this->directives = $directives;
	}
	
	/**
	 * 
	 * @return array
	 */
	public static function getAllDirectives() {
		return array(self::DIRECTIVE_PUBLIC, self::DIRECTIVE_PRIVATE, self::DIRECTIVE_NO_CACHE, 
				self::DIRECTIVE_NO_STORE, self::DIRECTIVE_MUST_REVALIDATE, self::DIRECTIVE_PROXY_REVALIDATE);
	}
	
	/**
	 * 
	 * @param Response $response
	 */
	public function applyHeaders(Response $response) {
		$directives = array();
		if ($this->maxAge === null) {
			if ($this->directives !== null) {
				$directives = $this->directives;
			} else {
// 				$directives[] = 'max-age=0';
				$directives[] = self::DIRECTIVE_NO_CACHE;
// 				$directives[] = self::DIRECTIVE_MUST_REVALIDATE;
// 				$directives[] = self::DIRECTIVE_PRIVATE;
			}
			$response->setHeader('Pragma: no-cache');
		} else {
			$date = new \DateTime();
			$date->setTimezone(new \DateTimeZone('GMT'));
			
			$nowTs = $date->getTimestamp();
			// RFC1123 with GMT
			$response->setHeader('Date: ' . $date->format('D, d M Y H:i:s') . ' GMT');
			$date->add($this->maxAge);
			// RFC1123 with GMT
			$response->setHeader('Expires: ' . $date->format('D, d M Y H:i:s') . ' GMT');
			$directives[] = 'max-age=' . ($date->getTimestamp() - $nowTs); 
			
			if ($this->directives !== null) {
				$directives = array_merge($directives, $this->directives);
			} else {
				$directives[] = self::DIRECTIVE_MUST_REVALIDATE;
				$directives[] = self::DIRECTIVE_PRIVATE;
			}			
		}		
		
		$response->setHeader('Cache-Control: ' . implode(', ', $directives));
	}
}
