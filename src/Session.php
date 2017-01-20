<?php

namespace dSStdlib;

class Session {

	/**
	 * String to prepend all session keys with
	 * @var string
	 */
	private static $prepend = '__DS_';
	private static $lifetime;
	private static $initialized = false;
	private static $writeClosed = false;

	private static function init() {
		if (self::getLifetime()) {
			ini_set('session.gc_maxlifetime', self::getLifetime());
			session_set_cookie_params(self::getLifetime());
		}
		session_start();
		self::$initialized = true;
	}

	private static function close() {
		if (!static::$writeClosed) session_write_close();
		static::$writeClosed = true;
	}

	/**
	 * Set the life time for the session
	 * @param int $lifetime
	 */
	public static function setLifetime($lifetime) {
		self::$lifetime = $lifetime;
	}

	/**
	 * Fetch the life time for the session
	 * @return int
	 */
	public static function getLifetime() {
		if (!self::$lifetime) {
			self::$lifetime = 60 * 60 * $sessionExpirationHours;
		}

		return self::$lifetime;
	}

	/**
	 * Saves to session
	 * @param string $key
	 * @param mixed $value
	 * @param int $duration Duration for which the identity should be valid
	 */
	public static function save($key, $value, $duration = null) {
		self::setLifetime($duration);
		static::init();
		$_SESSION[self::$prepend . $key] = $value;
		self::close();
	}

	/**
	 * Fetches from session
	 * @param string $key
	 * @return mixed
	 */
	public static function fetch($key, $remove = false) {
		if (!self::$initialized) self::init();
		if (isset($_SESSION[self::$prepend . $key])) $value = $_SESSION[self::$prepend . $key];
		if (self::$initialized) self::close();
		if ($remove) self::remove($key);
		return $value;
	}

	/**
	 * Removes from session
	 * @param string $key
	 */
	public static function remove($key) {
		if (!self::$initialized) self::init();
		if (isset($_SESSION[self::$prepend . $key])) unset($_SESSION[self::$prepend . $key]);
		if (self::$initialized) self::close();
	}

	/**
	 * Reset (destroy) the session
	 */
	public static function reset() {
		if (!self::$initialized) self::init();
		session_destroy();
		if (self::$initialized) self::close();
	}

}
