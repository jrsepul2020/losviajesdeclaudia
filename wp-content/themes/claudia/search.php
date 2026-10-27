<?php
/**
 * Resultados de búsqueda.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="entry-hero entry-hero--plain">
	<div class="entry-hero__content wrap">
		<h1 class="entry-title">
			<?php echo get_search_query() ? 'Resultados para “' . esc_html( get_search_query() ) . '”' : 'Buscar en el blog'; ?>
		</h1>
		<?php if ( get_search_query() ) : ?>
			<span class="pill"><?php echo esc_html( sprintf( _n( '%d resultado', '%d resultados', (int) $wp_query->found_posts, 'claudia' ), (int) $wp_query->found_posts ) ); ?></span>
		<?php endif; ?>
		<div class="search-big"><?php get_search_form(); ?></div>
	</div>
</header>

<div class="wrap archive-body">
	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php
			while ( have_posts() ) {
				the_post();
				// Las páginas hub también salen en la búsqueda: se muestran con la misma tarjeta.
				claudia_post_card();
			}
			?>
		</div>
		<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '‹ Anteriores', 'next_text' => 'Siguientes ›' ] ); ?>
	<?php else : ?>
		<p class="archive-intro__text">No he encontrado nada con esa búsqueda. Prueba con el nombre de un país o una ciudad, o echa un vistazo a estos destinos:</p>
		<?php get_template_part( 'template-parts/suggestions' ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
