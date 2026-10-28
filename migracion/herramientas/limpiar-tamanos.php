<?php
/**
 * Elimina los tamaños de imagen que el tema nuevo no usa (los del tema anterior y plugins retirados)
 * y, opcionalmente, los JPG/PNG redimensionados que ya tienen su versión .webp.
 *
 * Nunca toca:
 * - el archivo original de cada imagen (ni su .webp);
 * - los tamaños que usa WordPress/el tema: thumbnail, medium, medium_large, large, 1536x1536, 2048x2048 (su .webp siempre se conserva);
 * - cualquier archivo que aparezca escrito en el contenido de entradas, páginas o bloques reutilizables.
 *
 * Uso:
 *   wp eval-file limpiar-tamanos.php                → simulación (no borra nada, solo informa)
 *   wp eval-file limpiar-tamanos.php aplicar         → borra tamaños antiguos y actualiza los metadatos
 *   wp eval-file limpiar-tamanos.php aplicar jpg     → además borra los JPG/PNG redimensionados que tienen .webp
 */

global $wpdb;

$apply   = in_array( 'aplicar', $args, true );
$drop_jpg = in_array( 'jpg', $args, true );
$keep_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048' ];
$uploads    = wp_get_upload_dir();
$base       = trailingslashit( $uploads['basedir'] );

// Archivos citados en el contenido (por ruta relativa a uploads), se conservan siempre.
$referenced = [];
$contents   = $wpdb->get_col( "SELECT post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page','wp_block') AND post_status IN ('publish','draft','private','future','pending')" );
foreach ( $contents as $c ) {
	if ( preg_match_all( '#wp-content/uploads/([^"\'\s?)\]]+?\.(?:jpe?g|png|gif|webp))#i', $c, $m ) ) {
		foreach ( $m[1] as $rel ) {
			$referenced[ preg_replace( '/\.webp$/i', '', rawurldecode( $rel ) ) ] = true;
		}
	}
}

$stats = [ 'adjuntos' => 0, 'tamanos_quitados' => 0, 'archivos' => 0, 'bytes' => 0, 'jpg_archivos' => 0, 'jpg_bytes' => 0, 'protegidos_por_contenido' => 0 ];

$delete = function ( string $path ) use ( $apply, &$stats ) {
	if ( ! is_file( $path ) ) {
		return 0;
	}
	$size = filesize( $path );
	if ( $apply ) {
		unlink( $path );
	}
	return $size;
};

foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'" ) as $id ) {
	$meta = wp_get_attachment_metadata( $id );
	if ( empty( $meta['file'] ) || empty( $meta['sizes'] ) ) {
		continue;
	}
	$stats['adjuntos']++;
	$dir = trailingslashit( dirname( $meta['file'] ) );

	// Archivos que deben quedarse: original + tamaños conservados (pueden compartir nombre con tamaños antiguos).
	$keep_files = [ basename( $meta['file'] ) => true ];
	foreach ( $keep_sizes as $k ) {
		if ( isset( $meta['sizes'][ $k ]['file'] ) ) {
			$keep_files[ $meta['sizes'][ $k ]['file'] ] = true;
		}
	}

	$changed = false;
	foreach ( $meta['sizes'] as $size => $data ) {
		if ( in_array( $size, $keep_sizes, true ) ) {
			continue;
		}
		$file = $data['file'];
		if ( isset( $referenced[ $dir . $file ] ) ) {
			$stats['protegidos_por_contenido']++;
			continue; // se queda archivo y metadato: el contenido lo usa
		}
		unset( $meta['sizes'][ $size ] );
		$changed = true;
		$stats['tamanos_quitados']++;
		if ( ! isset( $keep_files[ $file ] ) ) {
			foreach ( [ $base . $dir . $file, $base . $dir . $file . '.webp' ] as $path ) {
				$b = $delete( $path );
				if ( $b ) {
					$stats['archivos']++;
					$stats['bytes'] += $b;
				}
			}
			$keep_files[ $file ] = true; // evita contar dos veces si otro tamaño apunta al mismo archivo
		}
	}

	// JPG/PNG redimensionados de los tamaños conservados que ya tienen .webp (el tema sirve siempre la .webp).
	if ( $drop_jpg ) {
		foreach ( $keep_sizes as $k ) {
			if ( empty( $meta['sizes'][ $k ]['file'] ) ) {
				continue;
			}
			$file = $meta['sizes'][ $k ]['file'];
			$path = $base . $dir . $file;
			if ( isset( $referenced[ $dir . $file ] ) || ! is_file( $path . '.webp' ) || basename( $meta['file'] ) === $file ) {
				continue;
			}
			$b = $delete( $path );
			if ( $b ) {
				$stats['jpg_archivos']++;
				$stats['jpg_bytes'] += $b;
			}
		}
	}

	if ( $changed && $apply ) {
		wp_update_attachment_metadata( $id, $meta );
	}
}

WP_CLI::log( ( $apply ? 'APLICADO' : 'SIMULACIÓN (no se ha borrado nada)' ) );
WP_CLI::log( sprintf( 'Adjuntos revisados: %d', $stats['adjuntos'] ) );
WP_CLI::log( sprintf( 'Tamaños antiguos quitados de los metadatos: %d', $stats['tamanos_quitados'] ) );
WP_CLI::log( sprintf( 'Protegidos por estar en el contenido: %d', $stats['protegidos_por_contenido'] ) );
WP_CLI::log( sprintf( 'Archivos de tamaños antiguos (jpg+webp): %d — %.2f GB', $stats['archivos'], $stats['bytes'] / 1e9 ) );
if ( $drop_jpg ) {
	WP_CLI::log( sprintf( 'JPG/PNG redimensionados con .webp: %d — %.2f GB', $stats['jpg_archivos'], $stats['jpg_bytes'] / 1e9 ) );
}
