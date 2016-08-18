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

use n2n\util\config\Attributes;
use n2n\web\ui\UiComponent;
use n2n\io\ob\OutputBuffer;
use n2n\web\ui\ViewStuffFailedException;
use n2n\web\dispatch\ui\Form;

class HtmlProperties {	
	protected $prependedAttributes;
	protected $attributes;
	protected $contentHtmlProperties;
	private $form;
	private $libraryHashCodes = array();
	private $ids = array();
	
	public function __construct() {
		$this->prependedAttributes = new Attributes();
		$this->attributes = new Attributes();
	}
	
	public function setContentHtmlProperties(HtmlProperties $contentHtmlProperties = null) {
		$this->contentHtmlProperties = $contentHtmlProperties;
	}
	
	public function getContentHtmlProperties() {
		return $this->contentHtmlProperties;
	}
	
	public function set($name, UiComponent $value, $prepend = false) {
		if ($prepend) {
			if ($this->prependedAttributes->contains($name)) return;
			$this->prependedAttributes->set($name, $value);
			$this->attributes->remove($name);
		} else if (!$this->prependedAttributes->contains($name) 
				&& !$this->attributes->contains($name)) {
			$this->attributes->set($name, $value);
		}
		
		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->remove($name);
		}
	}
	
	public function push($name, UiComponent $value, $prepend = false) {
		if ($prepend) {
			$this->prependedAttributes->push($name, $value);
		} else {
			$this->attributes->push($name, $value);
		}
		
// 		if ($this->contentHtmlProperties !== null) {
// 			$this->contentHtmlProperties->remove($name);
// 		}
	}
	
	public function add($name, $key, UiComponent $value, $prepend = false) {
		if ($prepend) {
			if ($this->prependedAttributes->hasKey($name, $key)) return;
			$this->prependedAttributes->add($name, $key, $value);
			$this->attributes->removeKey($name, $key);
		} else if (!$this->prependedAttributes->hasKey($name, $key)  
				&& !$this->attributes->hasKey($name, $key)) {
			$this->attributes->add($name, $key, $value);
		}

		if ($this->contentHtmlProperties !== null) {
			$this->contentHtmlProperties->removeKey($name, $key);
		}
	}
	
	public function remove($name) {
		$this->prependedAttributes->remove($name);
		$this->attributes->remove($name);
	}
	
	public function removeKey($name, $key) {
		$this->prependedAttributes->removeKey($name, $key);
		$this->attributes->removeKey($name, $key);
	}
	
	public function containsName($name) {
		return ($this->prependedAttributes->contains($name) || $this->attributes->contains($name))
				|| ($this->contentHtmlProperties !== null && $this->contentHtmlProperties->containsName($name));
	}
	
	public function getAttributesCollection() {
		$collection = array($this->prependedAttributes, $this->attributes);
		if ($this->contentHtmlProperties !== null) {
			$collection = array_merge($collection, $this->contentHtmlProperties->getAttributesCollection());
		}
		return $collection;
	}
	
	public function fetchUiComponentHtmlSnipplets(array $keys) {
		$contents = array_fill_keys($keys, array());
		
		foreach ($this->getAttributesCollection() as $attributes) {
			foreach ($attributes->toArray() as $name => $value) {
				if (!array_key_exists($name, $contents)) continue;
		
				if (is_array($value)) {
					foreach ($value as $key => $uiComponent) {
						$contents[$name][$key] = $uiComponent->getContents();
					}
				} else if ($value instanceof UiComponent) {
					$contents[$name][] = $value->getContents();
				}
		
				$attributes->remove($name);
			}
		}
		
		return $contents;
	}
	
	public function fetchHtmlSnipplets(array $keys): array {
		$htmlSnipplets = array_fill_keys($keys, null);
		
		foreach ($this->getAttributesCollection() as $attributes) {
			foreach ($attributes->toArray() as $name => $value) {
				if (!array_key_exists($name, $htmlSnipplets)) continue;
		
				if (is_array($value)) {
					foreach ($value as $uiComponent) {
						$htmlSnipplets[$name] .= $uiComponent->getContents() . "\r\n";
					}
				} else if ($value instanceof UiComponent) {
					$htmlSnipplets[$name] = $value->getContents() . "\r\n";
				}
		
				$attributes->remove($name);
			}
		}
		
		return $htmlSnipplets;
	}
	
	public function out(OutputBuffer $contentBuffer) {
		$htmlSnipplets = $this->fetchHtmlSnipplets($contentBuffer->getBreakPointNames());
				
		foreach ($htmlSnipplets as $name => $htmlSnipplet) {
			$contentBuffer->insertOnBreakPoint($name, $htmlSnipplets[$name]);
		}
	}
	
	private function getFirstHtmlSnipplet() {
		foreach ($this->getAttributesCollection() as $attributes) {
			foreach ($attributes->toArray() as $value) {
				if (is_array($value)) {
					foreach ($value as $uiComponent) {
						return $uiComponent->getContents();
					}
				} else if ($value instanceof UiComponent) {
					return $value->getContents();
				}
			}
		}
		
		return null;
	}
	
	public function isEmpty() {
		return $this->attributes->isEmpty() && $this->prependedAttributes->isEmpty();
	}
	
	public function validateForResponse() {
		if ($this->isEmpty()) return;
			
		throw new ViewStuffFailedException('Unassigned html property: ' 
				. $this->getFirstHtmlSnipplet());
	}
	
	public function registerLibrary(Library $library) {
		$hashCode = $library->hashCode();
		if (in_array($hashCode, $this->libraryHashCodes)) {
			return false;
		}
		
		$this->libraryHashCodes[] = $hashCode;
		return true;
	}
	
	public function registerId($id) {
		if (in_array($id, $this->ids)) {
			return false;
		}
		
		$this->ids[] = $id;
		return true;
	}
	
	/**
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}
	
	public function setForm(Form $form = null) {
		$this->form = $form;
	}
	
	public function merge(HtmlProperties $htmlProperties) {
		$this->prependedAttributes->append($htmlProperties->prependedAttributes);
		$this->attributes->append($htmlProperties->attributes);
	}
}
