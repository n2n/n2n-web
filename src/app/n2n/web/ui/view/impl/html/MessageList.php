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

use n2n\l10n\Message;
use n2n\web\ui\UiComponent;

class MessageList implements UiComponent {
	private $messages = array();
	private $html = '';
	
	public function __construct(array $messages, array $attrs = null, array $errorAttrs = null, 
			array $warnAttrs = null, array $infoAttrs = null) {
		$this->messages = $messages;
		if (empty($messages)) return;
	
		$this->html = '<ul' . HtmlElement::buildAttrsHtml($attrs) . '>' . "\r\n";
		foreach ($messages as $message) {
			$attrs = null;
			switch ($message->getSeverity()) {
				case Message::SEVERITY_ERROR:
					$attrs = $errorAttrs;
					break;
				case Message::SEVERITY_WARN:
					$attrs = $warnAttrs;
					break;
				case Message::SEVERITY_INFO:
					$attrs = $infoAttrs;
					break;
			}
			
			$liElement = new HtmlElement('li', $attrs, $message->__toString());
			$this->html .= $liElement->getContents() . "\r\n";
		}		
		$this->html .= '</ul>';
	}
	
	public function getMessages() {
		return $this->messages;
	}
	
	public function getContents(): string {
		return $this->html;
	}
}
