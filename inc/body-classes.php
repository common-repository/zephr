<?php
/**
 * Add body classes.
 *
 * @package Zephr
 */

namespace Zephr;

/**
 * Add admin page classes.
 *
 * @param [string] $classes Admin body tag css classes.
 * @return string
 */
function zephr_page_classes( $classes ) {
	global $pagenow;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( 'admin.php' === $pagenow ) && ( isset( $_GET['page'] ) && 'zephr' === $_GET['page'] ) ) {
		if ( ! Admin_Settings::instance()->check_onboarding_complete() ) {
			$classes .= ' zephr-fullscreen-onboarding ';
		}
	}

	return $classes;
}

add_filter( 'admin_body_class', __NAMESPACE__ . '\zephr_page_classes' );
