<?php
global $wpdb;
$ORIGEN = $args[1] ?? DB_NAME; // base de datos de origen (por defecto, la propia)
$rows = $wpdb->get_results( "select p.ID, p.post_name, m.meta_value from {$ORIGEN}.wp_postmeta m join {$ORIGEN}.wp_posts p on p.ID=m.post_id where m.meta_key='_elementor_data' and p.post_type='page' and p.post_status='publish'" );
$out = [];
foreach ( $rows as $r ) { $out[] = [ 'id' => (int) $r->ID, 'slug' => $r->post_name, 'data' => json_decode( $r->meta_value, true ) ]; }
file_put_contents( $args[0], wp_json_encode( $out ) );
echo count( $out );
