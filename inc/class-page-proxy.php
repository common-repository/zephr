<?php
/**
 * Page Proxy class file.
 *
 * @package Zephr
 */

namespace Zephr;

/**
 * Sets up the settings and output for OpenWeb Commenting.
 */
class Page_Proxy {
	use Instance;

	/**
	 * Settings key.
	 *
	 * @var string
	 */
	const SETTINGS_KEY = 'zephr_pages';

	/**
	 * Set everything up.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'schedule_cron_event' ] );
	}

	/**
	 * Schedules update_most_commented to run automatically.
	 */
	public function schedule_cron_event() {
		add_action( 'zephr_update_proxied_pages', [ $this, 'update_proxied_pages' ] );

		if ( ! wp_next_scheduled( 'zephr_update_proxied_pages' ) ) {
			wp_schedule_event( time(), 'every-ten-minutes', 'zephr_update_proxied_pages' );
		}
	}

	/**
	 * Pulls the pages from Zephr via GraphQL and stores them in a setting.
	 */
	public static function update_proxied_pages() {
		$vars  = [];
		$query = '{  listPages {   urls    {    loginUrl    registrationUrl    url}    }}';

		$response = Graphql_Client::post( $vars, $query );

		$urls = [];
		array_walk(
			$response['listPages'],
			function( $page ) use ( &$urls ) {
				$urls = array_merge( $urls, array_values( $page['urls'] ) );
			}
		);
		$urls = array_filter( $urls );

		update_option( self::SETTINGS_KEY, $urls );
	}

	/**
	 * Gets the stored proxied pages from settings.
	 *
	 * @return array
	 */
	public static function get_proxied_pages() {
		return get_option( self::SETTINGS_KEY, [] );
	}
}
