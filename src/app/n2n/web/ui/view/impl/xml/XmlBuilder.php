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
namespace n2n\web\ui\view\impl\xml;

use n2n\core\N2N;
use n2n\web\ui\Raw;
use n2n\web\ui\view\impl\html\HtmlUtils;

class XmlBuilder {
	private $view;
	
	public function __construct(XmlView $view) {
		$this->view = $view;
	}

	public function xmlHeader() {
		$this->view->out('<?xml version="1.0" encoding="' . strtoupper(N2N::CHARSET) . '"?>' . PHP_EOL);
	}

	public function esc($text) {
		$this->view->out($this->getEsc($text));
	}

	public function getEsc($text) {
		return new Raw(HtmlUtils::escape($text));
	}

	public function cdata($text) {
		$this->view->out($this->getCdata($text));
	}

	public function getCdata($text) {
		return new Raw('<![CDATA[' . $text . ']]>');
	}
}
