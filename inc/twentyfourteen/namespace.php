<?php
/**
 * PWCC Helpers.
 *
 * @package     PWCC Helpers
 * @author      Peter Wilson
 * @copyright   2018 Peter Wilson
 * @license     GPL-2.0+
 */
namespace PWCC\Helpers\TwentyFourteen;

/**
 * Boostrap the plugin.
 *
 * Need to wait until the theme is set up before attempting to boostrap.
 */
function bootstrap() {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\after_theme_bootstrap' );
}

/**
 * Bootstrap once the theme is available.
 */
function after_theme_bootstrap() {
	global $wp_styles;
	if ( ! function_exists( '\\twentyfourteen_setup' ) ) {
		// The site is not using twentyfourteen. Bail.
		return;
	}

	add_action( 'template_redirect', __NAMESPACE__ . '\\enqueue_scripts' );
	remove_action( 'wp_enqueue_scripts', 'twentyfourteen_scripts' );
}

/**
 * Generates an enqueued style's fully-qualified URL.
 *
 * Source is $wp-styles->_css_href but this is unescaped.
 *
 * @param string $src The source of the enqueued style.
 * @param string $ver The version of the enqueued style.
 * @param string $handle The style's registered handle.
 * @return string Style's fully-qualified URL.
 */
function _css_src( $src, $ver, $handle ) {
	global $wp_styles;
	$dependencies = $wp_styles;

	if ( ! is_bool( $src ) && ! preg_match( '|^(https?:)?//|', $src ) && ! ( $dependencies->content_url && 0 === strpos( $src, $dependencies->content_url ) ) ) {
		$src = $dependencies->base_url . $src;
	}

	if ( ! empty( $ver ) ) {
		$src = add_query_arg( 'ver', $ver, $src );
	}

	$src = apply_filters( 'style_loader_src', $src, $handle );
	return $src;
}

/**
 * Send HTTP 2 Push headers for Queued CSS files.
 *
 * @TODO: Handle dependencies.
 */
function wp_push_styles() {
	global $wp_styles;
	$dependencies = $wp_styles;
	$push = [];

	$queue = $dependencies->queue;

	if ( empty( $queue ) ) {
		return;
	}

	foreach ( $queue as $handle ) {
		// No idea what to do.
		if ( ! isset( $dependencies->registered[ $handle ] ) ) {
			continue;
		}

		$obj = $dependencies->registered[ $handle ];
		$alt = isset( $obj->extra['alt'] ) && $obj->extra['alt'];

		// Browser may not need it.
		if ( isset( $obj->extra['conditional'] ) || $alt ) {
			continue;
		}

		if ( null === $obj->ver ) {
			$ver = '';
		} else {
			$ver = $obj->ver ? $obj->ver : $dependencies->default_version;
		}

		if ( isset( $dependencies->args[ $handle ] ) ) {
			$ver = $ver ? $ver . '&amp;' . $dependencies->args[ $handle ] : $dependencies->args[ $handle ];
		}

		$src = $obj->src;

		if ( isset( $obj->args ) ) {
			$media = esc_attr( $obj->args );
		} else {
			$media = 'all';
		}

		$href = _css_src( $src, $ver, $handle );
		if ( ! $href ) {
			continue;
		}

		$push[] = "<$href>; rel=preload; as=style";
	}

	if ( ! $push ) {
		return;
	}

	header( 'Link: ' . implode( ', ', array_unique( $push ) ), false );

	return $push;
}

/**
 * Enqueue and push a script.
 *
 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
 *
 * @see WP_Dependencies::add()
 * @see WP_Dependencies::add_data()
 * @see WP_Dependencies::enqueue()
 *
 * @since 2.1.0
 *
 * @param string           $handle    Name of the script. Should be unique.
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    Default empty.
 * @param array            $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
 *                                    as a query string for cache busting purposes. If version is set to false, a version
 *                                    number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 */
function wp_push_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

	// Don't push if the JS is just being enqueued or is loaded in the footer.
	if ( ! $src || ! $in_footer ) {
		return;
	}

	$http_header = '';
	$http_header .= 'Link: ';
	$http_header .= "<$src> ;";
	$http_header .= 'rel=preload; ';
	$http_header .= 'as=script; ';

	header( $http_header, false );
}

/**
 * Enqueue assets required by 2014 theme.
 */
function enqueue_scripts() {
	twentyfourteen_scripts();

	wp_push_styles();
}
