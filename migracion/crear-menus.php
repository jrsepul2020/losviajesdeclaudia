<?php
/**
 * Crea los menús de WordPress a partir de la estructura por defecto del tema y los asigna
 * a sus ubicaciones. Si un menú ya existe con ese nombre, no se toca.
 * Uso: wp eval-file crear-menus.php
 */
$menus = [ 'primary-left' => 'Principal (izquierda)', 'primary-right' => 'Principal (derecha)', 'footer' => 'Pie de página' ];
$locations = get_theme_mod( 'nav_menu_locations', [] );
foreach ( $menus as $location => $name ) {
	if ( wp_get_nav_menu_object( $name ) ) {
		WP_CLI::log( "Ya existe: $name" );
		continue;
	}
	$menu_id = wp_create_nav_menu( $name );
	$add = function ( array $item, int $parent = 0 ) use ( $menu_id ) {
		$page = url_to_postid( $item['url'] );
		$args = [ 'menu-item-title' => $item['label'], 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ];
		$args += $page && false === strpos( $item['url'], '#' )
			? [ 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page ]
			: [ 'menu-item-type' => 'custom', 'menu-item-url' => $item['url'] ];
		return wp_update_nav_menu_item( $menu_id, 0, $args );
	};
	foreach ( claudia_default_menu( $location ) as $item ) {
		$parent = $add( $item );
		foreach ( $item['children'] ?? [] as $child ) {
			$add( $child, $parent );
		}
	}
	$locations[ $location ] = $menu_id;
	WP_CLI::success( "Creado: $name" );
}
set_theme_mod( 'nav_menu_locations', $locations );
