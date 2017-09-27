<?php
namespace n2n\web\http;

class HttpUtils {
	
	/**
	 * Masks native php function session_start() and prevents triggeration of warings if session cookie
	 * contains invalid session id or notice errors when session has already been started.
	 * @param array $options
	 * @throws HttpRuntimeException
	 */
	public static function sessionStart(array $options = null) {
		if (true === ($options === null ? @session_start() : @session_start($options))) return;
				
		$err = error_get_last();
		throw new HttpRuntimeException($err['message']);
	}
	
	/**
	 * Checks if passed session id is valid. Valid characters: a-z A-Z 0-9 , -
	 * @param string $id
	 * @return boolean true if valid, false otherwise
	 */
	public static function isSessionIdValid(string $id) {
		return (bool) preg_match('/^[0-9a-zA-Z,-]+$/', $id);
	}
	
	/**
	 * @param string $id
	 * @return string
	 * @throws \InvalidArgumentException if session is is invalid according to {@self::isSessionIdValid()}
	 */
	public static function sessionId(string $id = null) {
		if ($id === null) {
			return session_id();
		}
		
		if (!self::isSessionIdValid($id)) {
			throw new \InvalidArgumentException('Invalid session id: ' . $id);
		}
		
		session_id($id);
	}
}