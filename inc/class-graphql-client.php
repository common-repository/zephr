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
 * Client to interact with Zephr's GraphQL API.
 */
class Graphql_Client {
	/**
	 * Url of the graphql interface.
	 *
	 * @var string
	 */
	public const GRAPHQL_PATH = '/v4/admin/graphql';

	/**
	 * Make a request to the API.
	 *
	 * @param array  $variables GraphQL variables.
	 * @param string $query GraphQL query.
	 * @return array|\WP_Error|null
	 *
	 * @throws InvalidArgumentException Thrown on invalid HTTP verb.
	 * @throws InvalidArgumentException Thrown on missing endpoint.
	 */
	public static function post( $variables, $query ) {
		$body = sprintf(
			'[
				{
					"variables": %s,
					"query": "%s"
				}
			]',
			empty( $variables ) ? '{}' : wp_json_encode( $variables ),
			$query
		);

		$response = Client::post( static::get_graphql_path(), $body );
		if ( ! is_wp_error( $response ) && ! empty( $response[0]['data'] ) ) {
			return $response[0]['data'];
		}
		return false;
	}

	/**
	 * Get the GraphQL path.
	 *
	 * @return string
	 */
	protected static function get_graphql_path(): string {
		/**
		 * Zephr GraphQL Path.
		 *
		 * @return string
		 */
		return apply_filters( 'zephr_graphql_path', static::GRAPHQL_PATH );
	}
}
