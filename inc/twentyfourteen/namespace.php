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
}

/**
 * Enqueue and server push a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @param string           $handle Name of the stylesheet. Should be unique.
 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 *                                 Default empty.
 * @param array            $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
 *                                 as a query string for cache busting purposes. If version is set to false, a version
 *                                 number is automatically added equal to current installed WordPress version.
 *                                 If set to null, no version is added.
 * @param string           $media  Optional. The media for which this stylesheet has been defined.
 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
 */
function wp_push_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	wp_enqueue_style( $handle, $src, $deps, $ver, $media );

	// Don't push if the CSS is just being enqueued.
	if ( ! $src ) {
		return;
	}

	$http_header = '';
	$http_header .= 'Link: ';
	$http_header .= "<$src> ;";
	$http_header .= 'rel=preload; ';
	$http_header .= 'as=style; ';

	header( $http_header, false );
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
	// Add Lato font, used in the main stylesheet.
	wp_push_style( 'twentyfourteen-lato', twentyfourteen_font_url(), array(), null );

	// Add Genericons font, used in the main stylesheet.
	wp_push_style( 'genericons', get_template_directory_uri() . '/genericons/genericons.css', array(), '3.0.3' );

	// Load our main stylesheet.
	wp_push_style( 'twentyfourteen-style', get_stylesheet_uri() );

	// Theme block stylesheet.
	wp_push_style( 'twentyfourteen-block-style', get_template_directory_uri() . '/css/blocks.css', array( 'twentyfourteen-style' ), '20181230' );

	// Load the Internet Explorer specific stylesheet.
	wp_enqueue_style( 'twentyfourteen-ie', get_template_directory_uri() . '/css/ie.css', array( 'twentyfourteen-style' ), '20131205' );
	wp_style_add_data( 'twentyfourteen-ie', 'conditional', 'lt IE 9' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_push_script( 'comment-reply' );
	}

	if ( is_singular() && wp_attachment_is_image() ) {
		wp_push_script( 'twentyfourteen-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20130402' );
	}

	if ( is_active_sidebar( 'sidebar-3' ) ) {
		wp_push_script( 'jquery-masonry' );
	}

	if ( is_front_page() && 'slider' == get_theme_mod( 'featured_content_layout' ) ) {
		wp_push_script( 'twentyfourteen-slider', get_template_directory_uri() . '/js/slider.js', array( 'jquery' ), '20131205', true );
		wp_localize_script( 'twentyfourteen-slider', 'featuredSliderDefaults', array(
			'prevText' => __( 'Previous', 'twentyfourteen' ),
			'nextText' => __( 'Next', 'twentyfourteen' )
		) );
	}

	wp_push_script( 'twentyfourteen-script', get_template_directory_uri() . '/js/functions.js', array( 'jquery' ), '20150315', true );
}
