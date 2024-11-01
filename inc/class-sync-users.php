<?php
/**
 * Class File for Syncing WordPress Users to Zephr.
 *
 * @package Zephr
 */

namespace Zephr; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

/**
 * Adds custom REST Endpoints.
 */
class Sync_Users {
	use Instance;

	/**
	 * Set everything up.
	 */
	public function __construct() {
		add_action( 'do_zephr_user_migrate', [ $this, 'zephr_user_migrate' ] );
		add_action( 'admin_notices', [ $this, 'sitewide_admin_notices' ] );
	}

	/**
	 * Sets up an immediate cron job to migrate WordPress users to Zephr.
	 */
	public function schedule_user_migrate() {
		$scheduled = wp_schedule_single_event( time(), 'do_zephr_user_migrate', [] );
		return [ 'scheduled' => $scheduled ];
	}

	/**
	 * Migrate WordPress users to Zephr.
	 */
	public function zephr_user_migrate() {
		$users = get_users();
		foreach ( $users as $user ) {
			$this->create_user( $user->data->user_email, $user->data->user_pass );
		}
		update_option( 'zephr_user_migration_success', true );
	}

	/**
	 * Removes the admin notice.
	 */
	public function clear_zephr_user_admin_notice() {
		delete_option( 'zephr_user_migration_success' );
	}

	/**
	 * Apply Admin Notices site wide.
	 *
	 * @return void
	 */
	public function sitewide_admin_notices() {
		if ( $this->show_user_migration_success() ) {
			delete_option( 'zephr_user_migration_success' );

			printf(
				'<div id="zephr-user-migrate" class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'WordPress users have been migrated to Zephr.', 'zephr' ),
			);
		}
	}

	/**
	 * Checks if we should show the user migration success alert.
	 */
	public function show_user_migration_success() {
		return get_option( 'zephr_user_migration_success', false );
	}

	/**
	 * Creates a new zephr user.
	 *
	 * @param string $email The email address.
	 * @param string $password The legacy password.
	 *
	 * @todo Get proper legacy_password support.
	 */
	public function create_user( string $email, string $password ) {
		$salt = substr( $password, 3, 9 );
		$hash = substr( $password, 12, 22 );
		$body = [
			'identifiers' => [
				'email_address' => $email,
			],
			'validators'  => [
				'legacy_password' => wp_json_encode(
					[
						'algorithm' => 'bcrypt',
						'hash'      => $hash,
						'salt'      => $salt,
						'rounds'    => 256,
					]
				),
			],
		];

		$response = Client::post( '/v3/users', $body );
		return $response;
	}
}
