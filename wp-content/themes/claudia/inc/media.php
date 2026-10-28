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
 * Red de seguridad para imágenes antiguas que ya no están en disco (tamaños del tema anterior o JPG
 * redimensionados de los que solo queda la .webp). En vez de un 404, redirige (301) a la versión que sí
 * existe, para no perder enlaces externos ni visitas de Google Imágenes:
 *   foto-585x390.jpg  →  foto-585x390.jpg.webp  (si existe)
 *   foto-585x390.jpg  →  foto.jpg.webp / foto.jpg (el original)
 * Solo actúa cuando el servidor pasa a WordPress una petición de imagen inexistente (Apache con las reglas
 * estándar de WordPress y nginx con error_page 404 hacia index.php lo hacen).
 */
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	if ( ! preg_match( '#/wp-content/uploads/(.+\.(?:jpe?g|png|gif))$#i', rawurldecode( $path ), $m ) || str_contains( $m[1], '..' ) ) {
		return;
	}
	$uploads = wp_get_upload_dir();
	$rel     = $m[1];
	$candidates = [ $rel . '.webp' ];
	if ( preg_match( '/^(.+)-\d+x\d+\.(jpe?g|png|gif)$/i', $rel, $o ) ) {
		foreach ( [ $o[1] . '.' . $o[2], $o[1] . '-scaled.' . $o[2] ] as $original ) {
			array_push( $candidates, $original . '.webp', $original );
		}
	}
	foreach ( $candidates as $candidate ) {
		if ( is_file( $uploads['basedir'] . '/' . $candidate ) ) {
			wp_redirect( $uploads['baseurl'] . '/' . str_replace( '%2F', '/', rawurlencode( $candidate ) ), 301, 'Claudia' );
			exit;
		}
	}
}, 0 );

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
