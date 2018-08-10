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
 * Runs as the plugin is loaded.
 */
function fast_bootstrap() {
	add_filter( 'backwpup_register_destination', __NAMESPACE__ . '\\remove_s3_conflict' );
}

/**
 * Bootstrap helpers.
 *
 * Runs on the `plugins_loaded` hook.
 */
function bootstrap() {
	JetpackFixes\bootstrap();

	// Use Tachyon in the admin.
	add_filter( 'tachyon_disable_in_admin', '__return_false' );
	// No need to resize on upload due to use in admin.
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
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
