<?php
namespace n2n\web\http;

/**
 * Implemenations of this listener can be registered {@see Response::registerListener()} to get notified about
 * status changes.
 */
interface ResponseListener {
	
	/**
	 * Gets invoked when {@see Response::send()} is called.
	 * @param ResponseObject $responseObject
	 * @param Response $response
	 */
	public function onSend(ResponseObject $responseObject, Response $response);
	
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

