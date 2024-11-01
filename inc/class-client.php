<?php
/**
 * Client class file.
 *
 * @package Zephr
 */

namespace Zephr;

use InvalidArgumentException;
use RuntimeException;

/**
 * Client to interact with Zephr's Admin API
 *
 * @method static mixed delete(string $endpoint, array $args = []): array|\WP_Error
 * @method static mixed get(string $endpoint, array $args = []): array|\WP_Error
 * @method static mixed post(string $endpoint, array $args = []): array|\WP_Error
 * @method static mixed put(string $endpoint, array $args = []): array|\WP_Error
 * @method static mixed patch(string $endpoint, array $args = []): array|\WP_Error
 */
class Client {

	/**
	 * Valid HTTP methods to request to the API.
	 *
	 * @var string[]
	 */
	public const HTTP_VERBS = [
		'DELETE',
		'GET',
		'POST',
		'PUT',
		'PATCH',
	];

	/**
	 * Make a request to the API.
	 *
	 * @param string $method Method name.
	 * @param array  $arguments Arguments for the method.
	 * @return array|\WP_Error|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid HTTP verb.
	 * @throws InvalidArgumentException Thrown on missing endpoint.
	 */
	public static function __callStatic( string $method, array $arguments ) {
		$method = strtoupper( $method );
		if ( ! in_array( $method, static::HTTP_VERBS, true ) ) {
			throw new InvalidArgumentException( 'Unknown HTTP method: ' . $method );
		}

		$path = array_shift( $arguments );
		if ( empty( $path ) ) {
			throw new InvalidArgumentException( 'No path passed to client request.' );
		}

		// Determine the body for the request.
		$body = '';
		if ( ! empty( $arguments ) ) {
			if ( false !== strpos( $path, 'graphql' ) ) {
				$body = array_shift( $arguments );
			} else {
				$body = wp_json_encode( array_shift( $arguments ) );
			}
		}

		$authorization = static::get_authorization( $path, $method, $body );
		if ( is_wp_error( $authorization ) ) {
			return $authorization;
		}

		$api_base = static::get_api_base();

		// Force all GraphQL requests to use the Console Endpoint.
		if ( false !== strpos( $path, Graphql_Client::GRAPHQL_PATH ) ) {
			$api_base = 'https://console.zephr.com';
		}

		$response = wp_remote_request(
			$api_base . $path,
			[
				'method'  => $method,
				'body'    => $body,
				'headers' => [
					'Authorization' => $authorization,
					'Content-Type'  => 'application/json',
				],
				'timeout' => 3,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Get the API base for the admin API.
	 *
	 * @return string
	 */
	protected static function get_api_base(): string {
		$settings = get_option( 'zephr' );
		$domain   = 'console.zephr.com';

		// Use the customer ID as the API base instead.
		if ( ! empty( $settings['zephr_tenant_id'] ) ) {
			$domain = "{$settings['zephr_tenant_id']}.api.zephr.com";
		}

		/**
		 * Zephr API Base.
		 *
		 * @param string $url API Base URL.
		 */
		return apply_filters( 'zephr_api_base', 'https://' . $domain );
	}

	/**
	 * Get the authorization header for requests.
	 *
	 * @param string $path   The request's path less the host.
	 * @param string $method The request's method.
	 * @param string $body   The request's body (optional for GET requests).
	 * @throws RuntimeException Thrown on missing credentials.
	 * @return string
	 */
	protected static function get_authorization( string $path, string $method, string $body = '' ): string {
		$settings = get_option( 'zephr' );

		if (
			! is_array( $settings )
			|| empty( $settings['zephr_admin_key'] )
			|| empty( $settings['zephr_admin_secret'] )
		) {
			return new RuntimeException( 'Admin API credentials not set.' );
		}

		// Zephr API key & secret.
		$access_key = $settings['zephr_admin_key'];
		$secret_key = $settings['zephr_admin_secret'];

		// Time since the UNIX Epoch in milliseconds.
		$timestamp = intval( round( microtime( true ) * 1000 ) );

		// Create unique nonce string for use in signature and built authorization string.
		$nonce = uniqid();

		// Create string used in HMAC hash.
		$signature = $secret_key . $body . $path . strtoupper( $method ) . $timestamp . $nonce;

		// Create the hash.
		$hash = hash( 'sha256', $signature );

		// Return the built authorization string.
		return "ZEPHR-HMAC-SHA256 $access_key:$timestamp:$nonce:$hash";
	}
}
