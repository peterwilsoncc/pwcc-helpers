<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */

namespace PWCC\Helpers\CavalcadeMods;

const COMPLETED_CLEANUP_HOOK = 'pwcc.helpers.cavalcade-mods.completed';
const FAILED_CLEANUP_HOOK = 'pwcc.helpers.cavalcade-mods.failed';

/**
 * Bootstrap Cavalcade Mods.
 *
 * Runs on the `plugins_loaded` action.
 */
function bootstrap() {
	schedule_db_cleanup();

	add_action( COMPLETED_CLEANUP_HOOK, __NAMESPACE__ . '\\cleanup_completed_jobs' );
	add_action( FAILED_CLEANUP_HOOK, __NAMESPACE__ . '\\cleanup_failed_jobs' );
}

/**
 * Schedule clean up for completed jobs in the Database.
 */
function schedule_db_cleanup() {
	if ( ! wp_next_scheduled( COMPLETED_CLEANUP_HOOK ) ) {
		wp_schedule_event( time(), 'daily', COMPLETED_CLEANUP_HOOK );
	}

	if ( ! wp_next_scheduled( FAILED_CLEANUP_HOOK ) ) {
		wp_schedule_event( time(), 'daily', FAILED_CLEANUP_HOOK );
	}
}

/**
 * Cleanup log of completed jobs older than three days.
 *
 * Runs on the hook defined by the constant COMPLETED_CLEANUP_HOOK.
 */
function cleanup_completed_jobs() {
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->base_prefix}cavalcade_jobs
		WHERE status='completed' AND nextrun < NOW() - INTERVAL 3 DAY"
	);

	$wpdb->query(
		"DELETE from {$wpdb->base_prefix}cavalcade_logs
		 WHERE status='completed'
		   AND timestamp < NOW() - INTERVAL 3 DAY"
	);
}

/**
 * Cleanup log of failed jobs older than three months.
 *
 * Runs on the hook defined by the constant FAILED_CLEANUP_HOOK.
 */
function cleanup_failed_jobs() {
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->base_prefix}cavalcade_jobs
		WHERE status='failed' AND nextrun < NOW() - INTERVAL 3 MONTH"
	);

	$wpdb->query(
		"DELETE from {$wpdb->base_prefix}cavalcade_logs
		 WHERE status='failed'
		   AND timestamp < NOW() - INTERVAL 3 MONTH"
	);
}
