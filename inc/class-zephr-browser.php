<?php
/**
 * Zephr_Browser class file.
 *
 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
 *
 * @package Zephr
 */

namespace Zephr;

/**
 * Zephr Browser
 *
 * Include Zephr Browser Library on the site.
 *
 * @link https://www.npmjs.com/package/@zephr/browser
 */
class Zephr_Browser {
	use Instance;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'on_enqueue_scripts' ] );
	}

	/**
	 * Display Zephr JS.
	 */
	public function on_enqueue_scripts() {
		if ( ! empty( Admin_Settings::instance()->get_option( 'zephr_disable_browser' ) ) ) {
			return;
		}

		// Fetch the latest version from NPM.
		$version = $this->get_latest_version();

		wp_enqueue_script(
			'zephr-browser',
			"https://assets.zephr.com/zephr-browser/{$version}/zephr-browser.umd.js",
			[],
			$version,
			false
		);

		wp_add_inline_script(
			'zephr-browser',
			sprintf(
				'zephrBrowser.run(%s, %s, %s);',
				/**
				 * Home URL to use with Zephr
				 *
				 * @param string $url Base URL for the site.
				 */
				wp_json_encode( apply_filters( 'zephr_js_url', home_url() ) ),
				/**
				 * Zephr Browser JWT Token for Authentication
				 *
				 * @param string $jwt JWT for authentication.
				 */
				wp_json_encode( apply_filters( 'zephr_browser_jwt', '' ) ),
				/**
				 * Zephr Browser Custom Data
				 *
				 * @param array $data Custom data to feed to Zephr.
				 */
				wp_json_encode( apply_filters( 'zephr_browser_custom_data', [] ) ),
			),
			'after'
		);
	}

	/**
	 * Get the latest Zephr Browser version from NPM.
	 *
	 * @return string
	 */
	public function get_latest_version() {
		$version = get_transient( 'zephr_browser_version' );

		if ( false !== $version ) {
			return $version;
		}

		$body = wp_remote_get( 'https://api.npms.io/v2/package/%40zephr%2Fbrowser' ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$body = json_decode( wp_remote_retrieve_body( $body ), true );

		$version = $body['collected']['metadata']['version'] ?? '1.3.10';

		set_transient( 'zephr_browser_version', $version, DAY_IN_SECONDS );

		return $version;
	}
}
