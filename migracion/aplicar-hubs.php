<?php
/**
 * Aplica hubs.json (salida de elementor-a-bloques.py) a las páginas: contenido en bloques
 * y categoría del hub. Escribe con $wpdb para que kses no altere el HTML (wp-cli no tiene usuario).
 * Uso: wp eval-file aplicar-hubs.php hubs.json dominio-origen
 */
global $wpdb;
[ $file, $from ] = $args;

/**
 * Cambia los enlaces internos que pasan por una redirección 301 del plugin Redirection
 * por su destino final, para no encadenar saltos.
 */
function claudia_resolve_redirects( string $content, string $host ): string {
	global $wpdb;
	static $map = null;
	if ( null === $map ) {
		$map = [];
		foreach ( $wpdb->get_results( "SELECT url, action_data FROM {$wpdb->prefix}redirection_items WHERE status='enabled' AND action_code=301 AND match_type='url' AND regex=0" ) as $r ) {
			$target = maybe_unserialize( $r->action_data );
			$target = is_array( $target ) ? ( $target['url'] ?? '' ) : $target;
			if ( $target ) {
				$map[ untrailingslashit( $r->url ) ] = $target;
			}
		}
	}
	return preg_replace_callback( '#href="(https?://' . preg_quote( $host, '#' ) . ')?(/[^"\#?]*)#', function ( $m ) use ( $map ) {
		$key = untrailingslashit( $m[2] );
		return isset( $map[ $key ] ) ? 'href="' . $map[ $key ] : $m[0];
	}, $content );
}
$to    = wp_parse_url( home_url(), PHP_URL_HOST );
$hubs  = json_decode( file_get_contents( $file ), true );
$done  = 0;
foreach ( $hubs as $h ) {
	$post = get_post( $h['id'] );
	if ( ! $post || 'page' !== $post->post_type ) {
		WP_CLI::warning( "Página {$h['id']} ({$h['slug']}) no encontrada" );
		continue;
	}
	$content = claudia_resolve_redirects( str_replace( $from, $to, $h['content'] ), $to );
	$wpdb->update( $wpdb->posts, [ 'post_content' => $content ], [ 'ID' => $post->ID ] );
	if ( $h['hub_category'] ) {
		update_post_meta( $post->ID, '_claudia_hub_category', $h['hub_category'] );
	}
	clean_post_cache( $post->ID );
	$done++;
}
WP_CLI::success( "$done páginas actualizadas" );
