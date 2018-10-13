<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */
namespace PWCC\Helpers\TachyonMods;

/**
 * Bootstrap Tachyon mods.
 */
function bootstrap() {
	// Ensure Tachyon is set up before running.
	if ( ! function_exists( 'tachyon_url' ) ) {
		return;
	}

	if ( ! defined( 'TACHYON_URL' ) || ! TACHYON_URL ) {
		return;
	}

	// Use Tachyon in the admin, avoid resize on upload.
	add_filter( 'tachyon_disable_in_admin', '__return_false' );
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
}

/**
 * Provide an array of available image sizes and corresponding dimensions.
 * Similar to get_intermediate_image_sizes() except that it includes image sizes'
 * dimensions, not just their names.
 *
 * Credit: Automattic's Jetpack plugin.
 *
 * @link https://github.com/Automattic/jetpack/blob/master/class.photon.php
 *
 * @global $wp_additional_image_sizes array Custom registered image sizes.
 * @uses get_option
 *
 * @return array All image sizes available including core defaults.
 */
function image_sizes() {
	global $_wp_additional_image_sizes;
	static $image_sizes = null;

	// Save time, only calculate size array once.
	if ( $image_sizes !== null ) {
		return $image_sizes;
	}

	/*
	 * Populate an array matching the data structure of $_wp_additional_image_sizes
	 * so we have a consistent structure for image sizes.
	 */
	$images = [
		'thumb'        => [
			'width'  => intval( get_option( 'thumbnail_size_w' ) ),
			'height' => intval( get_option( 'thumbnail_size_h' ) ),
			'crop'   => (bool) get_option( 'thumbnail_crop' ),
		],
		'medium'       => [
			'width'  => intval( get_option( 'medium_size_w' ) ),
			'height' => intval( get_option( 'medium_size_h' ) ),
			'crop'   => false,
		],
		'medium_large' => [
			'width'  => intval( get_option( 'medium_large_size_w' ) ),
			'height' => intval( get_option( 'medium_large_size_h' ) ),
			'crop'   => false,
		],
		'large'        => [
			'width'  => intval( get_option( 'large_size_w' ) ),
			'height' => intval( get_option( 'large_size_h' ) ),
			'crop'   => false,
		],
		'full'         => [
			'width'  => null,
			'height' => null,
			'crop'   => false,
		],
	];

	// Compatibility mapping as found in wp-includes/media.php
	$images['thumbnail'] = $images['thumb'];

	// Merge in `$_wp_additional_image_sizes` if any are set.
	if ( is_array( $_wp_additional_image_sizes ) && ! empty( $_wp_additional_image_sizes ) ) {
		$image_sizes = array_merge( $images, $_wp_additional_image_sizes );
	} else {
		$image_sizes = $images;
	}

	return $image_sizes;
}
