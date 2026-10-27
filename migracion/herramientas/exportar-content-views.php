<?php
global $wpdb;
$ORIGEN = $args[1] ?? DB_NAME; // base de datos de origen (por defecto, la propia)
$rows = $wpdb->get_results( "select m1.meta_value id, m2.meta_value s from {$ORIGEN}.wp_postmeta m1 join {$ORIGEN}.wp_postmeta m2 on m2.post_id=m1.post_id and m2.meta_key='_pt_cv_settings' where m1.meta_key='_pt_cv_id'" );
$out = [];
foreach ( $rows as $r ) {
	$s = maybe_unserialize( $r->s );
	if ( ! is_array( $s ) ) { $out[ $r->id ] = 'ERR'; continue; }
	$keep = [];
	foreach ( $s as $k => $v ) {
		if ( preg_match( '/^pt-cv-(content-type|taxonomy|category-terms|category-operator|post_tag-terms|post_tag-operator|taxonomy-relation|limit|orderby|order|post__in|post__not_in|author__in|s|sticky-posts|pagination-items-per-page|enable-pagination|offset|view-type)$/', $k ) && $v !== '' && $v !== [] ) $keep[ substr( $k, 6 ) ] = $v;
	}
	$out[ $r->id ] = $keep;
}
file_put_contents( $args[0], wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
echo count( $out );
