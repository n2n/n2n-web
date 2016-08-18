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
 * Bert Hofmänner.......: Idea, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\web\ui\view\impl\html;

use n2n\web\ui\UiComponent;

class HtmlElement implements UiComponent {
	private $tagName;
	private $attrs; 
	private $contents = array();
	
	public function __construct($tagName, array $attrs = null, $content = null) {
		$this->tagName = (string) $tagName;
		$this->attrs = (array) $attrs;
		
		if ($content === null) return;
		if (is_array($content)) {
			$this->contents = $content;
		} else {
			$this->contents[] = $content;
		}
	}
	
	public function setTagName($tagName) {
		$this->tagName = (string) $tagName;
	}
	
	public function getTagName() {
		return $this->tagName;
	}
	
	public function setAttrs(array $attrs) {
		$this->attrs = $attrs;
	}
	
	public function getAttrs() {
		return $this->attrs;
	}
	
	public function buildContentHtml() {
		if (empty($this->contents)) return null;
		
		$contentHtml = '';
		foreach ($this->contents as $content) {
			$contentHtml .= HtmlUtils::contentsToHtml($content);	
		}
		return $contentHtml;
	}
	
	public function appendContent($content) {
		$this->contents[] = $content;
	}
	
	public function appendNl($content = null) {
		if ($content !== null) {
			$this->appendContent($content);
		}
		$this->contents[] = PHP_EOL;
	}
	
	public function getContents(): string {
		$html = '<' . htmlspecialchars($this->tagName) . self::buildAttrsHtml($this->attrs);
		
		if (null !== ($contentHtml = $this->buildContentHtml())) {
			$html .= '>' . $contentHtml . '</' . htmlspecialchars($this->tagName) . '>';
		} else {
			$html .= ' />';
		}
		
		return $html;
	}
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->getContents();
	}
	
	public static function buildAttrsHtml(array $attrs = null) {
		$html = '';
		foreach ((array) $attrs as $name => $value) {
			if ($value === null) continue;
			
			if (is_numeric($name)) {
				$html .= ' ' . htmlspecialchars((string) $value);
			} else {
				$html .= ' ' . htmlspecialchars((string) $name) . '="' . htmlspecialchars((string) $value) . '"';
			}
		}
		return $html;
	}
}
