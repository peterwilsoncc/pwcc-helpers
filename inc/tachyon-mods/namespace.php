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

	// Use Tachyon in the admin.
	add_filter( 'tachyon_disable_in_admin', '__return_false' );
	// No need to resize on upload due to use in admin.
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
}
