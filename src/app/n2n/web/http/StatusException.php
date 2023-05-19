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
namespace n2n\web\http;

class StatusException extends \Exception {
// 	private $attributes;
	
	public function __construct(private int $status, string $message = null, int $code = null, \Exception $previous = null) {
		parent::__construct((string) $message, (int) $code, $previous);
// 		$this->attributes = new Attributes();
	}
	/**
	 * 
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}
	/**
	 * 
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = (int) $status;
	}
	
	public function prepareResponse(Response $response) {
		$response->setStatus($this->status);
	}
// 	/**
// 	 * 
// 	 * @return \n2n\util\type\attrs\Attributes
// 	 */
// 	public function getAttributes() {
// 		return $this->attributes;
// 	}
}
