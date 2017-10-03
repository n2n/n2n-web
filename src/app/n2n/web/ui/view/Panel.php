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
namespace n2n\web\ui\view;

use n2n\web\ui\UiComponent;
use n2n\web\ui\BuildContext;

class Panel {
	private $name;
	private $contents;
		
	public function __construct($name) {
		$this->name = $name;
		$this->contents = array();
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function append($contents) {
		if ($contents instanceof UiComponent) {
			$this->contents[] = $contents;
		} else {
			$this->contents[] = (string) $contents;
		}
	}
	
	public function buildContents(BuildContext $buildContext) {
		$allContentsStr = '';
		foreach ($this->contents as $contents) {
			if ($contents instanceof UiComponent) {
				$allContentsStr .= $contents->build($buildContext);
			} else {
				$allContentsStr .= $contents;
			}
		}
		
		return $allContentsStr;
	}
}
