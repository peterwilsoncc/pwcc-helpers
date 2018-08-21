<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */
namespace PWCC\Helpers\HmPlatformFixes;

/**
 * Fast Bootstrap Platform Fixes.
 *
 * Runs as the plugin is loaded.
 */
function fast_bootstrap() {
	// Runs late to ensure it runs after Cavalcade filters the option.
	add_filter( 'pre_option_cron', __NAMESPACE__ . '\\get_cron_array', 20 );

	/*
	 * Filter Jetpack Schedules to avoid schedule name collisions.
	 *
	 * This could be in Jetpack fixes but the cause is Cavalcade so it
	 * is going here.
	 */
	add_filter( 'jetpack_sync_incremental_sync_interval', __NAMESPACE__ . '\\jetpack_sync_incremental_sync_interval' );
	add_filter( 'jetpack_sync_full_sync_interval', __NAMESPACE__ . '\\jetpack_sync_full_sync_interval' );
}

/**
 * Replace Cavalcade's `__fake_schedule` with an actual schedule name.
 *
 * @see https://github.com/humanmade/Cavalcade/issues/29
 *
 * @param array $crons Cron array as retrieved via Cavalcade.
 * @return array Cron array with faked schedules
 */
function get_cron_array( $crons ) {
	if ( empty( $crons ) ) {
		// Nothing to fix.
		return $crons;
	}

	$schedules = [];
	foreach ( wp_get_schedules() as $name => $schedule ) {
		$schedules[ $name ] = $schedule['interval'];
	}

	foreach ( $crons as $timestamp => $cronhooks ) {
		if ( ! is_array( $cronhooks ) ) {
			continue;
		}
		foreach ( $cronhooks as $hook => $args ) {
			foreach ( $args as $key => $event ) {
				if ( ! isset( $event['schedule'] ) ) {
					continue;
				}
				if ( $event['schedule'] !== '__fake_schedule' ) {
					continue;
				}

				// @codingStandardsIgnoreLine
				$schedule = array_search( $event['interval'], $schedules );
				if ( ! $schedule ) {
					$schedule = '__fake_schedule';
				}
				$crons[ $timestamp ][ $hook ][ $key ]['schedule'] = $schedule;
			}
		}
	}
	return $crons;
}

/**
 * Override the default incremental sync schedule for Jetpack.
 *
 * @param string $schedule_name The schedule name.
 * @return string The modified schedule (hourly).
 */
function jetpack_sync_incremental_sync_interval( $schedule_name ) {
	return 'hourly';
}

/**
 * Override the default full sync schedule for Jetpack.
 *
 * @param string $schedule_name The schedule name.
 * @return string The modified schedule (twice each day).
 */
function jetpack_sync_full_sync_interval( $schedule_name ) {
	return 'twicedaily';
}
