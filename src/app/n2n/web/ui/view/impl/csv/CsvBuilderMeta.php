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
namespace n2n\web\ui\view\impl\csv;

class CsvBuilderMeta {
	private $textDelimiter = '"';
	private $cellDelimiter = ',';
	private $lineDelimiter = PHP_EOL;
	/**
	 * @return string
	 */
	public function getTextDelimiter() {
		return $this->textDelimiter;
	}
	/**
	 * @param string $textDelimiter
	 */
	public function setTextDelimiter($textDelimiter) {
		$this->textDelimiter = $textDelimiter;
	}
	/**
	 * @return string
	 */
	public function getCellDelimiter() {
		return $this->cellDelimiter;
	}
	/**
	 * @param string $cellDelimiter
	 */
	public function setCellDelimiter($cellDelimiter) {
		$this->cellDelimiter = $cellDelimiter;
	}
	/**
	 * @return string
	 */
	public function getLineDelimiter() {
		return $this->lineDelimiter;
	}
	/**
	 * @param string $lineDelimiter
	 */
	public function setLineDelimiter($lineDelimiter) {
		$this->lineDelimiter = $lineDelimiter;
	}

	
	
}
