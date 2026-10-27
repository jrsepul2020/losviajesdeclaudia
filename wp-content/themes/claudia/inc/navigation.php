<?php
/**
 * Menús: si aún no se han creado en Apariencia › Menús, se usa una estructura por defecto
 * que apunta a los hubs existentes, para que la maqueta sea navegable desde el primer día.
 */

defined( 'ABSPATH' ) || exit;

function claudia_page_url( string $path ): string {
	$page = get_page_by_path( $path );
	return $page ? get_permalink( $page ) : home_url( '/' . $path . '/' );
}

function claudia_default_menu( string $location ): array {
	$destinos = [];
	foreach ( [ 'europa' => 'Europa', 'viajar-a-asia' => 'Asia', 'viajar-a-america' => 'América', 'viajar-a-africa' => 'África', 'viajar-a-oceania' => 'Oceanía', 'donde-viajar-en-navidad' => 'Viajar en Navidad' ] as $path => $label ) {
		$destinos[] = [ 'label' => $label, 'url' => claudia_page_url( $path ) ];
	}
	return match ( $location ) {
		'primary-left'  => [
			[ 'label' => 'Destinos', 'url' => claudia_page_url( 'europa' ), 'children' => $destinos ],
			[ 'label' => 'Planifica tu viaje', 'url' => home_url( '/#planifica' ) ],
		],
		'primary-right' => [
			[ 'label' => 'Sobre mí', 'url' => claudia_page_url( 'sobre-mi' ) ],
			[ 'label' => 'Contacto', 'url' => claudia_page_url( 'contacto-los-viajes-de-claudia-campos' ) ],
		],
		'footer'        => [
			[ 'label' => 'Aviso legal', 'url' => claudia_page_url( 'aviso-legal' ) ],
			[ 'label' => 'Política de privacidad', 'url' => claudia_page_url( 'politica-de-privacidad' ) ],
			[ 'label' => 'Política de cookies', 'url' => claudia_page_url( 'politica-de-cookies' ) ],
			[ 'label' => 'Contacto', 'url' => claudia_page_url( 'contacto-los-viajes-de-claudia-campos' ) ],
		],
		default         => [],
	};
}

/**
 * Elementos de un menú como [label, url, children]: los de Apariencia › Menús si el menú está
 * asignado a esa ubicación; si no, la estructura por defecto. Dos niveles como máximo.
 */
function claudia_menu_items( string $location ): array {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return claudia_default_menu( $location );
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] ) ?: [];
	$tree  = [];
	foreach ( $items as $i ) {
		if ( ! $i->menu_item_parent ) {
			$tree[ $i->ID ] = [ 'label' => $i->title, 'url' => $i->url, 'children' => [] ];
		}
	}
	foreach ( $items as $i ) {
		if ( $i->menu_item_parent && isset( $tree[ $i->menu_item_parent ] ) ) {
			$tree[ $i->menu_item_parent ]['children'][] = [ 'label' => $i->title, 'url' => $i->url ];
		}
	}
	return array_values( $tree );
}

/**
 * Destinos del menú principal (los hijos de su primer elemento), para el pie y la portada.
 */
function claudia_menu_destinations(): array {
	foreach ( claudia_menu_items( 'primary-left' ) as $item ) {
		if ( ! empty( $item['children'] ) ) {
			return $item['children'];
		}
	}
	return [];
}

/**
 * Pinta un menú como lista sencilla. Los submenús usan <details>, que funciona sin JavaScript
 * tanto con ratón como en móvil.
 */
function claudia_menu( string $location, string $class = 'menu' ): void {
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( claudia_menu_items( $location ) as $item ) {
		if ( ! empty( $item['children'] ) ) {
			echo '<li class="has-sub"><details><summary>' . esc_html( $item['label'] ) . claudia_icon( 'chevron' ) . '</summary><ul class="sub-menu">';
			foreach ( $item['children'] as $child ) {
				echo '<li><a href="' . esc_url( $child['url'] ) . '">' . esc_html( $child['label'] ) . '</a></li>';
			}
			echo '</ul></details></li>';
		} else {
			echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		}
	}
	echo '</ul>';
}

/**
 * Migas de pan: las de Yoast (ya activadas y coherentes con su schema) o una versión mínima propia.
 */
function claudia_breadcrumbs(): void {
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<nav class="breadcrumbs" aria-label="Migas de pan">', '</nav>' );
		return;
	}
	$cat = is_singular( 'post' ) ? claudia_primary_category() : null;
	echo '<nav class="breadcrumbs" aria-label="Migas de pan"><a href="' . esc_url( home_url( '/' ) ) . '">Inicio</a>';
	if ( $cat ) {
		echo ' › <a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
	}
	echo '</nav>';
}
