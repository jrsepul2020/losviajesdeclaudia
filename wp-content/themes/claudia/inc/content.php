<?php
/**
 * Contenido de las entradas: índice automático y compatibilidad con shortcodes antiguos.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Añade un id a cada H2 del contenido y guarda la lista para pintar el índice.
 * Se ejecuta tras do_blocks/shortcodes (prioridad 20) para ver el HTML final.
 */
add_filter( 'the_content', function ( string $html ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $html;
	}
	$toc  = [];
	$used = [];
	$html = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#is', function ( $m ) use ( &$toc, &$used ) {
		$text = trim( wp_strip_all_tags( $m[2] ) );
		if ( '' === $text ) {
			return $m[0];
		}
		if ( preg_match( '/\sid="([^"]+)"/', $m[1], $id ) ) {
			$slug = $id[1];
			$attrs = $m[1];
		} else {
			$slug = sanitize_title( $text ) ?: 'seccion';
			$base = $slug;
			for ( $i = 2; isset( $used[ $slug ] ); $i++ ) {
				$slug = $base . '-' . $i;
			}
			$attrs = $m[1] . ' id="' . esc_attr( $slug ) . '"';
		}
		$used[ $slug ] = true;
		$toc[]         = [ $slug, $text ];
		return '<h2' . $attrs . '>' . $m[2] . '</h2>';
	}, $html );

	if ( count( $toc ) >= 3 ) {
		$list = '';
		foreach ( $toc as [ $slug, $text ] ) {
			$list .= '<li><a href="#' . esc_attr( $slug ) . '">' . esc_html( $text ) . '</a></li>';
		}
		$box = '<details class="toc" open><summary>Índice del artículo</summary><ol>' . $list . '</ol></details>';
		// El índice va tras el primer párrafo, como en la referencia.
		$pos  = strpos( $html, '</p>' );
		$html = false === $pos ? $box . $html : substr_replace( $html, '</p>' . $box, $pos, 4 );
	}
	return $html;
}, 20 );

/**
 * Shortcodes de plugins retirados: evitan que aparezca texto “[pt_view …]” en pantalla.
 * Las páginas hub que los usan se rehacen con su propia plantilla (fase 5).
 */
add_action( 'init', function () {
	foreach ( [ 'pt_view', 'vc_row', 'vc_column', 'vc_column_text', 'vc_single_image', 'vc_empty_space', 'wpgdprc_access_request_form' ] as $tag ) {
		if ( ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, fn( $atts, $content = '' ) => $content ? do_shortcode( $content ) : '' );
		}
	}
} );
