<?php
/**
 * Sugerencias para búsquedas sin resultados y para la página 404: destinos populares y últimas guías.
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="suggestions">
	<h2 class="section__title">Destinos con más guías</h2>
	<ul class="continents continents--left">
		<?php foreach ( claudia_top_destinations( 12 ) as [ 'term' => $term ] ) : ?>
			<li><a class="pill" href="<?php echo esc_url( claudia_destination_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
		<?php endforeach; ?>
	</ul>

	<h2 class="section__title">Últimas guías</h2>
	<div class="post-grid">
		<?php
		$latest = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 3, 'no_found_rows' => true, 'ignore_sticky_posts' => true ] );
		while ( $latest->have_posts() ) {
			$latest->the_post();
			claudia_post_card( null, false );
		}
		wp_reset_postdata();
		?>
	</div>
</section>
