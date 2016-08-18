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

use n2n\io\managed\File;
use n2n\core\N2N;
use n2n\web\ui\UiComponent;
use n2n\web\ui\Raw;
use n2n\io\ob\OutputBuffer;
use n2n\web\ui\view\impl\html\HtmlView;
use n2n\web\ui\UiException;
use n2n\io\managed\img\ThumbStrategy;
use n2n\util\uri\Url;

class HtmlBuilder {
	private $view;
	private $meta;
	private $contentBuffer;
	private $request;

	/**
	 * @param HtmlView $view
	 * @param OutputBuffer $contentBuffer
	 */
	public function __construct(HtmlView $view, OutputBuffer $contentBuffer) {
		$this->view = $view;
		$this->meta = new HtmlBuilderMeta($view);
		$this->contentBuffer = $contentBuffer;
	}
	
	/**
	 * @return \n2n\web\ui\view\impl\html\HtmlBuilderMeta
	 */
	public function meta() {
		return $this->meta;
	}

	/**
	 * @param array $attrs
	 */
	public function headStart(array $attrs = null) {
		$this->view->out('<head' . HtmlElement::buildAttrsHtml($attrs) . '>' . "\r\n");
	}

	/**
	 * 
	 */
	public function headContents() {
		$htmlProperties = $this->meta->getHtmlProperties();
		
		if (!$htmlProperties->containsName(HtmlBuilderMeta::HEAD_TITLE_KEY)) {
			$htmlProperties->set(HtmlBuilderMeta::HEAD_TITLE_KEY, new HtmlElement('title', null,
					N2N::getAppConfig()->general()->getPageName()));
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_META_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_META_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_TITLE_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_TITLE_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_LINK_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_LINK_KEY);
		}
		
		if (!$this->contentBuffer->hasBreakPoint(HtmlBuilderMeta::HEAD_SCRIPT_KEY)) {
			$this->contentBuffer->breakPoint(HtmlBuilderMeta::HEAD_SCRIPT_KEY);
		}
	}

	/**
	 * 
	 */
	public function headEnd() {
		$this->headContents();

		$this->view->out('</head>' . "\r\n");
	}

	/**
	 * @param array $attrs
	 */
	public function bodyStart(array $attrs = null) {
		$this->view->out('<body' . HtmlElement::buildAttrsHtml($attrs) . '>' . "\r\n");
		$this->bodyStartContents();
	}

	/**
	 * 
	 */
	public function bodyStartContents() {
		$this->contentBuffer->breakPoint(HtmlBuilderMeta::TARGET_BODY_START);
	}

	/**
	 * 
	 */
	public function bodyEndContents() {
		$this->contentBuffer->breakPoint(HtmlBuilderMeta::TARGET_BODY_END);
	}

	/**
	 * 
	 */
	public function bodyEnd() {
		$this->bodyEndContents();
		$this->view->out('</body>' . "\r\n");
	}

	/*
	 * BASIC UTILS
	 */

	/**
	 * @param mixed $contents
	 */
	public function out($contents) {
		if ($contents instanceof UiComponent) {
			$this->view->out($contents);		
			return;
		}
		
		$this->esc($contents);
	}
	
	public function getUic($arg) {
		if ($arg instanceof UiComponent) {
			return $arg;
		}
		
		return new Raw($arg);
	}
	
	/**
	 * @param mixed $contents
	 */
	public function esc($contents) {
		$this->view->out($this->getEsc($contents));
	}
	
	/**
	 * @param mixed $contents
	 * @return \n2n\web\ui\Raw
	 */
	public function getEsc($contents) {
		return new Raw(HtmlUtils::escape($contents));
	}
	/**
	 * @param mixed $contents
	 * @param array $attrs
	 * @param string $strict
	 */
	public function escP($contents, array $attrs = null, bool $strict = false) {
		$this->view->out($this->getEscP($contents, $attrs, $strict));
	}
	/**
	 * @param mixed $contents
	 * @param string $strict
	 * @return \n2n\web\ui\Raw
	 */
	public function getEscP($contents, array $attrs = null, bool $strict = false): UiComponent {
	    $attrsHtml = HtmlElement::buildAttrsHtml($attrs);
		$html = HtmlUtils::escape($contents, function ($html) use ($strict, $attrsHtml) {
			$html = str_replace("\n\n", '</p><p' . $attrsHtml . '>', str_replace("\r", '', $html));
			if (!$strict) $html = nl2br($html);
			return $html;
		});
		
		return new Raw('<p' . $attrsHtml . '>' . $html . '</p>');
	}
	/**
	 * @param string $contents
	 */
	public function escBr($contents) {
		$this->view->out($this->getEscBr($contents));
	}
	/**
	 * @param string $string
	 * @return \n2n\web\ui\Raw
	 */
	public function getEscBr($contents) {
		$html = HtmlUtils::escape($contents, function ($html) {
			return nl2br($html);
		});
		
		return new Raw($html);
	}
	
	/*
	 * NAVIGATION UTILS
	 */

	/**
	 * @param mixed $murl
	 * @param mixed $label
	 * @param array $attrs
	 */
	public function link($murl, $label = null, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null) {
		$this->view->out($this->getLink($murl, $label, $attrs, $alternateTagName, $alternateAttrs));
	}
	
	/**
	 * @param string $murl
	 * @param mixed $label
	 * @param array $attrs
	 * @throws UnavailableMurlException
	 * @return \n2n\web\ui\view\impl\html\Link
	 */
	public function getLink($murl, $label = null, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		if ($label === null) {
			$suggestedLabel = null;
			$murl = $this->view->buildUrlStr($murl, $required, $suggestedLabel);
			if ($suggestedLabel !== null) {
				$label = $suggestedLabel;
			} else {
				$url = Url::create($murl);
				if (null !== ($hostName = $url->getAuthority()->getHost())) {
					$label = $hostName;
				} else {
					$label = str_replace(array('http://', 'https://'), '', $murl);
				}	
			}
		}
		
		$raw = new Raw();
		$raw->append($this->getLinkStart($murl, $attrs, $alternateTagName, $alternateAttrs, $required));
		$raw->append(HtmlUtils::contentsToHtml($label));
		$raw->append($this->getLinkEnd());
		
		return $raw;
	}
	
	private $linkStartedTagNameHtml = null;
	
	public function linkStart($murl, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		$this->view->out($this->getLinkStart($murl, $attrs, $alternateTagName, $alternateAttrs, $required));
	}
	
	public function getLinkStart($murl, array $attrs = null, string $alternateTagName = null, 
			array $alternateAttrs = null, bool $required = false) {
		if ($this->linkStartedTagNameHtml !== null) {
			throw new UiException('Link already started.');
		}
		
		$href = null;
		if ($murl !== null) {
			$href = $this->view->buildUrlStr($murl, $required);
		}
		$attrs = (array) $attrs;
		
		if ($href !== null) {
			$this->linkStartedTagNameHtml = 'a';
			$attrs = HtmlUtils::mergeAttrs(array('href' =>  $href), $attrs);
			return new Raw('<a ' . HtmlElement::buildAttrsHtml($attrs) . '>');
		} 
		
		if ($alternateTagName !== null) {
			$this->linkStartedTagNameHtml = $this->getEsc($alternateTagName);
		} else {
			$this->linkStartedTagNameHtml = 'a';
		}
		
		return new Raw('<' . $this->linkStartedTagNameHtml . ' ' 
				. HtmlElement::buildAttrsHtml($alternateAttrs ?? $attrs) . '>');
	}
	
	
	public function linkEnd() {
		$this->view->out($this->getLinkEnd());
	}
	
	public function getLinkEnd() {
		if ($this->linkStartedTagNameHtml === null) {
			throw new UiException('No Link started.');
		}
		
		$raw = new Raw('</' . $this->linkStartedTagNameHtml . '>');
		$this->linkStartedTagNameHtml = null;
		return $raw;
	}

	/**
	 * @param mixed $pathExt
	 * @param unknown $label
	 * @param array $attrs
	 * @param array $query
	 * @param string $fragment
	 * @param string $ssl
	 * @param string $subsystem
	 */
	public function linkToContext($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToContext($pathExt, $label, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	/**
	 * @param mixed $pathExt
	 * @param mixed $label
	 * @param array $attrs
	 * @param array $query
	 * @param string $fragment
	 * @param bool $ssl
	 * @param string $subsystem
	 * @return \n2n\web\ui\view\impl\html\Link
	 */
	public function getLinkToContext($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getContextUrl($pathExt, $query, $fragment, $ssl, $subsystem), 
				$label, $attrs);
	}

	public function linkToController($pathExt, $label, array $attrs = null, $query = null, 
			$fragment = null, $contextKey = null, $ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToController($pathExt, $label, $attrs, $query, $fragment, 
				$contextKey, $ssl, $subsystem));
	}
	
	public function getLinkToController($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$contextKey = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getControllerUrl($pathExt, $query, $fragment, $contextKey, $ssl, $subsystem), 
				$label, $attrs);
	}

	public function linkToPath($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToPath($pathExt, $label, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	public function getLinkToPath($pathExt = null, $label = null, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getPath($pathExt, $query, $fragment, $ssl, $subsystem), $label, $attrs);
	}

	public function linkToUrl($pathExt, $label, array $attrs = null, $query = null, $fragment = null, 
			$ssl = null, $subsystem = null) {
		$this->view->out($this->getLinkToUrl($pathExt, $attrs, $query, $fragment, $ssl, $subsystem));
	}

	public function getLinkToUrl($pathExt = null, $label = null, array $attrs = null, $query = null, 
			$fragment = null, $ssl = null, $subsystem = null) {
		return new Link($this->meta->getUrl($pathExt, $query, $fragment, $ssl, $subsystem), $label, $attrs);
	}
	
	/**
	 * 
	 * @param unknown $email
	 * @param unknown $label
	 * @param array $attrs
	 */
	public function linkEmail($email, $label = null, array $attrs = null) {
		$this->view->out($this->getLinkEmail($email, $label, $attrs));
	}
	
	/** 
	 * @param unknown $email
	 * @param unknown $label
	 * @param array $attrs
	 * @return \n2n\web\ui\Raw
	 */
	public function getLinkEmail($email, $label = null, array $attrs = null) {
		$uriHtml = HtmlUtils::encodedEmailUrl($email);
		HtmlUtils::validateCustomAttrs((array) $attrs, array('href'));
		return new Raw('<a href="' . $uriHtml . '"' . HtmlElement::buildAttrsHtml($attrs) . '>'
				. ($label !== null ? HtmlUtils::contentsToHtml($label) : HtmlUtils::encode($email)) . '</a>');
	}
	
	/*
	 * MESSAGE CONTAINER UTILS 
	 */
	
	/**
	 * @param string $groupName
	 * @param int $severity
	 * @param array $attrs
	 * @param array $errorAttrs
	 * @param array $warnAttrs
	 * @param array $infoAttrs
	 * @param array $successAttrs
	 */
	public function messageList($groupName = null, $severity = null, array $attrs = null, array $errorAttrs = null, 
			array $warnAttrs = null, array $infoAttrs = null, array $successAttrs = null) {
		$this->view->out($this->getMessageList($groupName, $severity, $attrs, $errorAttrs, 
				$warnAttrs, $infoAttrs, $successAttrs));
	}
	
	/**
	 * @param string $groupName
	 * @param int $severity
	 * @param array $attrs
	 * @param array $errorAttrs
	 * @param array $warnAttrs
	 * @param array $infoAttrs
	 * @param array $successAttrs
	 * @return \n2n\web\ui\view\impl\html\MessageList
	 */
	public function getMessageList($groupName = null, $severity = null, array $attrs = null, array $errorAttrs = null, 
			array $warnAttrs = null, array $infoAttrs = null, array $successAttrs = null) {
		
		return new MessageList($this->meta->getMessages($groupName, $severity), $attrs, 
				$errorAttrs, $warnAttrs, $infoAttrs, $successAttrs);
	}
	
	/*
	 * L10N UTILS 
	 */
	
	const REPLACEMENT_PREFIX = '[';
	const REPLACEMENT_SUFFIX = ']';
	
	/**
	 * @param unknown $code
	 * @param array $args
	 * @param string $num
	 * @param array $replacements
	 * @param string $module
	 */
	public function text($code, array $args = null, $num = null, array $replacements = null, 
			$module = null) {
		$this->l10nText($code, $args, $num, $replacements, $module);
	}
	
	public function getText($code, array $args = null, $num = null, array $replacements = null, 
			$module = null) {
		return $this->getL10nText($code, $args, $num, $replacements, $module);
	}
		
	public function getL10nText($key, array $args = null, $num = null, array $replacements = null, $module = null) {
		$textRaw = $this->getEsc($this->view->getL10nText($key, $args, $num, $module));
		if (empty($replacements)) return $textRaw;
		
		$textHtml = (string) $textRaw;
		foreach ($replacements as $key => $replacement) {
			$textHtml = str_replace(self::REPLACEMENT_PREFIX . $key . self::REPLACEMENT_SUFFIX, 
					HtmlUtils::contentsToHtml($replacement), $textHtml);
		}
		return new Raw($textHtml);
	}
	
	public function l10nText($key, array $args = null, $num = null, array $replacements = null, $module = null) {
		$this->view->out($this->getL10nText($key, $args, $num, $replacements, $module));
	}
	
	public function getL10nNumber($value, $style = \NumberFormatter::DECIMAL, $pattern = null) {
		return $this->getEsc($this->view->getL10nNumber($value, $style, $pattern));
	}
	
	public function l10nNumber($value, $style = \NumberFormatter::DECIMAL, $pattern = null) {
		$this->view->out($this->getL10nNumber($value, $style, $pattern));
	}
	
	public function getL10nCurrency($value, $currency = null) {
		return $this->getEsc($this->view->getL10nCurrency($value, $currency));
	}
	
	public function l10nCurrency($value, $currency = null) {
		$this->view->out($this->getL10nCurrency($value, $currency));
	}
	
	public function getL10nDate($value, $dateStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDate($value, $dateStyle, $timeZone));
	}
	
	public function l10nDate($value, $dateStyle = null, \DateTimeZone $timeZone = null) {
		return $this->view->out($this->getL10nDate($value, $dateStyle, $timeZone));
	}
	
	public function getL10nTime($value, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nTime($value, $timeStyle, $timeZone));
	}
	
	public function l10nTime($value, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->view->out($this->getL10nTime($value, $timeStyle, $timeZone));
	}
	
	public function getL10nDateTime($value, $dateStyle = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDateTime($value, $dateStyle, $timeStyle, $timeZone));
	}
	
	public function l10nDateTime(\DateTime $value = null, $dateStyle = null, $timeStyle = null, \DateTimeZone $timeZone = null) {
		$this->view->out($this->getL10nDateTime($value, $dateStyle, $timeStyle, $timeZone));
	}
	
	public function getL10nDateTimeFormat(\DateTime $dateTime = null, $icuPattern, \DateTimeZone $timeZone = null) {
		return $this->getEsc($this->view->getL10nDateTimeFormat($dateTime, $icuPattern, $timeZone));
	}
	
	public function l10nDateTimeFormat(\DateTime $dateTime = null, $icuPattern, \DateTimeZone $timeZone = null) {
		$this->view->out($this->getL10nDateTimeFormat($dateTime, $icuPattern, $timeZone));
	}
	
	/*
	 * IMAGE UTILS
	 */
	
	public function image(File $file = null, ThumbStrategy $thumbStrategy = null, array $attrs = null, 
			bool $attrWidth = true, bool $attrHeight = true) {
		$this->view->out($this->getImage($file, $thumbStrategy, $attrs, $attrWidth, $attrHeight));
	}
	
	public function getImage(File $file = null, ThumbStrategy $thumbStrategy = null, array $attrs = null, 
			bool $addWidthAttr = true, bool $addHeightAttr = true) {
		if ($file === null) return null;
		
		return UiComponentFactory::createImg($file, $thumbStrategy, $attrs, $addWidthAttr, $addHeightAttr);
	}
	
	public function imageAsset($pathExt, $alt, array $attrs = null, string $moduleNamespace = null) {
		$this->view->out($this->getImageAsset($pathExt, $alt, $attrs, $moduleNamespace));
	}
	
	public function getImageAsset($pathExt, $alt, array $attrs = null, string $moduleNamespace = null) {
		if ($moduleNamespace === null) {
			$moduleNamespace = $this->view->getModuleNamespace();
		}
		return new HtmlElement('img', HtmlUtils::mergeAttrs(
				array('src' => $this->view->getHttpContext()->getAssetsUrl($moduleNamespace)->ext($pathExt), 
						'alt' => $alt), (array) $attrs));
	}
}
