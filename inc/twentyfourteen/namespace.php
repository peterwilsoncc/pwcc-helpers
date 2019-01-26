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
 *
 * @return array CSS assets to push/preload.
 */
function wp_push_styles() {
	global $wp_styles;
	$dependencies = $wp_styles;
	$push = [];

	$queue = $dependencies->queue;

	if ( empty( $queue ) ) {
		return [];
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

		if ( isset( $obj->args ) ) {
			$media = esc_attr( $obj->args );
		} else {
			$media = 'all';
		}

		if ( $media !== 'all' ) {
			// Browser may not want it early.
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

		$href = _css_src( $src, $ver, $handle );
		if ( ! $href ) {
			continue;
		}

		$push[] = "<$href>; rel=preload; as=style";
	}

	if ( ! $push ) {
		return [];
	}

	$push = array_unique( $push );

	// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	@header( 'Link: ' . implode( ', ', $push ), false );

	return $push;
}

/**
 * Send HTTP 2 Push headers for Queued JavaScript files.
 *
 * @TODO: Handle dependencies.
 * @TODO: Handle concatenated scripts.
 *
 * @return array JS assets to push/preload.
 */
function wp_push_scripts() {
	global $wp_scripts;
	$dependencies = $wp_scripts;
	$push = [];

	$queue = $dependencies->queue;

	if ( empty( $queue ) ) {
		return [];
	}

	if ( $dependencies->do_concat ) {
		// Ugh, let's not for now.
		return [];
	}

	foreach ( $queue as $handle ) {
		// No idea what to do.
		if ( ! isset( $dependencies->registered[ $handle ] ) ) {
			continue;
		}

		$obj = $dependencies->registered[ $handle ];
		$src = $obj->src;

		// No source, no push.
		if ( ! $src ) {
			continue;
		}

		// Browser may not need it.
		if ( isset( $obj->extra['conditional'] ) ) {
			continue;
		}

		// Only push header scripts
		if ( $obj->groups[ $handle ] > 0 ) {
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

		if ( ! empty( $ver ) ) {
			$src = add_query_arg( 'ver', $ver, $src );
		}

		/** This filter is documented in wp-includes/class.wp-scripts.php */
		$src = apply_filters( 'script_loader_src', $src, $handle );

		if ( ! $src ) {
			continue;
		}

		$push[] = "<$src>; rel=preload; as=script";
	}

	if ( ! $push ) {
		return [];
	}

	$push = array_unique( $push );

	// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	@header( 'Link: ' . implode( ', ', $push ), false );

	return $push;
}

/**
 * Enqueue assets required by 2014 theme.
 */
function enqueue_scripts() {
	twentyfourteen_scripts();

	wp_push_styles();
	wp_push_scripts();
}
