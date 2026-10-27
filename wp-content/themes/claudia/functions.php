<?php
/**
 * Tema Claudia: configuración, limpieza del <head> y carga de recursos.
 */

defined( 'ABSPATH' ) || exit;

define( 'CLAUDIA_VER', '0.1.0' );
define( 'CLAUDIA_DIR', get_template_directory() );
define( 'CLAUDIA_URI', get_template_directory_uri() );

require CLAUDIA_DIR . '/inc/helpers.php';
require CLAUDIA_DIR . '/inc/content.php';
require CLAUDIA_DIR . '/inc/navigation.php';
require CLAUDIA_DIR . '/inc/hubs.php';
require CLAUDIA_DIR . '/inc/media.php';
require CLAUDIA_DIR . '/inc/components.php';
require CLAUDIA_DIR . '/inc/monetization.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );

	register_nav_menus( [
		'primary-left'  => 'Menú principal (izquierda)',
		'primary-right' => 'Menú principal (derecha)',
		'footer'        => 'Menú del pie',
	] );

	// Ancho máximo del contenido para que WordPress calcule bien el atributo sizes.
	$GLOBALS['content_width'] = 760;
} );

/* -------------------------------------------------------------------------
 * Limpieza: fuera todo lo que WordPress añade y el tema no necesita.
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );

	// Variables y presets de theme.json: el tema define los suyos en main.css.
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
} );

// Solo los estilos de los bloques que aparecen en la página, no la librería completa.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
	wp_deregister_script( 'wp-embed' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	wp_enqueue_script( 'claudia', CLAUDIA_URI . '/assets/js/main.js', [], CLAUDIA_VER, [ 'strategy' => 'defer', 'in_footer' => true ] );
}, 100 );

/* -------------------------------------------------------------------------
 * CSS en línea (cero peticiones que bloqueen el renderizado) y precarga de fuentes.
 * ---------------------------------------------------------------------- */
add_action( 'wp_head', function () {
	foreach ( [ 'montserrat-latin-wght', 'oswald-latin-wght' ] as $font ) {
		printf( '<link rel="preload" href="%s/assets/fonts/%s.woff2" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( CLAUDIA_URI ), $font );
	}
	$css = file_get_contents( CLAUDIA_DIR . '/assets/css/main.css' );
	$css = str_replace( '{{THEME}}', CLAUDIA_URI, $css );
	echo '<style id="claudia-css">' . claudia_minify_css( $css ) . '</style>' . "\n";
}, 2 );

/* -------------------------------------------------------------------------
 * Ajustes de consultas e imágenes.
 * ---------------------------------------------------------------------- */
// Yoast (“limpiar parámetros de URL”) entraba en bucle de redirecciones con ?s= en este entorno.
add_filter( 'Yoast\WP\SEO\allowlist_permalink_vars', fn( array $vars ) => array_merge( $vars, [ 's' ] ) );

// El H1 de categorías y etiquetas es solo el nombre, igual que en la web anterior.
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_filter( 'excerpt_length', fn() => 22 );
add_filter( 'excerpt_more', fn() => '…' );

// La imagen principal de la cabecera no debe cargarse en diferido.
add_filter( 'wp_omit_loading_attr_threshold', fn() => 0 );
