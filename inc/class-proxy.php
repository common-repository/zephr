<?php
/**
 * Proxy class file.
 *
 * @package Zephr
 */

namespace Zephr;

/**
 * Reverse Proxy
 */
class Proxy {
	use Instance;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'wp_loaded', [ $this, 'handle_request' ] );
	}

	/**
	 * Handle a proxy request.
	 */
	public function handle_request() {
		if ( ! $this->should_proxy() ) {
			return;
		}

		$this->check_trailing_slash();

		$domain = Admin_Settings::instance()->get_domain();

		$response = wp_remote_request(
			sprintf(
				'https://%s%s',
				trim( untrailingslashit( $domain ) ),
				trim( $this->get_request_path() ),
			),
			[
				'body'       => file_get_contents( 'php://input' ), // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile
				'method'     => sanitize_text_field( $_SERVER['REQUEST_METHOD'] ?? 'GET' ),
				'headers'    => $this->get_request_headers(),
				'timeout'    => 10, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'user-agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
			],
		);

		// Handle an internal error.
		if ( is_wp_error( $response ) ) {
			wp_die(
				sprintf(
					/* translators: 1: error message */
					esc_html__( 'There was an error proxying the request: %s', 'zephr' ),
					esc_html( $response->get_error_message() ),
				)
			);
		}

		// Ensure that no headers were sent already.
		if ( headers_sent() ) {
			wp_die(
				esc_html__( 'Headers already sent, cannot proxy request.', 'zephr' )
			);
		}

		$headers = wp_remote_retrieve_headers( $response );

		// Output all the headers from the response.
		$this->output_headers(
			$headers instanceof \Requests_Utility_CaseInsensitiveDictionary
				? $headers->getAll()
				: $headers
		);

		// Output the body of the response.
		echo wp_remote_retrieve_body( $response ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Output the headers from a response.
	 *
	 * @param array $headers Headers from the response.
	 * @return void
	 */
	protected function output_headers( array $headers ): void {
		foreach ( $headers as $header => $value ) {
			$header = ucwords( $header, '-' );

			// Handle an array of values.
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					$this->output_headers( [ $header => $v ] );
				}
			} else {
				// Prevent specific headers from being output.
				if ( in_array( strtolower( $header ), [ 'content-encoding', 'content-length', 'content-type', 'vary', 'via' ], true ) ) {
					continue;
				}

				header(
					sprintf( '%s: %s', $header, $value ),
					'set-cookie' === $header ? false : true,
				);
			}
		}
	}

	/**
	 * Check if the request should be proxied to the Zephr CDN.
	 *
	 * @return bool
	 */
	protected function should_proxy() {
		$url = $this->get_request_path();

		if (
			0 === strpos( $url, '/blaize/' )
			|| '/blaize' === $url
			|| 0 === strpos( $url, '/zephr/' )
			|| '/zephr' === $url
			|| in_array( untrailingslashit( $url ), Page_Proxy::get_proxied_pages(), true )
			|| in_array( trailingslashit( $url ), Page_Proxy::get_proxied_pages(), true )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Make sure our URI matches Zephr page exactly.
	 *
	 * @return bool
	 */
	protected function check_trailing_slash() {
		$url = $this->get_request_path();

		// If it already matches, we don't need to do anything.
		if ( in_array( $url, Page_Proxy::get_proxied_pages(), true ) ) {
			return;
		}

		$has_trailing_slash = '/' === substr( $url, -1 );

		// Remove the slash.
		if (
			$has_trailing_slash
			&& in_array( untrailingslashit( $url ), Page_Proxy::get_proxied_pages(), true )
		) {
			wp_safe_redirect( untrailingslashit( $url ), 301, __( 'Zephr Plugin', 'zephr' ) );
			exit();
		}

		// Add the slash.
		if (
			! $has_trailing_slash
			&& in_array( trailingslashit( $url ), Page_Proxy::get_proxied_pages(), true )
		) {
			wp_safe_redirect( trailingslashit( $url ), 301, __( 'Zephr Plugin', 'zephr' ) );
			exit();
		}
	}

	/**
	 * Get the request URL without the blog path.
	 *
	 * @return string
	 */
	protected function get_request_path(): string {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$url = sanitize_text_field( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		if ( is_multisite() ) {
			$url = $this->trim_multisite_path( $url );
		}

		return $url;
	}

	/**
	 * Get the request headers for the current request.
	 *
	 * @return array
	 */
	protected function get_request_headers(): array {
		return array_filter(
			getallheaders(),
			fn ( $key ) => ! in_array( strtolower( $key ), [ 'host', 'user-agent', 'connection', 'content-length' ], true ),
			ARRAY_FILTER_USE_KEY,
		);
	}

	/**
	 * Trim the multisite path from a request URL.
	 *
	 * @param string $url URL to modify.
	 * @return string
	 */
	protected function trim_multisite_path( string $url ): string {
		$path = trailingslashit( get_blog_details()->path ?? '' );
		return '/' . ltrim( array_reverse( explode( $path, $url, 2 ) )[0] ?? '', '/' );
	}
}
