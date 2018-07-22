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
	add_filter( 'backwpup_register_destination', __NAMESPACE__ . '//remove_s3_conflict' );
}

/**
 * Bootstrap helpers.
 *
 * Runs on the `plugins_loaded` hook.
 */
function bootstrap() {
	// Do not resize on uploads, we use Tachyon.
	add_filter( 'intermediate_image_sizes_advanced', __return_empty_array() );
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

/**
 * Filter resize values for image uploads.
 *
 * This can be used to modify the values to resize images
 * to on uploads.
 *
 * @todo see if I can use Tachyon URLs in admin.
 *
 * Once the site uses Tachyon URLs in the admin this can
 * be changed to return an empty array. Until then it does nothing.
 *
 * Runs on the filter `intermediate_image_sizes_advanced`.
 *
 * @param array $sizes
 * @return array
 */
function intermediate_image_sizes( $sizes ) {
	return $sizes;
}
