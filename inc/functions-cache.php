<?php
/**
 * Cache helper functions.
 *
 * @package Zephr
 */

namespace Zephr;

use Closure;

/**
 * Helper function to cache the return value of a function.
 *
 * @param string  $key Cache key.
 * @param Closure $callback Closure to invoke.
 * @param int     $ttl Cache TTL.
 * @return mixed
 */
function remember( string $key, Closure $callback, int $ttl = 3600 ) {
	$value = get_transient( $key );

	if ( $value ) {
		return $value;
	}
	$value = $callback();

	set_transient( $key, $value, $ttl );

	return $value;
}

/**
 * Helper function to delete a cached value.
 *
 * @param string $key Cache key.
 */
function forget( string $key ) {
	delete_transient( $key );
}
