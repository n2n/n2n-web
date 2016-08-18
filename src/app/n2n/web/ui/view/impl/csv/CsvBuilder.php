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

use n2n\web\ui\view\impl\csv\CsvView;

class CsvBuilder {
	private $view;
	private $meta;
	
	public function __construct(CsvView $view) {
		$this->view = $view;
		$this->meta = new CsvBuilderMeta();
	}
	
	public function meta() {
		return $this->meta;
	}
	
	public function getEsc($text) {
		return str_replace($this->meta->getTextDelimiter(), str_repeat($this->meta->getTextDelimiter(), 2), $text);
	}
	/**
	 *
	 * @param string $text
	 */
	public function esc($text) {
		$this->view->out($this->getEsc($text));
	}
	/**
	 *
	 * @param string $content
	 * @return string
	 */
	public function getCell($content) {
		return $this->meta->getTextDelimiter() . $this->getEsc($content) . $this->meta->getTextDelimiter();
	}
	/**
	 *
	 * @param string $content
	 */
	public function cell($content) {
		$this->view->out($this->getCell($content));
	}
	/**
	 *
	 * @param array $cells
	 * @return string
	 */
	public function getEncodeLine(array $cells) {
		$content = array();
		foreach ($cells as $cell) {
			$content[] = $this->getCell($cell);
		}
		$content = implode($this->meta->getCellDelimiter(), $content);
		return $content;
	}
	/**
	 *
	 * @param array $cells
	 */
	public function encodeLine(array $cells) {
		$this->view->out($this->getEncodeLine($cells));
	}
	/**
	 *
	 * @param array $contents
	 * @param unknown_type $keysAsHeaders
	 * @return string
	 */
	public function getEncode(array $contents, $keysAsHeaders = true) {
		$lines = array();
		foreach ($contents as $row) {
			if ($keysAsHeaders) {
				$headers = array();
				foreach ($row as $name => $cell) {
					$headers[] = $name;
				}
				$lines[] = $this->getEncodeLine($headers);
			}
			$lines[] =  $this->getEncodeLine($row);
			$keysAsHeaders = false;
		}
		return implode($this->meta->getLineDelimiter(), $lines);
	}
	/**
	 *
	 * @param array $contents
	 * @param unknown_type $keysAsHeaders
	 */
	public function encode(array $contents, $keysAsHeaders = true) {
		$this->view->out($this->getEncode($contents, $keysAsHeaders));
	}
}
