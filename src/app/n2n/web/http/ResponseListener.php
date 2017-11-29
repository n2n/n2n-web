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

use n2n\web\http\payload\Payload;

/**
 * Implemenations of this listener can be registered {@see Response::registerListener()} to get notified about
 * status changes.
 */
interface ResponseListener {
	
	/**
	 * Gets invoked when {@see Response::send()} is called.
	 * @param Payload $payload
	 * @param Response $response
	 */
	public function onSend(Payload $payload, Response $response);
	
	/**
	 * Gets invoked when a new Status is set over {@see Response::setStatus()}.
	 * @param int $newStatus
	 * @param Response $response
	 */
	public function onStatusChange(int $newStatus, Response $response);
	
	/**
	 * Gets invoked when {@see Response::reset()} is called.
	 */
	public function onReset(Response $response);
	
	/**
	 * Gets invoked when {@see Response::flush()} is called.
	 */
	public function onFlush(Response $response);
}

