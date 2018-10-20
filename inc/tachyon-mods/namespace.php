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
 *
 * @TODO: Remove custom image sizes that confuse both WP and Tachy.
 */
function bootstrap() {
	add_image_size( 'North', 400, 400, [ 'center', 'top' ] );
	add_image_size( 'South', 400, 400, [ 'center', 'bottom' ] );
	add_image_size( 'West', 400, 400, [ 'left', 'center' ] );
	add_image_size( 'East', 400, 400, [ 'right', 'center' ] );
	add_image_size( 'NorthWest', 400, 400, [ 'left', 'top' ] );
	add_image_size( 'NorthEast', 400, 400, [ 'right', 'top' ] );
	add_image_size( 'SouthWest', 400, 400, [ 'left', 'bottom' ] );
	add_image_size( 'SouthEast', 400, 400, [ 'right', 'bottom' ] );

	add_image_size( 'Bullseye', 400, 400, [ 'center', 'center' ] );

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

	/*
	 * Replace WordPress Core's responsive image filter with our own as
	 * the Core one doesn't work with Tachyon due to the sizing details
	 * being stored in the query string.
	 */
	remove_filter( 'the_content', 'wp_make_content_images_responsive' );
	// Runs very late to ensure images have passed through Tachyon first.
	add_filter( 'the_content', __NAMESPACE__ . '\\make_content_images_responsive', 999999 );
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
 * Filters 'img' elements in post content to add 'srcset' and 'sizes' attributes.
 *
 * @param string $content The raw post content to be filtered.
 * @return string Converted content with 'srcset' and 'sizes' attributes added to images.
 */
function make_content_images_responsive( $content ) {
	$images = \Tachyon::parse_images_from_html( $content );

	if ( empty( $images ) ) {
		// No images, leave early.
		return $content;
	}

	// This bit is from Core.
	$selected_images = $attachment_ids = [];
	foreach ( $images['img_tag'] as $image ) {
		if ( false === strpos( $image, ' srcset=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) && absint( $class_id[1] ) ) {
			$attachment_id = $class_id[1];
			/*
			 * If exactly the same image tag is used more than once, overwrite it.
			 * All identical tags will be replaced later with 'str_replace()'.
			 */
			$selected_images[ $image ] = $attachment_id;
			// Overwrite the ID when the same image is included more than once.
			$attachment_ids[ $attachment_id ] = true;
		}
	}

	if ( empty( $attachment_ids ) ) {
		// No WP attachments, nothing further to do.
		return $content;
	}

	/*
	 * Warm the object cache with post and meta information for all found
	 * images to avoid making individual database calls.
	 */
	_prime_post_caches( array_keys( $attachment_ids ), false, true );

	foreach ( $images['img_url'] as $key => $img_url ) {
		if ( strpos( $img_url , TACHYON_URL ) !== 0 ) {
			// It's not a Tachyon URL.
			continue;
		}

		$image_data = [
			'full_tag' => $images[0][ $key ],
			'link_url' => $images['link_url'][ $key ],
			'img_tag' => $images['img_tag'][ $key ],
			'img_url' => $images['img_url'][ $key ],
		];
		$image = $image_data['img_tag'];

		$attachment_id = $selected_images[ $images['img_tag'][ $key ] ];
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		$content = str_replace( $image, add_srcset_and_sizes( $image_data, $image_meta, $attachment_id ), $content );
	}

	return $content;
}

/**
 * Adds 'srcset' and 'sizes' attributes to an existing 'img' element.
 *
 * @TODO Deal with crop.
 *
 * @param array $image_data    The full data extracted via `make_content_images_responsive`.
 * @param array $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int   $attachment_id Image attachment ID.
 *
 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
 */
function add_srcset_and_sizes( $image_data, $image_meta, $attachment_id ) {
	$image = $image_data['img_tag'];
	$image_src = $image_data['img_url'];

	// Bail early if an image has been inserted and later edited.
	list( $image_path ) = explode( '?', $image_src );
	if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) &&
	     strpos( wp_basename( $image_path ), $img_edit_hash[0] ) === false ) {

		return $image;
	}

	$transform = 'fit';
	$width = false;
	$height = false;

	parse_str( html_entity_decode( parse_url( $image_data['img_url'], PHP_URL_QUERY ) ), $tachyon_args );

	// Need to work back width and height from various Tachyon options.
	if ( isset( $tachyon_args['resize'] ) ) {
		// Image is cropped.
		list( $width, $height ) = explode( ',', $tachyon_args['resize'] );
		$transform = 'resize';
	} elseif ( isset( $tachyon_args['fit'] ) ) {
		// Image is uncropped.
		list( $width, $height ) = explode( ',', $tachyon_args['fit'] );
	} else {
		if ( isset( $tachyon_args['w'] ) ) {
			$width = (int) $tachyon_args['w'];
		}
		if ( isset( $tachyon_args['h'] ) ) {
			$height = (int) $tachyon_args['h'];
		}
	}

	$image_filename = wp_basename( $image_path );
	$meta_matches = $image_filename === wp_basename( $image_meta['file'] );

	if ( ! $meta_matches && ! $width ) {
		// Unable to work out width.
		return $image;
	}

	if ( ! $width && ! $height ) {
		$width = (int) $image_meta['width'];
		$height = (int) $image_meta['height'];
	} elseif ( ! $height ) {
		$height = (int) ( $image_meta['height'] * ( $width / $image_meta['width'] ) );
	}

	// Still stumped?
	if ( ! $width || ! $height ) {
		return $image;
	}

	$size_array = array( $width, $height );
	$sources = [];
	$srcset = '';

	global $content_width;
	$std_srcset_widths = [
		320,
		480,
		768,
		$content_width,
		$content_width * 2,
		$image_meta['width'],
	];

	sort( $std_srcset_widths, SORT_NUMERIC );

	foreach( $std_srcset_widths as $srcset_width ) {
		if ( $height ) {
			$srcset_height = intval( $height * ( $srcset_width / $width ) );
			$args[ $transform ] = "{$srcset_width},{$srcset_height}";
		} else {
			$args[ 'w' ] = $srcset_width;
		}
		$args = array_merge( $args, array_intersect_key( $tachyon_args, [ 'gravity' => true ] ) );

		$source = array(
			'url'        => add_query_arg( $args, $image_path ),
			'descriptor' => 'w',
			'value'      => $srcset_width,
		);

		$sources[ $srcset_width ] = $source;

		unset( $args );
	}

	foreach ( $sources as $source ) {
		$srcset .= str_replace( ' ', '%20', $source['url'] ) . ' ' . $source['value'] . $source['descriptor'] . ', ';
	}

	$srcset = rtrim( $srcset, ', ' );

	if ( $srcset ) {
		// Check if there is already a 'sizes' attribute.
		$sizes = strpos( $image, ' sizes=' );

		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
		}
	}

	if ( $srcset && $sizes ) {
		// Format the 'srcset' and 'sizes' string and escape attributes.
		$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );

		if ( is_string( $sizes ) ) {
			$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
		}

		// Add 'srcset' and 'sizes' attributes to the image markup.
		$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
	}

	return $image;
}
