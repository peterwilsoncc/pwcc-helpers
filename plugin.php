<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: PWCC Helpers.
 * Plugin URI:  https://peterwilson.cc/
 * Description: Various bits that help me.
 * Version:     %%VERSION%%
 * Author:      Peter Wilson
 * Author URI:  https://peterwilson.cc/
 * Text Domain: pwcc-notes
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
namespace PWCC\Helpers;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/cavalcade-mods/namespace.php';
require_once __DIR__ . '/inc/jetpack-fixes/namespace.php';
require_once __DIR__ . '/inc/tachyon-mods/namespace.php';
require_once __DIR__ . '/inc/twentyfourteen/namespace.php';

fast_bootstrap();
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );


add_action( 'template_redirect', function() {
	return;

	header( 'X-template_redirect: here' );

	$wrong = ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) );


	var_dump( 'template_redirect', function_exists( '\\twentyfourteen_scripts' ), $wrong );

	global $wp_query;
	var_dump( $wp_query );

	\twentyfourteen_scripts();
	exit;
} );
