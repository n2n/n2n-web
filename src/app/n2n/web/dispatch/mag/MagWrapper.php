<?php

namespace n2n\web\dispatch\mag;

use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;

class MagWrapper {
	private $mag;
	private $markAttrs = array();
	
	public function __construct(Mag $mag) {
		$this->mag = $mag;
	}
	
	public function getMag() {
		return $this->mag;
	}
	
	public function addMarkAttrs(array $markAttrs) {
		$this->markAttrs = HtmlUtils::mergeAttrs($this->markAttrs, $markAttrs, true);
	}
	
	public function getMarkAttrs() {
		return $this->markAttrs;
	}
	
	public function setMarkAttrs(array $markAttrs) {
		$this->markAttrs = $markAttrs;
	}
	
	public function getContainerAttrs(HtmlView $view) {
		return HtmlUtils::mergeAttrs($this->markAttrs, $this->mag->getContainerAttrs($view), true);
	}
}