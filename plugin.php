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
 * Version:     1.0.0
 * Author:      Peter Wilson
 * Author URI:  https://peterwilson.cc/
 * Text Domain: pwcc-notes
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
namespace PWCC\Helpers;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/jetpack-fixes/namespace.php';

fast_bootstrap();
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
