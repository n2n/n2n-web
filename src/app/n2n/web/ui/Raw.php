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
namespace n2n\web\ui;

class Raw implements UiComponent {
	private $contents;
	
	/**
	 * 
	 * @param string $raw
	 */
	public function __construct(string $raw = null) {
		$this->contents = (string) $raw;
	}
	
	public function append(string $raw = null) {
		$this->contents .= $raw;
	}
	
	public function appendLn(string $raw = null) {
		$this->append($raw . "\n");
	}
	/**
	 * (non-PHPdoc)
	 * @see n2n\web\ui.UiComponent::build()
	 */
	public function build(BuildContext $buildContext): string {
		return $this->contents;
	}
	
	public function getContents() {
		return $this->__toString();
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->contents;
	}
}
