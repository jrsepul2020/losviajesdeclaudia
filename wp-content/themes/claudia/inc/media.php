<?php
/**
 * Imágenes: sirve las versiones WebP que Imagify ya generó (foto.jpg.webp) y limita el
 * tamaño máximo del srcset en las tarjetas para no descargar fotos de 1920 px en miniaturas.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Devuelve la URL .webp equivalente si el archivo existe en disco; si no, la original.
 */
function claudia_webp_url( string $url ): string {
	static $uploads = null, $cache = [];
	if ( ! preg_match( '/\.(jpe?g|png)$/i', $url ) ) {
		return $url;
	}
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}
	$uploads ??= wp_get_upload_dir();
	$base_url  = set_url_scheme( $uploads['baseurl'] );
	$path      = str_replace( [ $base_url, set_url_scheme( $uploads['baseurl'], 'http' ), set_url_scheme( $uploads['baseurl'], 'https' ) ], $uploads['basedir'], $url );
	return $cache[ $url ] = ( $path !== $url && is_file( $path . '.webp' ) ) ? $url . '.webp' : $url;
}

function claudia_webp_srcset( string $srcset ): string {
	return preg_replace_callback( '/(\S+\.(?:jpe?g|png))(\s+\d+w)/i', fn( $m ) => claudia_webp_url( $m[1] ) . $m[2], $srcset );
}

// Imágenes que pinta el tema (tarjetas, cabeceras).
add_filter( 'wp_get_attachment_image_attributes', function ( array $attr ): array {
	if ( is_admin() ) {
		return $attr;
	}
	if ( ! empty( $attr['src'] ) ) {
		$attr['src'] = claudia_webp_url( $attr['src'] );
	}
	if ( ! empty( $attr['srcset'] ) ) {
		$attr['srcset'] = claudia_webp_srcset( $attr['srcset'] );
	}
	return $attr;
}, 20 );

// Imágenes dentro del contenido de entradas y páginas (después de que WordPress añada srcset y lazy).
add_filter( 'wp_content_img_tag', function ( string $img ): string {
	$img = preg_replace_callback( '/\ssrc="([^"]+)"/', fn( $m ) => ' src="' . esc_url( claudia_webp_url( html_entity_decode( $m[1] ) ) ) . '"', $img );
	return preg_replace_callback( '/\ssrcset="([^"]+)"/', fn( $m ) => ' srcset="' . esc_attr( claudia_webp_srcset( html_entity_decode( $m[1] ) ) ) . '"', $img );
}, 20 );

/**
 * Ejecuta $render con el srcset limitado a $max píxeles de ancho (para tarjetas y miniaturas).
 */
function claudia_with_max_srcset( int $max, callable $render ) {
	$limit = fn() => $max;
	add_filter( 'max_srcset_image_width', $limit );
	try {
		return $render();
	} finally {
		remove_filter( 'max_srcset_image_width', $limit );
	}
}
