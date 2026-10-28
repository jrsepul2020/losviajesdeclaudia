<?php
/**
 * Rellena los textos alternativos (ALT) vacíos, sin tocar los que ya existen.
 *
 * En el contenido de entradas y páginas, para cada <img> sin ALT se usa, por este orden:
 *   1. el ALT guardado en la biblioteca para esa imagen (clase wp-image-ID);
 *   2. la leyenda de la imagen (<figcaption> o [caption]);
 *   3. el título de la imagen en la biblioteca, si es descriptivo (no «image», «IMG_1234»…);
 *   4. el último encabezado anterior a la imagen + el título del artículo.
 * En la biblioteca, cada imagen sin ALT recibe su título descriptivo o, si no lo tiene, el del artículo al que pertenece.
 *
 * Uso: wp eval-file rellenar-alt.php [aplicar] [informe.csv]
 */

global $wpdb;

$apply  = in_array( 'aplicar', $args, true );
$report = current( array_filter( $args, fn( $a ) => str_ends_with( $a, '.csv' ) ) );
$rows   = [];

function claudia_alt_clean( string $text ): string {
	$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
	return mb_strimwidth( $text, 0, 125, '…' );
}

function claudia_alt_from_title( string $title ): string {
	$t = trim( preg_replace( '/[-_]+/', ' ', $title ) );
	do { // quita sufijos técnicos encadenados: «… 585x390 1», «… scaled», «… e1550222946538»
		$prev = $t;
		$t    = preg_replace( '/\s+(e\d{6,}|\d+x\d+|scaled|copia|copy|\d{1,3})$/i', '', $t );
	} while ( $t !== $prev );
	if ( mb_strlen( $t ) < 8 || preg_match( '/^(image|imagen|img|dsc|dscn|foto|photo|captura|screenshot|whatsapp|pxl|wp)\b[\s\d]*$/i', $t ) ) {
		return '';
	}
	return claudia_alt_clean( mb_strtoupper( mb_substr( $t, 0, 1 ) ) . mb_substr( $t, 1 ) );
}

// 1) Contenido publicado
$posts = $wpdb->get_results( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post','page')" );
foreach ( $posts as $p ) {
	$content = $p->post_content;
	$changed = false;
	$offset  = 0;
	$new     = preg_replace_callback( '/<img\b[^>]*>/i', function ( $m ) use ( $p, &$changed, $content, &$rows, &$offset ) {
		$tag = $m[0][0];
		$pos = $m[0][1];
		if ( preg_match( '/\salt="[^"]+"/i', $tag ) ) {
			return $tag;
		}
		$alt    = '';
		$source = '';
		if ( preg_match( '/wp-image-(\d+)/', $tag, $id ) ) {
			$alt    = claudia_alt_clean( (string) get_post_meta( (int) $id[1], '_wp_attachment_image_alt', true ) );
			$source = 'biblioteca';
		}
		if ( '' === $alt ) {
			$after = substr( $content, $pos, 1200 );
			if ( preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#is', $after, $c ) || preg_match( '#^[^\[]*?\]?\s*(?:<img[^>]*>)\s*([^\[<]{8,}?)\[/caption\]#is', $after, $c ) ) {
				$alt    = claudia_alt_clean( $c[1] );
				$source = 'leyenda';
			}
		}
		if ( '' === $alt && ! empty( $id[1] ) ) {
			$alt    = claudia_alt_from_title( get_the_title( (int) $id[1] ) );
			$source = 'título de la imagen';
		}
		if ( '' === $alt ) {
			$before = substr( $content, 0, $pos );
			$head   = preg_match_all( '#<h[2-4][^>]*>(.*?)</h[2-4]>#is', $before, $h ) ? claudia_alt_clean( end( $h[1] ) ) : '';
			$alt    = claudia_alt_clean( $head ? $head . ' – ' . $p->post_title : $p->post_title );
			$source = $head ? 'encabezado + título' : 'título del artículo';
		}
		$changed = true;
		$src     = preg_match( '/\ssrc="([^"]+)"/', $tag, $s ) ? basename( $s[1] ) : '';
		$rows[]  = [ 'contenido', $p->ID, get_permalink( $p->ID ), $src, $alt, $source ];
		$tag     = preg_match( '/\salt=""/', $tag ) ? preg_replace( '/\salt=""/', ' alt="' . esc_attr( $alt ) . '"', $tag, 1 ) : preg_replace( '/<img\b/i', '<img alt="' . esc_attr( $alt ) . '"', $tag, 1 );
		return $tag;
	}, $content, -1, $count, PREG_OFFSET_CAPTURE );
	if ( $changed && $apply ) {
		$wpdb->update( $wpdb->posts, [ 'post_content' => $new ], [ 'ID' => $p->ID ] );
		clean_post_cache( $p->ID );
	}
}

// 2) Biblioteca de medios
$atts = $wpdb->get_results( "SELECT p.ID, p.post_title, p.post_parent FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attachment_image_alt' WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%' AND ( m.meta_value IS NULL OR m.meta_value = '' )" );
foreach ( $atts as $a ) {
	$alt    = claudia_alt_from_title( $a->post_title );
	$source = 'título de la imagen';
	if ( '' === $alt && $a->post_parent ) {
		$alt    = claudia_alt_clean( get_the_title( $a->post_parent ) );
		$source = 'título del artículo';
	}
	if ( '' === $alt ) {
		continue;
	}
	$rows[] = [ 'biblioteca', $a->ID, wp_get_attachment_url( $a->ID ), basename( (string) get_attached_file( $a->ID ) ), $alt, $source ];
	if ( $apply ) {
		update_post_meta( $a->ID, '_wp_attachment_image_alt', $alt );
	}
}

if ( $report ) {
	$fh = fopen( $report, 'w' );
	fputcsv( $fh, [ 'dónde', 'id', 'url', 'archivo', 'alt', 'origen' ] );
	foreach ( $rows as $r ) {
		fputcsv( $fh, $r );
	}
	fclose( $fh );
}
$by = array_count_values( array_map( fn( $r ) => $r[0] . ' / ' . $r[5], $rows ) );
WP_CLI::log( ( $apply ? 'APLICADO' : 'SIMULACIÓN' ) . ': ' . count( $rows ) . ' ALT' );
foreach ( $by as $k => $n ) {
	WP_CLI::log( "  $k: $n" );
}
