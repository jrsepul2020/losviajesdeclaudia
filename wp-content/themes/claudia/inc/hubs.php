<?php
/**
 * Páginas hub de destino: el shortcode [claudia_guias] sustituye a las vistas de Content Views.
 *
 * [claudia_guias categorias="chipre"]                 Guías de una o varias categorías (con subcategorías).
 * [claudia_guias tipo="page" ids="12,34"]             Tarjetas de páginas (p. ej. provincias o países).
 * [claudia_guias ids="1,2" estilo="carrusel"]         Entradas concretas en carrusel.
 * Atributos opcionales: limite (100 por defecto).
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'claudia_guias', function ( $atts ): string {
	$a = shortcode_atts( [ 'categorias' => '', 'ids' => '', 'tipo' => 'post', 'limite' => 100, 'estilo' => 'rejilla' ], $atts, 'claudia_guias' );

	$type = 'page' === $a['tipo'] ? 'page' : 'post';
	$args = [
		'post_type'           => $type,
		'post_status'         => 'publish',
		'posts_per_page'      => min( 200, max( 1, (int) $a['limite'] ) ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	];
	$ids = array_filter( array_map( 'absint', explode( ',', $a['ids'] ) ) );
	if ( $ids ) {
		$args['post__in'] = $ids;
		$args['orderby']  = 'post__in';
	}
	if ( $a['categorias'] && 'post' === $type ) {
		$args['tax_query'] = [ [ 'taxonomy' => 'category', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['categorias'] ) ) ] ];
	}
	if ( ! $ids && ! $a['categorias'] ) {
		return '';
	}

	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '';
	}

	// Subdestinos (páginas): tarjetas con la parte baja oscurecida y el título en blanco.
	if ( 'page' === $type ) {
		$items = [];
		foreach ( $q->posts as $page ) {
			$hub     = claudia_hub_category( $page );
			$items[] = [
				'title'    => get_the_title( $page ),
				'url'      => get_permalink( $page ),
				'image_id' => claudia_page_hero_image_id( $page ),
				'eyebrow'  => $hub ? sprintf( _n( '%d guía', '%d guías', claudia_hub_count( $hub ), 'claudia' ), claudia_hub_count( $hub ) ) : '',
			];
		}
		return claudia_story_grid( $items );
	}

	// Un carrusel con 3 tarjetas o menos queda vacío: se muestra como rejilla.
	$carousel = 'carrusel' === $a['estilo'] && $q->post_count > 3;
	ob_start();
	if ( $carousel ) {
		echo '<div class="guides carousel">';
	} else {
		echo '<div class="guides guides-grid' . ( $q->post_count <= 3 ? ' guides-grid--few' : '' ) . '">';
	}
	while ( $q->have_posts() ) {
		$q->the_post();
		claudia_photo_card( [
			'title'    => get_the_title(),
			'url'      => get_permalink(),
			'image_id' => 'page' === $type ? claudia_page_hero_image_id() : (int) get_post_thumbnail_id(),
			'ratio'    => $carousel ? 'portrait' : 'landscape',
			'sizes'    => $carousel ? '(min-width: 1100px) 300px, 70vw' : '(min-width: 1100px) 380px, (min-width: 640px) 46vw, 92vw',
		] );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
} );

/**
 * Categoría asociada a una página hub (guardada en la migración como _claudia_hub_category).
 */
function claudia_hub_category( $post = null ): ?WP_Term {
	$slug = get_post_meta( get_post( $post )->ID, '_claudia_hub_category', true );
	$term = $slug ? get_term_by( 'slug', $slug, 'category' ) : null;
	return $term instanceof WP_Term ? $term : null;
}

/**
 * Foto de cabecera de una página: su destacada o, en un hub, la de la última guía de su categoría.
 */
function claudia_page_hero_image_id( $post = null ): int {
	$post = get_post( $post );
	if ( has_post_thumbnail( $post ) ) {
		return (int) get_post_thumbnail_id( $post );
	}
	$hub = claudia_hub_category( $post );
	return $hub ? claudia_destination_image_id( $hub ) : 0;
}

/**
 * Número de guías del hub: entradas publicadas en la categoría y sus subcategorías.
 */
function claudia_hub_count( WP_Term $term ): int {
	$ids = array_merge( [ $term->term_id ], get_term_children( $term->term_id, 'category' ) );
	return ( new WP_Query( [ 'category__in' => $ids, 'posts_per_page' => 1, 'fields' => 'ids' ] ) )->found_posts;
}
