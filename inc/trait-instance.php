<?php
/**
 * Trait file for Instance.
 *
 * @package nr
 */

namespace Zephr;

/**
 * Make a class into a singleton.
 */
trait Instance {
	/**
	 * Existing instances.
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * Get class instance.
	 *
	 * @return static
	 */
	public static function instance() {
		$class = get_called_class();

		if ( ! isset( static::$instances[ $class ] ) ) {
			static::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}
}
