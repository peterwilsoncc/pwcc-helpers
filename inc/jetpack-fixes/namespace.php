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
 * Bootstrap Jetpack Fixes.
 *
 * Runs on the `plugins_loaded` hook.
 */
function bootstrap() {
	add_filter( 'jetpack_implode_frontend_css', __NAMESPACE__ . '\\maybe_implode_css' );
}

/**
 * Use a bit of smarts to determine if Jetpack CSS should be imploded.
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
			// Two or more files enqueued, no further checks required.
			break;
		}
	}

	return $enqueued_count >= 2 ? $do_implode : false;
}
