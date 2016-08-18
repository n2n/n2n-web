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

use n2n\core\SysTextUtils;
use n2n\web\ui\UiException;
use n2n\util\HashUtils;
use n2n\reflection\ReflectionUtils;
use n2n\web\ui\view\View;

class HtmlUtils {
	public static function validateCustomAttrs(array $customAttrs, array $reservedAttrNames) {
		foreach ($customAttrs as $name => $value) {
			if (in_array($name, $reservedAttrNames)) {
				throw new AttributeNameIsReservedException(
						SysTextUtils::get('n2n_error_view_html_attribute_is_reserved',
								array('reservedAttributes' => implode(', ', $reservedAttrNames), 'attribute' => $name)));
			}
		}
	}
	
	public static function mergeAttrs(array $attrs, array $customAttrs) {
		foreach ($customAttrs as $name => $value) {
			if (is_numeric($name)) {
				if (in_array($value, $attrs)) continue;
			} else if (array_key_exists($name, $attrs)) {
				if ($name == 'class') {
					$attrs['class'] .= ' ' . $value;
					continue;
				}
				
				throw new AttributeNameIsReservedException('Html attribute \'' . $name . '\' is reserved.' 
						. ' Reserved attributes: ' . implode(', ', array_keys($attrs)));
			}
			
			$attrs[$name] = $value;
		}
		
		return $attrs;
	}
	
	public static function contentsToHtml($contents) {
		if ($contents instanceof UiComponent) {
			return $contents->getContents();
		}
		
		if (is_object($contents) && !method_exists($contents, '__toString')) {
			$contents = ReflectionUtils::getTypeInfo($contents);
		}
		
		return htmlspecialchars((string) $contents);
	}
	
	public static function escape($contents, \Closure $pcf = null) {
		$html = null;
		
		if ($contents === null) {
			$html = '';
		} else if (is_scalar($contents)) {
			$html = htmlspecialchars((string) $contents);
		} else if ($contents instanceof UiComponent) {
			return htmlspecialchars($contents->getContents());
		} else if (is_object($contents) && method_exists($contents, '__toString')) {
			$html = htmlspecialchars((string) $contents);
		} else {
			throw new \InvalidArgumentException('Could not convert type to escaped string: ' 
					. ReflectionUtils::getTypeInfo($contents));
		}
		
		if (isset($pcf)) {
			$html = $pcf($html);
		}
		
		return $html;
	}
		
	public static function buildUniqueId($prefix = null) {
		return $prefix . HashUtils::base36Uniqid();
	}

	public static function encode($str) {
		$strHtml = (string) $str;
		
		for($i = "a"; $i <= "z"; $i++) {
			$strHtml = str_replace($i, "&#" . ord($i) . ";", $strHtml);
		}
		
		for($i = "A"; $i <= "Z"; $i++) {
			$strHtml = str_replace($i, "&#" . ord($i) . ";", $strHtml);
		}
		
		$strHtml = str_replace(".", "&#46;", $strHtml);
		$strHtml = str_replace("@", "&#64;", $strHtml);
		
		return $strHtml;
	}
	
	public static function encodedEmailUrl($email) {
		return HtmlUtils::encode('mailto:' . urlencode($email));
	}
}


class AttributeNameIsReservedException extends UiException {
	
}
