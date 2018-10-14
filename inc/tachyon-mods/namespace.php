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

	// Fake the image meta data.
	add_filter( 'wp_get_attachment_metadata', __NAMESPACE__ . '\\filter_attachment_meta_data', 10, 2 );

	// Ensure the gravity string is nice.
	add_filter( 'tachyon_image_downsize_string', __NAMESPACE__ . '\\filter_tachyon_gravity', 10, 2 );

	// Ensure WP has the srcset data it needs.
	add_filter( 'wp_calculate_image_srcset_meta', __NAMESPACE__ . '\\filter_image_srcset_meta', 10, 4 );
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

/**
 * Fake attachment meta data to include all image sizes.
 *
 * This attempts to fix two issues:
 *  - "new" image sizes are not included in meta data.
 *  - when using Tachyon in admin and disabling resizing,
 *    NO image sizes are included in the meta data.
 *
 * @param $data          array The original attachment meta data.
 * @param $attachment_id int The attachment ID.
 *
 * @return array The modified attachment data including "new" image sizes.
 */
function filter_attachment_meta_data( $data, $attachment_id ) {
	// Only modify if valid format and for images.
	if ( ! is_array( $data ) || ! wp_attachment_is_image( $attachment_id ) ) {
		return $data;
	}

	// Full size image info.
	$image_sizes = image_sizes();
	$mime_type = get_post_mime_type( $attachment_id );
	$filename = pathinfo( $data['file'], PATHINFO_FILENAME );
	$ext = pathinfo( $data['file'], PATHINFO_EXTENSION );
	$orig_w = $data['width'];
	$orig_h = $data['height'];

	foreach ( $image_sizes as $size => $crop ) {
		if ( isset( $data['sizes'][ $size ] ) ) {
			// Meta data is set.
			continue;
		}
		if ( 'full' === $size ) {
			// Full is a special case.
			continue;
		}
		$new_dims = image_resize_dimensions( $orig_w, $orig_h, $crop['width'], $crop['height'], $crop['crop'] );
		/*
		 * $new_dims = [
		 *    0 => 0
		 *    1 => 0
		 *    2 => // Crop start X axis
		 *    3 => // Crop start Y axis
		 *    4 => // New width
		 *    5 => // New height
		 *    6 => // Crop width on source image
		 *    7 => // Crop height on source image
		 * ];
		*/
		if ( ! $new_dims ) {
			continue;
		}
		$w = (int) $new_dims[4];
		$h = (int) $new_dims[5];

		// Set crop hash if source crop isn't 0,0,orig_width,orig_height
		$crop_details = "{$orig_w},{$orig_h},{$new_dims[2]},{$new_dims[3]},{$new_dims[6]},{$new_dims[7]}";
		$crop_hash = '';
		if ( $crop_details !== "{$orig_w},{$orig_h},0,0,{$orig_w},{$orig_h}" ) {
			/*
			 * NOTE: Custom file name data.
			 *
			 * The crop hash is used to help determine the correct crop to use for identically
			 * sized images.
			 */
			$crop_hash = '-c' . substr( strtolower( sha1( $crop_details ) ), 0, 8 );
		}
		// Add meta data with fake WP style file name.
		$data['sizes'][ $size ] = [
			'width' => $w,
			'height' => $h,
			'file' => "{$filename}{$crop_hash}-{$w}x{$h}.{$ext}",
			'mime-type' => $mime_type,
		];
	}

	return $data;
}

/**
 * Tachyon gravity string can sometimes be reversed.
 *
 * Gravity is sometimes reported as east/west before north/south.
 * This causes problems with the service as `eastnorth` is not recognised.
 *
 * @todo maybe remove normalising for portrait/landscape.
 *
 * @param $tachyon_args array Arguments for calling Tachyon.
 * @param $image array The image details.
 *
 * @return array Modified arguments with gravity corrected.
 */
function filter_tachyon_gravity( $tachyon_args, $image ) {
	if ( ! is_array( $tachyon_args ) || ! isset( $tachyon_args['gravity'] ) ) {
		return $tachyon_args;
	}

	$gravity = $tachyon_args['gravity'];

	// Ensure Gravity is not empty.
	if ( $gravity === '' ) {
		unset( $tachyon_args['gravity'] );
		return $tachyon_args;
	}

	// Check if too short to need modification.
	if ( strlen( $gravity ) <= 5 ) {
		return $tachyon_args;
	}

	// Ensure direction is correct: northeast instead of eastnorth.
	$gravity_tail = substr( $gravity, -5 );
	if ( $gravity_tail === 'north' || $gravity_tail === 'south' ) {
		$gravity = $gravity_tail . substr( $gravity, 0, -5 );
	}

	// Normalise for orientation.
	$image_id  = $image['attachment_id'];
	$image_meta  = wp_get_attachment_metadata( $image_id );

	$orientation = '';
	if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
		$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
	}

	switch ( $orientation ) {
		case 'portrait':
			if ( strpos( $gravity, 'north' ) !== false ) {
				$gravity = 'north';
			} elseif ( strpos( $gravity, 'south' ) !== false ) {
				$gravity = 'south';
			}
			break;
		case 'landscape':
			if ( strpos( $gravity, 'east' ) !== false ) {
				$gravity = 'east';
			} elseif ( strpos( $gravity, 'west' ) !== false ) {
				$gravity = 'west';
			}
			break;
	}

	$tachyon_args['gravity'] = $gravity;
	return $tachyon_args;
}

/**
 * Filter the meta data WordPress uses to generate the srcset.
 *
 * @TODO Work out how the h*ck to fix this.
 *
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param array  $size_array    Array of width and height values in pixels (in that order).
 * @param string $image_src     The 'src' of the image.
 * @param int    $attachment_id The image attachment ID or 0 if not supplied.
 *
 * @return array The modified image meta for generating the srcset.
 */
function filter_image_srcset_meta( $image_meta, $size_array, $image_src, $attachment_id ) {
	/*
	 * Because I use Tachyon in the admin/inserted data at this point
	 * WordPress is very confused and returns the full size image as the URL.
	 *
	 * This in turn confuses Tachyon so it doesn't know which crop to use either.
	 *
	 * The result of all this confusing is that the wrong size images get included
	 * in the srcset. Which is a problem.
	 *
	 * So I've ripped out the srcset as a temporary measure.
	 *
	 * CAUSE
	 *
	 * In `wp_image_add_srcset_and_sizes()` WordPress removes the querystring
	 * from any WordPress image URLs.
	 *
	 * In this filter `$image_src` becomes incorrect as a result.
	 *
	 * POTENTIAL FIXES
	 *
	 * Before WP filters the content, add our own filter to fake the URL to the
	 * URL format. That seems expensive.
	 *
	 * Replace the `wp_make_content_images_responsive` filter on `the_content`, that
	 * seems less expensive but will be a pain to maintain. It require some sort of forking
	 * to deal with old posts.
	 *
	 * Give up and don't use tachyon in the admin. But this will hit problems with Gutenberg
	 * which gets image URLs from the WP REST API in which `is_admin() === false`.
	 */

	unset( $image_meta['sizes'] );

	return $image_meta;
}
