<?php
/**
 * Contains functions for working with cron.
 *
 * @package Zephr
 */

namespace Zephr;

add_filter( 'cron_schedules', __NAMESPACE__ . '\add_custom_cron_schedule' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected

/**
 * Adds a custom cron schedule for every 10 minutes.
 *
 * @param array $schedules An array of non-default cron schedules.
 * @return array Filtered array of non-default cron schedules.
 */
function add_custom_cron_schedule( $schedules ) {
	$schedules['every-ten-minutes'] = [
		'interval' => 10 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 10 minutes', 'zephr' ),
	];
	return $schedules;
}
