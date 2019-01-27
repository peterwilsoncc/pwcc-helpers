<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */
namespace PWCC\Helpers;

/**
 * Fast Bootstrap helpers.
 *
 * These filters are needed before WP completes bootstrapping.
 *
 * Runs as the plugin is included.
 */
function fast_bootstrap() {
	add_filter( 'backwpup_register_destination', __NAMESPACE__ . '\\remove_s3_conflict' );
	JetpackFixes\fast_bootstrap();
}

/**
 * Bootstrap helpers.
 *
 * Runs on the `plugins_loaded` hook.
 */
function bootstrap() {
	JetpackFixes\bootstrap();
	CavalcadeMods\bootstrap();
	TachyonMods\bootstrap();
	TwentyFourteen\bootstrap();
}

/**
 * Remove AWS SDK Conflict b/w S3 Uploads and BackWPup.
 *
 * S3 Uploads and BackWPup include conflicting versions of the
 * AWS SDK. This removes S3 as an option from BackWPUp to remove
 * the conflict.
 *
 * Runs on the filter `backwpup_register_destination`.
 *
 * @param array $registered_destinations
 * @return array
 */
function remove_s3_conflict( $registered_destinations ) {
	unset( $registered_destinations['S3'] );

	return $registered_destinations;
}
