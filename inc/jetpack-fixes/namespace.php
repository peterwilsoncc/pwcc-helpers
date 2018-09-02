<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */
namespace PWCC\Helpers\JetpackFixes;

use Jetpack;

/**
 * Fast boostrap for Jetpack fixes.
 *
 * Runs as the plugin is included.
 */
function fast_bootstrap() {
	/*
	 * Filter Jetpack Schedules run less frequently.
	 *
	 * By default JP runs both a full and incremental sync
	 * every five minutes. This slows them down to hourly and
	 * every twelve hours respectively.
	 */
	add_filter( 'jetpack_sync_incremental_sync_interval', __NAMESPACE__ . '\\jetpack_sync_incremental_sync_interval' );
	add_filter( 'jetpack_sync_full_sync_interval', __NAMESPACE__ . '\\jetpack_sync_full_sync_interval' );
}

/**
 * Bootstrap Jetpack Fixes.
 *
 * Runs on the `plugins_loaded` hook.
 */
function bootstrap() {
	add_filter( 'jetpack_implode_frontend_css', __NAMESPACE__ . '\\maybe_implode_css' );
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

/**
 * Use a bit of smarts to determine if Jetpack CSS should be imploded.
 *
 * Jetpack will include its concatenated CSS file on every page of a site,
 * regardless of the number of files enqueued by modules.
 *
 * If no front-end modules are enabled, this results in a 30K of unused
 * CSS being included in the HTML header.
 *
 * If one module front-end module is enabled, the smaller file is removed
 * and a larger file loaded in its place.
 *
 * This function only allows the concatenated file to be enqueued if two or
 * more front-end modules are loaded. The use of HTTP/2 could be detected and
 * concatenation disabled if it's in use but the performance affects of this
 * need to be measured and I am on holiday.
 *
 * @todo Determine if HTTP2 traffic should always use the separate CSS files.
 *
 * Runs on the filter `jetpack_implode_frontend_css`.
 *
 * @param bool $do_implode Initial decision to implode/not implode CSS.
 * @return bool Whether to implode CSS.
 */
function maybe_implode_css( $do_implode ) {
	if ( ! $do_implode ) {
		// It's already been decided not to implode.
		return $do_implode;
	}

	$jetpack = Jetpack::init();
	$jetpack_styles = $jetpack->concatenated_style_handles;
	$enqueued_count = 0;

	foreach ( $jetpack_styles as $style ) {
		if ( wp_style_is( $style, 'enqueued' ) ) {
			$enqueued_count++;
		}

		if ( $enqueued_count >= 2 ) {
			// Two or more files enqueued, implode.
			return $do_implode;
		}
	}

	// Zero or one file enqueued. Do not implode.
	return false;
}
